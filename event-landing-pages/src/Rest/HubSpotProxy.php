<?php

namespace EventLandingPages\Rest;

defined( 'ABSPATH' ) || exit;

class HubSpotProxy {

    private const NAMESPACE = 'elp/v1';
    private const HUBSPOT_AVAILABILITY  = 'https://api.hubapi.com/meetings-public/v3/book/availability-page';
    private const HUBSPOT_MEETING_CONFIG = 'https://api.hubapi.com/meetings-public/v3/book';
    private const HUBSPOT_BOOK           = 'https://api.hubspot.com/meetings-public/v1/book';

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

        register_rest_route( self::NAMESPACE, '/meeting-config', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_meeting_config' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'slug'     => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'timezone' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
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

    /**
     * Proxy a GET request to HubSpot's meetings-public API.
     *
     * @return array{code: int, body: mixed}|\WP_REST_Response Error response on failure.
     */
    private function proxy_hubspot_get( string $endpoint, array $params ) {
        $url = add_query_arg( $params, $endpoint );

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

        return [
            'code' => wp_remote_retrieve_response_code( $response ),
            'body' => json_decode( wp_remote_retrieve_body( $response ), true ),
        ];
    }

    public function get_availability( \WP_REST_Request $request ): \WP_REST_Response {
        $result = $this->proxy_hubspot_get( self::HUBSPOT_AVAILABILITY, [
            'slug'        => $request->get_param( 'slug' ),
            'timezone'    => $request->get_param( 'timezone' ),
            'monthOffset' => $request->get_param( 'monthOffset' ),
        ] );

        if ( $result instanceof \WP_REST_Response ) {
            return $result;
        }

        return new \WP_REST_Response( $result['body'], $result['code'] );
    }

    public function get_meeting_config( \WP_REST_Request $request ): \WP_REST_Response {
        $slug          = $request->get_param( 'slug' );
        $transient_key = 'elp_mtg_cfg_' . md5( $slug );
        $cached        = get_transient( $transient_key );

        if ( false !== $cached ) {
            return new \WP_REST_Response( $cached, 200 );
        }

        $result = $this->proxy_hubspot_get( self::HUBSPOT_MEETING_CONFIG, [
            'slug'     => $slug,
            'timezone' => $request->get_param( 'timezone' ),
        ] );

        if ( $result instanceof \WP_REST_Response ) {
            return $result;
        }

        // Return only formFields to avoid leaking busy times or other config.
        if ( $result['code'] >= 200 && $result['code'] < 300 && isset( $result['body']['customParams']['formFields'] ) ) {
            $safe = [ 'formFields' => $result['body']['customParams']['formFields'] ];
            set_transient( $transient_key, $safe, 12 * HOUR_IN_SECONDS );
            return new \WP_REST_Response( $safe, 200 );
        }

        // Non-success or missing data — pass through without caching.
        return new \WP_REST_Response( $result['body'], $result['code'] );
    }

    /**
     * Get the origin URL used for HubSpot API requests.
     * HubSpot's meetings-public API checks Origin/Referer headers.
     */
    private static function hubspot_origin(): string {
        return (string) apply_filters( 'elp_hubspot_origin', home_url() );
    }

    public function book_slot( \WP_REST_Request $request ): \WP_REST_Response {
        // Extract contact fields from the formFields array sent by the JS.
        // Accepts any field name defined in the HubSpot meeting form.
        $form_fields_raw = $request->get_param( 'formFields' );
        $contact          = [];

        foreach ( $form_fields_raw as $field ) {
            if ( ! isset( $field['name'], $field['value'] ) ) {
                continue;
            }
            $name = sanitize_key( $field['name'] );
            if ( $name ) {
                $contact[ $name ] = sanitize_text_field( $field['value'] );
            }
        }

        // HubSpot v1 booking API expects flat fields, not the v3 formFields array.
        // Slug must be a query parameter, not in the JSON body.
        $slug = sanitize_text_field( $request->get_param( 'slug' ) );
        $book_url = add_query_arg( 'slug', $slug, self::HUBSPOT_BOOK );

        // Map formField names to HubSpot v1 flat-key equivalents.
        $v1_map = [
            'firstname' => 'firstName',
            'lastname'  => 'lastName',
            'email'     => 'email',
            'phone'     => 'phone',
        ];

        $payload = [
            'timezone'  => sanitize_text_field( $request->get_param( 'timezone' ) ),
            'duration'  => absint( $request->get_param( 'duration' ) ),
            'startTime' => absint( $request->get_param( 'startMillisUtc' ) ),
        ];

        foreach ( $contact as $name => $value ) {
            if ( '' === $value ) {
                continue;
            }
            $key             = isset( $v1_map[ $name ] ) ? $v1_map[ $name ] : $name;
            $payload[ $key ] = $value;
        }

        $response = wp_remote_post( $book_url, [
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
