<?php

namespace EventLandingPages\PostType;

defined( 'ABSPATH' ) || exit;

class EventPostType {

    public const SLUG = 'elp_event';

    public function __construct() {
        add_action( 'init', [ $this, 'register' ] );
        add_filter( 'post_type_link', [ $this, 'custom_permalink' ], 10, 2 );
        add_action( 'save_post_' . self::SLUG, [ $this, 'sync_slug' ] );
    }

    public function register(): void {
        $labels = [
            'name'               => __( 'Events', 'event-landing-pages' ),
            'singular_name'      => __( 'Event', 'event-landing-pages' ),
            'add_new'            => __( 'Add New Event', 'event-landing-pages' ),
            'add_new_item'       => __( 'Add New Event', 'event-landing-pages' ),
            'edit_item'          => __( 'Edit Event', 'event-landing-pages' ),
            'new_item'           => __( 'New Event', 'event-landing-pages' ),
            'view_item'          => __( 'View Event', 'event-landing-pages' ),
            'search_items'       => __( 'Search Events', 'event-landing-pages' ),
            'not_found'          => __( 'No events found.', 'event-landing-pages' ),
            'not_found_in_trash' => __( 'No events found in Trash.', 'event-landing-pages' ),
            'menu_name'          => __( 'Events', 'event-landing-pages' ),
        ];

        register_post_type( self::SLUG, [
            'labels'        => $labels,
            'public'        => true,
            'show_in_rest'  => true,
            'menu_icon'     => 'dashicons-calendar-alt',
            'supports'      => [ 'title', 'thumbnail' ],
            'has_archive'   => false,
            'rewrite'       => [ 'slug' => 'events', 'with_front' => false ],
            'template_lock' => 'all',
        ] );
    }

    /**
     * Sync the post slug when the custom path ACF field changes.
     *
     * Normalizes the stored path (preserving slashes) and uses only the
     * last segment as post_name for fallback /events/{slug}/ routing.
     */
    public function sync_slug( int $post_id ): void {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        $custom_path = get_field( 'elp_custom_slug', $post_id );
        if ( empty( $custom_path ) ) {
            return;
        }

        $normalized = \EventLandingPages\Routing\CustomPathRouter::normalize_path( $custom_path );

        // Save back the cleaned version if it changed.
        if ( $normalized !== $custom_path ) {
            update_field( 'elp_custom_slug', $normalized, $post_id );
        }

        // Use only the last segment as post_name (for fallback routing).
        $segments  = explode( '/', $normalized );
        $leaf_slug = end( $segments );
        $post      = get_post( $post_id );

        if ( $post && $post->post_name !== $leaf_slug ) {
            remove_action( 'save_post_' . self::SLUG, [ $this, 'sync_slug' ] );
            wp_update_post( [
                'ID'        => $post_id,
                'post_name' => $leaf_slug,
            ] );
            add_action( 'save_post_' . self::SLUG, [ $this, 'sync_slug' ] );
        }
    }

    /**
     * Replace the permalink with the custom path if set.
     */
    public function custom_permalink( string $url, \WP_Post $post ): string {
        if ( $post->post_type !== self::SLUG ) {
            return $url;
        }

        $custom_path = get_field( 'elp_custom_slug', $post->ID );
        if ( ! empty( $custom_path ) ) {
            $normalized = \EventLandingPages\Routing\CustomPathRouter::normalize_path( $custom_path );
            return home_url( '/' . $normalized . '/' );
        }

        return $url;
    }
}
