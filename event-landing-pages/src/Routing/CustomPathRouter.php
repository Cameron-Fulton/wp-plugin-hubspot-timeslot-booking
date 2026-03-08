<?php

namespace EventLandingPages\Routing;

use EventLandingPages\PostType\EventPostType;

defined( 'ABSPATH' ) || exit;

/**
 * Routes arbitrary-depth custom URL paths to elp_event posts.
 *
 * Maintains a cached path→post_id lookup map and intercepts incoming
 * requests via the `request` filter before WordPress runs its main query.
 */
class CustomPathRouter {

    private const CACHE_KEY = 'elp_custom_path_map';

    public function __construct() {
        add_filter( 'request', [ $this, 'resolve_custom_path' ], 1 );
        add_filter( 'pre_handle_404', [ $this, 'prevent_false_404' ], 10, 2 );

        add_action( 'save_post_' . EventPostType::SLUG, [ $this, 'flush_path_cache' ] );
        add_action( 'delete_post', [ $this, 'maybe_flush_path_cache' ] );
        add_action( 'trashed_post', [ $this, 'maybe_flush_path_cache' ] );
        add_action( 'untrashed_post', [ $this, 'maybe_flush_path_cache' ] );
    }

    /**
     * Intercept the request and resolve custom paths to elp_event posts.
     */
    public function resolve_custom_path( array $query_vars ): array {
        if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
            return $query_vars;
        }

        // If WP already resolved to a specific post, don't interfere
        // (unless it resolved as a pagename — deep paths look like hierarchical pages).
        if ( isset( $query_vars['name'] ) || isset( $query_vars['p'] ) || isset( $query_vars['page_id'] ) ) {
            return $query_vars;
        }

        $request_path = $this->get_request_path();
        if ( empty( $request_path ) ) {
            return $query_vars;
        }

        $map = self::get_path_map();

        // Direct match against the request path.
        if ( isset( $map[ $request_path ] ) ) {
            return [
                'post_type' => EventPostType::SLUG,
                'p'         => $map[ $request_path ],
            ];
        }

        // WP may have interpreted the deep path as a hierarchical page lookup.
        if ( isset( $query_vars['pagename'] ) ) {
            $pagename = self::normalize_path( $query_vars['pagename'] );
            if ( isset( $map[ $pagename ] ) ) {
                return [
                    'post_type' => EventPostType::SLUG,
                    'p'         => $map[ $pagename ],
                ];
            }
        }

        return $query_vars;
    }

    /**
     * Prevent WordPress from serving a 404 when we resolved an event post
     * via a custom path that doesn't match any rewrite rule.
     */
    public function prevent_false_404( bool $preempt, \WP_Query $query ): bool {
        if ( $query->is_singular( EventPostType::SLUG ) && $query->found_posts > 0 ) {
            return true;
        }
        return $preempt;
    }

    /**
     * Get the path→post_id map, using object cache → option → DB query.
     */
    public static function get_path_map(): array {
        $map = wp_cache_get( self::CACHE_KEY, 'elp' );
        if ( is_array( $map ) ) {
            return $map;
        }

        $map = get_option( self::CACHE_KEY, false );
        if ( is_array( $map ) ) {
            wp_cache_set( self::CACHE_KEY, $map, 'elp' );
            return $map;
        }

        $map = self::build_path_map();
        update_option( self::CACHE_KEY, $map, true );
        wp_cache_set( self::CACHE_KEY, $map, 'elp' );
        return $map;
    }

    /**
     * Query all published elp_event posts with a custom path.
     */
    private static function build_path_map(): array {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT pm.post_id, pm.meta_value
                 FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                 WHERE pm.meta_key = %s
                   AND pm.meta_value != ''
                   AND p.post_type = %s
                   AND p.post_status = 'publish'",
                EventPostType::META_CUSTOM_PATH,
                EventPostType::SLUG
            )
        );

        $map = [];
        foreach ( $results as $row ) {
            $path = self::normalize_path( $row->meta_value );
            if ( $path ) {
                $map[ $path ] = (int) $row->post_id;
            }
        }
        return $map;
    }

    /**
     * Sanitize a URL path while preserving slashes.
     *
     * Splits on `/`, applies sanitize_title() to each segment, and rejoins.
     */
    public static function normalize_path( string $path ): string {
        $path = trim( $path, " \t\n\r\0\x0B/" );

        $segments = array_filter(
            array_map( 'sanitize_title', explode( '/', $path ) )
        );

        return implode( '/', $segments );
    }

    /**
     * Flush the cache only when the post is an elp_event.
     */
    public function maybe_flush_path_cache( int $post_id ): void {
        if ( get_post_type( $post_id ) === EventPostType::SLUG ) {
            $this->flush_path_cache();
        }
    }

    /**
     * Flush the cached path map.
     */
    public function flush_path_cache(): void {
        delete_option( self::CACHE_KEY );
        wp_cache_delete( self::CACHE_KEY, 'elp' );

        // Eagerly rebuild so the next frontend request doesn't pay the cost.
        self::get_path_map();
    }

    /**
     * Extract the clean request path from the current request.
     */
    private function get_request_path(): string {
        global $wp;

        if ( ! empty( $wp->request ) ) {
            return self::normalize_path( $wp->request );
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = wp_parse_url( $uri, PHP_URL_PATH );
        if ( ! $path ) {
            return '';
        }

        // Strip the site's subdirectory prefix if WordPress is in a subfolder.
        $home_path = wp_parse_url( home_url(), PHP_URL_PATH );
        if ( $home_path && str_starts_with( $path, $home_path ) ) {
            $path = substr( $path, strlen( $home_path ) );
        }

        return self::normalize_path( $path );
    }
}
