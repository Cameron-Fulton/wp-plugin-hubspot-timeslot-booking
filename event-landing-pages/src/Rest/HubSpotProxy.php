<?php

namespace EventLandingPages\Rest;

defined( 'ABSPATH' ) || exit;

class HubSpotProxy {

    private const NAMESPACE = 'elp/v1';
    private const HUBSPOT_AVAILABILITY = 'https://api.hubapi.com/meetings-public/v3/book/availability-page';
    private const HUBSPOT_BOOK         = 'https://api.hubapi.com/meetings-public/v1/book';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes(): void {
        register_rest_route( self::NAMESPACE, '/availability', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_availability' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'slug'        => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'timezone'    => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'monthOffset' => [
                    'required'          => false,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                    'default'           => 0,
                ],
            ],
        ] );

        register_rest_route( self::NAMESPACE, '/book', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'book_slot' ],
            'permission_callback' => [ $this, 'verify_nonce' ],
            'args'                => [
                'slug'           => [ 'required' => true, 'type' => 'string' ],
                'timezone'       => [ 'required' => true, 'type' => 'string' ],
                'duration'       => [ 'required' => true, 'type' => 'integer' ],
                'startMillisUtc' => [ 'required' => true, 'type' => 'integer' ],
                'formFields'     => [ 'required' => true, 'type' => 'array' ],
            ],
        ] );
    }

    public function verify_nonce( \WP_REST_Request $request ): bool {
        $nonce = $request->get_header( 'X-WP-Nonce' );
        return wp_verify_nonce( $nonce, 'wp_rest' ) !== false;
    }

    public function get_availability( \WP_REST_Request $request ): \WP_REST_Response {
        $url = add_query_arg(
            [
                'slug'        => $request->get_param( 'slug' ),
                'timezone'    => $request->get_param( 'timezone' ),
                'monthOffset' => $request->get_param( 'monthOffset' ),
            ],
            self::HUBSPOT_AVAILABILITY
        );

        $response = wp_remote_get( $url, [
            'timeout' => 15,
            'headers' => [
                'Accept'  => 'application/json',
                'Origin'  => self::hubspot_origin(),
                'Referer' => self::hubspot_origin() . '/',
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return new \WP_REST_Response(
                [ 'message' => $response->get_error_message() ],
                502
            );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        return new \WP_REST_Response( $body, $code );
    }

    /**
     * Get the origin URL used for HubSpot API requests.
     * HubSpot's meetings-public API checks Origin/Referer headers.
     */
    private static function hubspot_origin(): string {
        return (string) apply_filters( 'elp_hubspot_origin', home_url() );
    }

    public function book_slot( \WP_REST_Request $request ): \WP_REST_Response {
        // Whitelist only expected fields.
        $allowed_form_fields = [ 'firstname', 'lastname', 'email', 'phone' ];
        $form_fields         = [];

        foreach ( $request->get_param( 'formFields' ) as $field ) {
            if ( ! isset( $field['name'], $field['value'] ) ) {
                continue;
            }
            if ( ! in_array( $field['name'], $allowed_form_fields, true ) ) {
                continue;
            }
            $form_fields[] = [
                'name'  => sanitize_text_field( $field['name'] ),
                'value' => sanitize_text_field( $field['value'] ),
            ];
        }

        $payload = [
            'slug'           => sanitize_text_field( $request->get_param( 'slug' ) ),
            'timezone'       => sanitize_text_field( $request->get_param( 'timezone' ) ),
            'duration'       => absint( $request->get_param( 'duration' ) ),
            'startMillisUtc' => absint( $request->get_param( 'startMillisUtc' ) ),
            'formFields'     => $form_fields,
        ];

        $response = wp_remote_post( self::HUBSPOT_BOOK, [
            'timeout' => 15,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
                'Origin'       => self::hubspot_origin(),
                'Referer'      => self::hubspot_origin() . '/',
            ],
            'body' => wp_json_encode( $payload ),
        ] );

        if ( is_wp_error( $response ) ) {
            return new \WP_REST_Response(
                [ 'message' => $response->get_error_message() ],
                502
            );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        return new \WP_REST_Response( $body, $code );
    }
}
