<?php

namespace EventLandingPages\Admin;

use EventLandingPages\Security\Encryption;

defined( 'ABSPATH' ) || exit;

class SettingsPage {

    public function __construct() {
        add_action( 'acf/init', [ $this, 'register_options_page' ] );
        add_action( 'acf/init', [ $this, 'register_fields' ] );

        // Encrypt the API key on save.
        add_filter( 'acf/update_value/key=elp_hubspot_api_key', [ $this, 'encrypt_api_key' ], 10, 3 );
        add_filter( 'acf/load_value/key=elp_hubspot_api_key', [ $this, 'decrypt_api_key' ], 10, 3 );
    }

    public function register_options_page(): void {
        if ( ! function_exists( 'acf_add_options_page' ) ) {
            return;
        }
        acf_add_options_page( [
            'page_title' => __( 'Event Landing Pages Settings', 'event-landing-pages' ),
            'menu_title' => __( 'Settings', 'event-landing-pages' ),
            'menu_slug'  => 'elp-settings',
            'parent_slug' => 'edit.php?post_type=elp_event',
            'capability' => 'manage_options',
        ] );
    }

    public function register_fields(): void {
        if ( ! function_exists( 'acf_add_options_page' ) ) {
            return;
        }
        acf_add_local_field_group( [
            'key'      => 'group_elp_global_settings',
            'title'    => __( 'Global Settings', 'event-landing-pages' ),
            'location' => [
                [
                    [
                        'param'    => 'options_page',
                        'operator' => '==',
                        'value'    => 'elp-settings',
                    ],
                ],
            ],
            'fields' => [
                [
                    'key'   => 'elp_tab_api',
                    'label' => __( 'API', 'event-landing-pages' ),
                    'type'  => 'tab',
                ],
                [
                    'key'          => 'elp_hubspot_api_key',
                    'label'        => __( 'HubSpot API Key', 'event-landing-pages' ),
                    'name'         => 'elp_hubspot_api_key',
                    'type'         => 'password',
                    'instructions' => __( 'Encrypted at rest. Used for server-side HubSpot API calls.', 'event-landing-pages' ),
                ],
                [
                    'key'           => 'elp_default_timezone',
                    'label'         => __( 'Default Timezone', 'event-landing-pages' ),
                    'name'          => 'elp_default_timezone',
                    'type'          => 'select',
                    'choices'       => self::timezone_choices(),
                    'default_value' => 'America/Denver',
                ],
                [
                    'key'   => 'elp_tab_default_brand',
                    'label' => __( 'Default Brand', 'event-landing-pages' ),
                    'type'  => 'tab',
                ],
                [
                    'key'   => 'elp_default_brand_name',
                    'label' => __( 'Brand Name', 'event-landing-pages' ),
                    'name'  => 'elp_default_brand_name',
                    'type'  => 'text',
                ],
                [
                    'key'           => 'elp_default_brand_logo',
                    'label'         => __( 'Brand Logo', 'event-landing-pages' ),
                    'name'          => 'elp_default_brand_logo',
                    'type'          => 'image',
                    'return_format' => 'array',
                ],
                [
                    'key'   => 'elp_default_brand_website',
                    'label' => __( 'Brand Website', 'event-landing-pages' ),
                    'name'  => 'elp_default_brand_website',
                    'type'  => 'url',
                ],
                [
                    'key'           => 'elp_default_brand_logo_invert',
                    'label'         => __( 'Invert Logo (dark background)', 'event-landing-pages' ),
                    'name'          => 'elp_default_brand_logo_invert',
                    'type'          => 'true_false',
                    'ui'            => 1,
                    'default_value' => 0,
                ],
            ],
        ] );
    }

    /**
     * Encrypt before storing in DB.
     *
     * @param mixed $value
     * @return string
     */
    public function encrypt_api_key( $value, $post_id, array $field ): string {
        if ( empty( $value ) || ! Encryption::is_available() ) {
            return '';
        }
        return Encryption::encrypt( $value );
    }

    /**
     * Decrypt when loading for display in admin.
     *
     * @param mixed $value
     * @return string
     */
    public function decrypt_api_key( $value, $post_id, array $field ): string {
        if ( empty( $value ) || ! Encryption::is_available() ) {
            return '';
        }
        return Encryption::decrypt( $value );
    }

    /**
     * Get a subset of common US timezones.
     */
    private static function timezone_choices(): array {
        return [
            'America/New_York'    => 'Eastern (America/New_York)',
            'America/Chicago'     => 'Central (America/Chicago)',
            'America/Denver'      => 'Mountain (America/Denver)',
            'America/Phoenix'     => 'Arizona (America/Phoenix)',
            'America/Los_Angeles' => 'Pacific (America/Los_Angeles)',
            'America/Anchorage'   => 'Alaska (America/Anchorage)',
            'Pacific/Honolulu'    => 'Hawaii (Pacific/Honolulu)',
        ];
    }
}
