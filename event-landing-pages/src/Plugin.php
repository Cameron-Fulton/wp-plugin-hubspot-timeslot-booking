<?php

namespace EventLandingPages;

defined( 'ABSPATH' ) || exit;

final class Plugin {

    private static ?Plugin $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Always check for ACF dependency.
        new Admin\AdminNotices();

        if ( ! $this->has_acf() ) {
            return;
        }

        // Core registrations.
        new PostType\EventPostType();
        new ACF\FieldGroups();
        new Routing\CustomPathRouter();
        new Admin\SettingsPage();

        // Frontend.
        new Frontend\TemplateLoader();
        new Frontend\AssetEnqueuer();

        // REST API.
        new Rest\HubSpotProxy();

        // i18n.
        add_action( 'init', [ $this, 'load_textdomain' ] );
    }

    private function has_acf(): bool {
        return class_exists( 'ACF' );
    }

    public function load_textdomain(): void {
        load_plugin_textdomain(
            'event-landing-pages',
            false,
            dirname( plugin_basename( ELP_PLUGIN_FILE ) ) . '/languages'
        );
    }
}
