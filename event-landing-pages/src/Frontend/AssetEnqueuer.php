<?php

namespace EventLandingPages\Frontend;

use EventLandingPages\PostType\EventPostType;

defined( 'ABSPATH' ) || exit;

class AssetEnqueuer {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
    }

    public function enqueue(): void {
        if ( ! is_singular( EventPostType::SLUG ) ) {
            return;
        }

        $post_id = get_the_ID();
        if ( ! $post_id ) {
            return;
        }

        $font_heading = get_field( 'elp_font_heading', 'option' ) ?: '';
        $font_body    = get_field( 'elp_font_body', 'option' ) ?: '';

        $style_deps = [];

        // Only load Google Fonts if custom fonts are configured.
        if ( $font_heading || $font_body ) {
            $families = [];
            if ( $font_heading ) {
                $families[] = 'family=' . rawurlencode( $font_heading ) . ':wght@400;500;600;700';
            }
            if ( $font_body ) {
                $families[] = 'family=' . rawurlencode( $font_body ) . ':wght@300;400;500;600';
            }
            wp_enqueue_style(
                'elp-google-fonts',
                'https://fonts.googleapis.com/css2?' . implode( '&', $families ) . '&display=swap',
                [],
                null
            );
            $style_deps[] = 'elp-google-fonts';
        }

        // Main stylesheet.
        wp_enqueue_style(
            'elp-frontend',
            ELP_PLUGIN_URL . 'assets/css/event-frontend.css',
            $style_deps,
            ELP_VERSION
        );

        // Inject font CSS custom properties when custom fonts are set.
        if ( $font_heading || $font_body ) {
            $vars = [];
            if ( $font_heading ) {
                $vars[] = '--elp-font-heading:\'' . esc_attr( $font_heading ) . '\', sans-serif';
            }
            if ( $font_body ) {
                $vars[] = '--elp-font-body:\'' . esc_attr( $font_body ) . '\', sans-serif';
            }
            wp_add_inline_style( 'elp-frontend', ':root{' . implode( ';', $vars ) . '}' );
        }

        $booking_method = get_field( 'elp_booking_method', $post_id ) ?: 'timeslots';

        if ( 'timeslots' === $booking_method ) {
            $this->enqueue_timeslots( $post_id );
        } else {
            $this->enqueue_hubspot_form( $post_id );
        }
    }

    private function enqueue_timeslots( int $post_id ): void {
        wp_enqueue_script(
            'elp-timeslots',
            ELP_PLUGIN_URL . 'assets/js/event-timeslots.js',
            [],
            ELP_VERSION,
            true
        );

        $timezone_value = get_field( 'elp_default_timezone', 'option' ) ?: 'America/Denver';
        $timezone = in_array( $timezone_value, timezone_identifiers_list(), true ) ? $timezone_value : 'America/Denver';

        wp_localize_script( 'elp-timeslots', 'elpEventConfig', [
            'restUrl'             => esc_url_raw( rest_url( 'elp/v1' ) ),
            'nonce'               => wp_create_nonce( 'wp_rest' ),
            'slug'                => trim( sanitize_text_field( get_field( 'elp_hubspot_slug', $post_id ) ?: '' ), '/' ),
            'timezone'            => $timezone,
            'targetDate'          => sanitize_text_field( get_field( 'elp_target_date', $post_id ) ?: '' ),
            'ctaLabel'            => sanitize_text_field( get_field( 'elp_cta_label', $post_id ) ?: 'Reserve My Spot' ),
            'confirmationMessage' => sanitize_text_field( get_field( 'elp_confirmation_message', $post_id ) ?: 'Check your email for confirmation details.' ),
        ] );
    }

    private function enqueue_hubspot_form( int $post_id ): void {
        wp_enqueue_script(
            'elp-hubspot-form',
            ELP_PLUGIN_URL . 'assets/js/event-hubspot-form.js',
            [],
            ELP_VERSION,
            true
        );

        $portal_id = get_field( 'elp_hubspot_portal_id', $post_id )
                     ?: get_field( 'elp_default_portal_id', 'option' );

        wp_localize_script( 'elp-hubspot-form', 'elpEventConfig', [
            'portalId' => $portal_id ? (string) absint( $portal_id ) : '',
            'formId'   => sanitize_text_field( get_field( 'elp_hubspot_form_id', $post_id ) ?: '' ),
        ] );
    }
}
