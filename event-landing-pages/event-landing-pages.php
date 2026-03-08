<?php
/**
 * Plugin Name: Event Landing Pages
 * Plugin URI:  https://github.com/Cameron-Fulton/event-landing-pages
 * Description: Create and manage event landing pages with HubSpot time slot picker or form embed integration.
 * Version:     1.2.0
 * Author:      Cameron Fulton
 * Author URI:  https://searchactions.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: event-landing-pages
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'ELP_VERSION', '1.2.0' );
define( 'ELP_PLUGIN_FILE', __FILE__ );
define( 'ELP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ELP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// PSR-4 autoloader.
$elp_autoloader = ELP_PLUGIN_DIR . 'vendor/autoload.php';
if ( file_exists( $elp_autoloader ) ) {
    require_once $elp_autoloader;
} else {
    // Fallback: manual autoloader for deployments without Composer.
    spl_autoload_register( function ( $class ) {
        $prefix = 'EventLandingPages\\';
        if ( strpos( $class, $prefix ) !== 0 ) {
            return;
        }
        $relative = substr( $class, strlen( $prefix ) );
        $file     = ELP_PLUGIN_DIR . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
        if ( file_exists( $file ) ) {
            require_once $file;
        }
    } );
}

// GitHub-based update checker.
$elp_puc = ELP_PLUGIN_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';
if ( file_exists( $elp_puc ) ) {
    require_once $elp_puc;
    $elp_update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/Cameron-Fulton/wp-plugin-hubspot-timeslot-booking/',
        ELP_PLUGIN_FILE,
        'event-landing-pages'
    );
    // Plugin lives in a subdirectory of the repo, so download the
    // plugin-only zip attached to each release instead of the source archive.
    $elp_vcs_api = $elp_update_checker->getVcsApi();
    if ( $elp_vcs_api !== null ) {
        $elp_vcs_api->enableReleaseAssets();
    }
}

// Boot the plugin.
add_action( 'plugins_loaded', [ \EventLandingPages\Plugin::class, 'instance' ] );
