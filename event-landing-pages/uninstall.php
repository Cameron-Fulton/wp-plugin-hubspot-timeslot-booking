<?php
/**
 * Uninstall handler for Event Landing Pages.
 *
 * Removes all plugin data: custom posts, ACF fields, options.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Delete all elp_event posts and their meta.
$posts = get_posts( [
    'post_type'      => 'elp_event',
    'post_status'    => 'any',
    'posts_per_page' => -1,
    'fields'         => 'ids',
] );

foreach ( $posts as $post_id ) {
    wp_delete_post( $post_id, true );
}

// Delete global options.
$options = [
    'options_elp_hubspot_api_key',
    'options_elp_default_timezone',
    'options_elp_default_brand_name',
    'options_elp_default_brand_logo',
    'options_elp_default_brand_website',
    'options_elp_default_brand_logo_invert',
    'elp_custom_path_map',
];

foreach ( $options as $option ) {
    delete_option( $option );
    delete_option( '_' . $option ); // ACF reference key.
}

// Flush rewrite rules.
flush_rewrite_rules();
