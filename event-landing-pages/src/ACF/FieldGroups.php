<?php

namespace EventLandingPages\ACF;

defined( 'ABSPATH' ) || exit;

class FieldGroups {

    public function __construct() {
        add_action( 'acf/init', [ $this, 'register' ] );
        add_filter( 'acf/validate_value/name=elp_custom_slug', [ $this, 'validate_custom_path' ], 10, 4 );
    }

    public function register(): void {
        acf_add_local_field_group( [
            'key'      => 'group_elp_event_fields',
            'title'    => __( 'Event Configuration', 'event-landing-pages' ),
            'location' => [
                [
                    [
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => 'elp_event',
                    ],
                ],
            ],
            'fields' => array_merge(
                $this->brand_tab(),
                $this->partner_tab(),
                $this->event_details_tab(),
                $this->booking_tab(),
                $this->url_tab(),
                $this->colors_tab()
            ),
            'style'    => 'seamless',
            'position' => 'normal',
        ] );
    }

    private function brand_tab(): array {
        return [
            [
                'key'   => 'elp_tab_brand',
                'label' => __( 'Brand', 'event-landing-pages' ),
                'type'  => 'tab',
            ],
            [
                'key'           => 'elp_use_global_brand',
                'label'         => __( 'Use Global Brand Defaults', 'event-landing-pages' ),
                'name'          => 'elp_use_global_brand',
                'type'          => 'true_false',
                'ui'            => 1,
                'default_value' => 1,
                'instructions'  => __( 'When enabled, uses the brand settings from the global settings page.', 'event-landing-pages' ),
            ],
            [
                'key'               => 'elp_brand_name',
                'label'             => __( 'Brand Name', 'event-landing-pages' ),
                'name'              => 'elp_brand_name',
                'type'              => 'text',
                'conditional_logic' => [
                    [
                        [
                            'field'    => 'elp_use_global_brand',
                            'operator' => '!=',
                            'value'    => '1',
                        ],
                    ],
                ],
            ],
            [
                'key'               => 'elp_brand_logo',
                'label'             => __( 'Brand Logo', 'event-landing-pages' ),
                'name'              => 'elp_brand_logo',
                'type'              => 'image',
                'return_format'     => 'array',
                'conditional_logic' => [
                    [
                        [
                            'field'    => 'elp_use_global_brand',
                            'operator' => '!=',
                            'value'    => '1',
                        ],
                    ],
                ],
            ],
            [
                'key'               => 'elp_brand_website',
                'label'             => __( 'Brand Website', 'event-landing-pages' ),
                'name'              => 'elp_brand_website',
                'type'              => 'url',
                'conditional_logic' => [
                    [
                        [
                            'field'    => 'elp_use_global_brand',
                            'operator' => '!=',
                            'value'    => '1',
                        ],
                    ],
                ],
            ],
            [
                'key'               => 'elp_brand_logo_invert',
                'label'             => __( 'Invert Logo (dark background)', 'event-landing-pages' ),
                'name'              => 'elp_brand_logo_invert',
                'type'              => 'true_false',
                'ui'                => 1,
                'default_value'     => 0,
                'conditional_logic' => [
                    [
                        [
                            'field'    => 'elp_use_global_brand',
                            'operator' => '!=',
                            'value'    => '1',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function partner_tab(): array {
        return [
            [
                'key'   => 'elp_tab_partner',
                'label' => __( 'Partner', 'event-landing-pages' ),
                'type'  => 'tab',
            ],
            [
                'key'           => 'elp_has_partner',
                'label'         => __( 'Has Partner', 'event-landing-pages' ),
                'name'          => 'elp_has_partner',
                'type'          => 'true_false',
                'ui'            => 1,
                'default_value' => 0,
            ],
            [
                'key'               => 'elp_partner_name',
                'label'             => __( 'Partner Name', 'event-landing-pages' ),
                'name'              => 'elp_partner_name',
                'type'              => 'text',
                'conditional_logic' => [
                    [
                        [
                            'field'    => 'elp_has_partner',
                            'operator' => '==',
                            'value'    => '1',
                        ],
                    ],
                ],
            ],
            [
                'key'               => 'elp_partner_website',
                'label'             => __( 'Partner Website', 'event-landing-pages' ),
                'name'              => 'elp_partner_website',
                'type'              => 'url',
                'conditional_logic' => [
                    [
                        [
                            'field'    => 'elp_has_partner',
                            'operator' => '==',
                            'value'    => '1',
                        ],
                    ],
                ],
            ],
            [
                'key'               => 'elp_partner_logo',
                'label'             => __( 'Partner Logo', 'event-landing-pages' ),
                'name'              => 'elp_partner_logo',
                'type'              => 'image',
                'return_format'     => 'array',
                'conditional_logic' => [
                    [
                        [
                            'field'    => 'elp_has_partner',
                            'operator' => '==',
                            'value'    => '1',
                        ],
                    ],
                ],
            ],
            [
                'key'               => 'elp_partner_address',
                'label'             => __( 'Partner Address', 'event-landing-pages' ),
                'name'              => 'elp_partner_address',
                'type'              => 'textarea',
                'rows'              => 3,
                'conditional_logic' => [
                    [
                        [
                            'field'    => 'elp_has_partner',
                            'operator' => '==',
                            'value'    => '1',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function event_details_tab(): array {
        return [
            [
                'key'   => 'elp_tab_event_details',
                'label' => __( 'Event Details', 'event-landing-pages' ),
                'type'  => 'tab',
            ],
            [
                'key'            => 'elp_event_start',
                'label'          => __( 'Event Start', 'event-landing-pages' ),
                'name'           => 'elp_event_start',
                'type'           => 'date_time_picker',
                'display_format' => 'F j, Y g:i a',
                'return_format'  => 'Y-m-d H:i:s',
                'required'       => 1,
            ],
            [
                'key'            => 'elp_event_end',
                'label'          => __( 'Event End', 'event-landing-pages' ),
                'name'           => 'elp_event_end',
                'type'           => 'date_time_picker',
                'display_format' => 'F j, Y g:i a',
                'return_format'  => 'Y-m-d H:i:s',
                'required'       => 1,
            ],
            [
                'key'   => 'elp_location_name',
                'label' => __( 'Location Name', 'event-landing-pages' ),
                'name'  => 'elp_location_name',
                'type'  => 'text',
            ],
            [
                'key'   => 'elp_location_address',
                'label' => __( 'Location Address', 'event-landing-pages' ),
                'name'  => 'elp_location_address',
                'type'  => 'text',
            ],
            [
                'key'           => 'elp_event_price',
                'label'         => __( 'Event Price', 'event-landing-pages' ),
                'name'          => 'elp_event_price',
                'type'          => 'text',
                'placeholder'   => '$99',
            ],
            [
                'key'           => 'elp_price_label',
                'label'         => __( 'Price Label', 'event-landing-pages' ),
                'name'          => 'elp_price_label',
                'type'          => 'text',
                'placeholder'   => 'Comprehensive Hormone Panel',
            ],
            [
                'key'           => 'elp_event_badge',
                'label'         => __( 'Event Badge Text', 'event-landing-pages' ),
                'name'          => 'elp_event_badge',
                'type'          => 'text',
                'placeholder'   => 'Exclusive One-Day Event',
            ],
            [
                'key'           => 'elp_duration_label',
                'label'         => __( 'Duration Label', 'event-landing-pages' ),
                'name'          => 'elp_duration_label',
                'type'          => 'text',
                'placeholder'   => '20-minute blood draw',
            ],
            [
                'key'       => 'elp_event_description',
                'label'     => __( 'Event Description', 'event-landing-pages' ),
                'name'      => 'elp_event_description',
                'type'      => 'wysiwyg',
                'tabs'      => 'all',
                'toolbar'   => 'basic',
                'media_upload' => 0,
            ],
            [
                'key'           => 'elp_event_image',
                'label'         => __( 'Event Image', 'event-landing-pages' ),
                'name'          => 'elp_event_image',
                'type'          => 'image',
                'return_format' => 'array',
            ],
            [
                'key'           => 'elp_cta_label',
                'label'         => __( 'CTA Button Label', 'event-landing-pages' ),
                'name'          => 'elp_cta_label',
                'type'          => 'text',
                'placeholder'   => 'Reserve My Spot',
                'default_value' => 'Reserve My Spot',
            ],
            [
                'key'           => 'elp_confirmation_message',
                'label'         => __( 'Confirmation Message', 'event-landing-pages' ),
                'name'          => 'elp_confirmation_message',
                'type'          => 'textarea',
                'rows'          => 3,
                'placeholder'   => 'Check your email for confirmation details.',
                'default_value' => 'Check your email for confirmation details.',
            ],
        ];
    }

    private function booking_tab(): array {
        return [
            [
                'key'   => 'elp_tab_booking',
                'label' => __( 'Booking', 'event-landing-pages' ),
                'type'  => 'tab',
            ],
            [
                'key'           => 'elp_booking_method',
                'label'         => __( 'Booking Method', 'event-landing-pages' ),
                'name'          => 'elp_booking_method',
                'type'          => 'button_group',
                'choices'       => [
                    'timeslots'    => __( 'Time Slot Picker', 'event-landing-pages' ),
                    'hubspot_form' => __( 'HubSpot Form', 'event-landing-pages' ),
                ],
                'default_value' => 'timeslots',
                'layout'        => 'horizontal',
            ],
            [
                'key'               => 'elp_hubspot_slug',
                'label'             => __( 'HubSpot Meeting Slug', 'event-landing-pages' ),
                'name'              => 'elp_hubspot_slug',
                'type'              => 'text',
                'instructions'      => __( 'e.g., username/meeting-name', 'event-landing-pages' ),
                'conditional_logic' => [
                    [
                        [
                            'field'    => 'elp_booking_method',
                            'operator' => '==',
                            'value'    => 'timeslots',
                        ],
                    ],
                ],
            ],
            [
                'key'               => 'elp_target_date',
                'label'             => __( 'Target Date', 'event-landing-pages' ),
                'name'              => 'elp_target_date',
                'type'              => 'date_picker',
                'display_format'    => 'F j, Y',
                'return_format'     => 'Y-m-d',
                'instructions'      => __( 'Only show time slots for this specific date.', 'event-landing-pages' ),
                'conditional_logic' => [
                    [
                        [
                            'field'    => 'elp_booking_method',
                            'operator' => '==',
                            'value'    => 'timeslots',
                        ],
                    ],
                ],
            ],
            [
                'key'               => 'elp_hubspot_portal_id',
                'label'             => __( 'HubSpot Portal ID', 'event-landing-pages' ),
                'name'              => 'elp_hubspot_portal_id',
                'type'              => 'text',
                'conditional_logic' => [
                    [
                        [
                            'field'    => 'elp_booking_method',
                            'operator' => '==',
                            'value'    => 'hubspot_form',
                        ],
                    ],
                ],
            ],
            [
                'key'               => 'elp_hubspot_form_id',
                'label'             => __( 'HubSpot Form ID', 'event-landing-pages' ),
                'name'              => 'elp_hubspot_form_id',
                'type'              => 'text',
                'conditional_logic' => [
                    [
                        [
                            'field'    => 'elp_booking_method',
                            'operator' => '==',
                            'value'    => 'hubspot_form',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function url_tab(): array {
        return [
            [
                'key'   => 'elp_tab_url',
                'label' => __( 'URL', 'event-landing-pages' ),
                'type'  => 'tab',
            ],
            [
                'key'          => 'elp_custom_slug',
                'label'        => __( 'Custom URL Path', 'event-landing-pages' ),
                'name'         => 'elp_custom_slug',
                'type'         => 'text',
                'placeholder'  => 'locations/co/fort-collins/event/event-name',
                'instructions' => __( 'Enter a full URL path (e.g. locations/co/fort-collins/event/event-name). No leading or trailing slashes. Leave empty to use the default /events/{slug}/ URL.', 'event-landing-pages' ),
            ],
        ];
    }

    /**
     * Validate the custom URL path field.
     *
     * @param mixed  $valid      Whether the value is valid.
     * @param mixed  $value      The field value.
     * @param array  $field      The field config.
     * @param string $input_name The input name.
     * @return mixed True if valid, or an error message string.
     */
    public function validate_custom_path( $valid, $value, $field, $input_name ) {
        if ( ! $valid || empty( $value ) ) {
            return $valid;
        }

        $normalized = \EventLandingPages\Routing\CustomPathRouter::normalize_path( $value );

        // Check reserved WordPress prefixes.
        $reserved      = [ 'wp-admin', 'wp-content', 'wp-json', 'wp-login', 'feed', 'comments' ];
        $first_segment = explode( '/', $normalized )[0];
        if ( in_array( $first_segment, $reserved, true ) ) {
            return __( 'This path uses a reserved WordPress prefix.', 'event-landing-pages' );
        }

        // Check for conflicts with existing pages.
        $page = get_page_by_path( $normalized );
        if ( $page ) {
            return __( 'This path conflicts with an existing page.', 'event-landing-pages' );
        }

        // Check for duplicate event paths.
        $current_post_id = (int) ( $_POST['post_ID'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
        $router          = new \EventLandingPages\Routing\CustomPathRouter();
        $map             = $router->get_path_map();
        if ( isset( $map[ $normalized ] ) && $map[ $normalized ] !== $current_post_id ) {
            return __( 'This path is already used by another event.', 'event-landing-pages' );
        }

        return $valid;
    }

    private function colors_tab(): array {
        return [
            [
                'key'   => 'elp_tab_colors',
                'label' => __( 'Colors', 'event-landing-pages' ),
                'type'  => 'tab',
            ],
            [
                'key'          => 'elp_colors_message',
                'label'        => '',
                'name'         => '',
                'type'         => 'message',
                'message'      => __( 'Colors inherit from your theme\'s CSS custom properties by default. Only set values here to override for this specific event.', 'event-landing-pages' ),
            ],
            [
                'key'   => 'elp_color_accent',
                'label' => __( 'Accent', 'event-landing-pages' ),
                'name'  => 'elp_color_accent',
                'type'  => 'color_picker',
            ],
            [
                'key'   => 'elp_color_accent_dark',
                'label' => __( 'Accent Dark', 'event-landing-pages' ),
                'name'  => 'elp_color_accent_dark',
                'type'  => 'color_picker',
            ],
            [
                'key'   => 'elp_color_background',
                'label' => __( 'Background', 'event-landing-pages' ),
                'name'  => 'elp_color_background',
                'type'  => 'color_picker',
            ],
            [
                'key'   => 'elp_color_surface',
                'label' => __( 'Surface', 'event-landing-pages' ),
                'name'  => 'elp_color_surface',
                'type'  => 'color_picker',
            ],
            [
                'key'   => 'elp_color_text',
                'label' => __( 'Text', 'event-landing-pages' ),
                'name'  => 'elp_color_text',
                'type'  => 'color_picker',
            ],
            [
                'key'   => 'elp_color_text_muted',
                'label' => __( 'Text Muted', 'event-landing-pages' ),
                'name'  => 'elp_color_text_muted',
                'type'  => 'color_picker',
            ],
            [
                'key'   => 'elp_color_gold',
                'label' => __( 'Gold / Highlight', 'event-landing-pages' ),
                'name'  => 'elp_color_gold',
                'type'  => 'color_picker',
            ],
        ];
    }
}
