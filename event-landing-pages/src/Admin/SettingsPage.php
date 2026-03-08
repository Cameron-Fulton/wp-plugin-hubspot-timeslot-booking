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
        if ( ! function_exists( '\acf_add_options_page' ) ) {
            return;
        }
        \acf_add_options_page( [
            'page_title' => __( 'Event Landing Pages Settings', 'event-landing-pages' ),
            'menu_title' => __( 'Settings', 'event-landing-pages' ),
            'menu_slug'  => 'elp-settings',
            'parent_slug' => 'edit.php?post_type=elp_event',
            'capability' => 'manage_options',
        ] );
    }

    public function register_fields(): void {
        if ( ! function_exists( '\acf_add_local_field_group' ) ) {
            return;
        }
        \acf_add_local_field_group( [
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
                    'key'   => 'elp_tab_hubspot',
                    'label' => __( 'HubSpot', 'event-landing-pages' ),
                    'type'  => 'tab',
                ],
                [
                    'key'          => 'elp_default_portal_id',
                    'label'        => __( 'Default Portal ID', 'event-landing-pages' ),
                    'name'         => 'elp_default_portal_id',
                    'type'         => 'text',
                    'instructions' => __( 'Your HubSpot Portal (Hub) ID. Find it in HubSpot under Settings → Account Management. Used as the default for form embeds.', 'event-landing-pages' ),
                ],
                [
                    'key'          => 'elp_hubspot_api_key',
                    'label'        => __( 'Private App Access Token', 'event-landing-pages' ),
                    'name'         => 'elp_hubspot_api_key',
                    'type'         => 'password',
                    'instructions' => __( 'Optional. Encrypted at rest. Required for server-side HubSpot API calls (not needed for form embeds or time slot picker).', 'event-landing-pages' ),
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
                    'key'           => 'elp_enable_country_code',
                    'label'         => __( 'Phone Country Code', 'event-landing-pages' ),
                    'name'          => 'elp_enable_country_code',
                    'type'          => 'true_false',
                    'ui'            => 1,
                    'default_value' => 0,
                    'instructions'  => __( 'Show a country code dropdown next to the phone field on event landing pages. Prepends the code (e.g. +1) so HubSpot phone validation passes.', 'event-landing-pages' ),
                ],
                [
                    'key'              => 'elp_default_country_code',
                    'label'            => __( 'Default Country Code', 'event-landing-pages' ),
                    'name'             => 'elp_default_country_code',
                    'type'             => 'select',
                    'choices'          => self::country_code_choices(),
                    'default_value'    => '+1',
                    'conditional_logic' => [
                        [ [ 'field' => 'elp_enable_country_code', 'operator' => '==', 'value' => '1' ] ],
                    ],
                ],
                [
                    'key'   => 'elp_tab_typography',
                    'label' => __( 'Typography', 'event-landing-pages' ),
                    'type'  => 'tab',
                ],
                [
                    'key'          => 'elp_font_heading',
                    'label'        => __( 'Heading Font', 'event-landing-pages' ),
                    'name'         => 'elp_font_heading',
                    'type'         => 'text',
                    'placeholder'  => 'Oswald',
                    'instructions' => __( 'Google Font family name for headings and labels. Leave blank to inherit from your theme.', 'event-landing-pages' ),
                ],
                [
                    'key'          => 'elp_font_body',
                    'label'        => __( 'Body Font', 'event-landing-pages' ),
                    'name'         => 'elp_font_body',
                    'type'         => 'text',
                    'placeholder'  => 'Source Sans 3',
                    'instructions' => __( 'Google Font family name for body text and form inputs. Leave blank to inherit from your theme.', 'event-landing-pages' ),
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
     * Country calling codes for the phone field dropdown.
     */
    public static function country_code_choices(): array {
        return [
            '+1'   => '+1 (US / Canada)',
            '+44'  => '+44 (UK)',
            '+61'  => '+61 (Australia)',
            '+64'  => '+64 (New Zealand)',
            '+91'  => '+91 (India)',
            '+49'  => '+49 (Germany)',
            '+33'  => '+33 (France)',
            '+39'  => '+39 (Italy)',
            '+34'  => '+34 (Spain)',
            '+351' => '+351 (Portugal)',
            '+31'  => '+31 (Netherlands)',
            '+32'  => '+32 (Belgium)',
            '+41'  => '+41 (Switzerland)',
            '+43'  => '+43 (Austria)',
            '+353' => '+353 (Ireland)',
            '+46'  => '+46 (Sweden)',
            '+47'  => '+47 (Norway)',
            '+45'  => '+45 (Denmark)',
            '+48'  => '+48 (Poland)',
            '+81'  => '+81 (Japan)',
            '+82'  => '+82 (South Korea)',
            '+86'  => '+86 (China)',
            '+65'  => '+65 (Singapore)',
            '+60'  => '+60 (Malaysia)',
            '+63'  => '+63 (Philippines)',
            '+66'  => '+66 (Thailand)',
            '+84'  => '+84 (Vietnam)',
            '+62'  => '+62 (Indonesia)',
            '+55'  => '+55 (Brazil)',
            '+52'  => '+52 (Mexico)',
            '+971' => '+971 (UAE)',
            '+972' => '+972 (Israel)',
            '+27'  => '+27 (South Africa)',
            '+234' => '+234 (Nigeria)',
            '+254' => '+254 (Kenya)',
            '+7'   => '+7 (Russia)',
            '+90'  => '+90 (Turkey)',
        ];
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
