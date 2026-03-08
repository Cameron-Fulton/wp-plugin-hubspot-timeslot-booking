<?php
/**
 * Template: Single Event Landing Page
 *
 * Standalone HTML page (no get_header/get_footer) for a full-bleed
 * event landing page experience. Theme can override by placing
 * event-landing-pages/single-elp_event.php in its directory.
 */

defined( 'ABSPATH' ) || exit;

use EventLandingPages\Frontend\BrandResolver;

$post_id        = get_the_ID();
if ( ! $post_id ) {
    return;
}
$brand          = BrandResolver::resolve( $post_id );
$has_partner    = get_field( 'elp_has_partner', $post_id );
$partner_name   = get_field( 'elp_partner_name', $post_id );
$partner_logo   = get_field( 'elp_partner_logo', $post_id );
$partner_website = get_field( 'elp_partner_website', $post_id );

$event_badge    = get_field( 'elp_event_badge', $post_id ) ?: '';
$event_price    = get_field( 'elp_event_price', $post_id ) ?: '';
$price_label    = get_field( 'elp_price_label', $post_id ) ?: '';
$description    = get_field( 'elp_event_description', $post_id ) ?: '';
$duration_label = get_field( 'elp_duration_label', $post_id ) ?: '';
$location_name  = get_field( 'elp_location_name', $post_id ) ?: '';
$location_addr  = get_field( 'elp_location_address', $post_id ) ?: '';
$event_start    = get_field( 'elp_event_start', $post_id ) ?: '';
$event_end      = get_field( 'elp_event_end', $post_id ) ?: '';
$cta_label      = get_field( 'elp_cta_label', $post_id ) ?: 'Reserve My Spot';
$booking_method = get_field( 'elp_booking_method', $post_id ) ?: 'timeslots';

// Format dates.
$date_display = '';
$time_display = '';
if ( $event_start ) {
    $start_ts     = strtotime( $event_start );
    $date_display = date_i18n( 'F j, Y', $start_ts );

    if ( $event_end ) {
        $end_ts       = strtotime( $event_end );
        $time_display = date_i18n( 'g:i A', $start_ts ) . ' – ' . date_i18n( 'g:i A', $end_ts );
    }
}

// Per-event color overrides (inline CSS vars).
$color_fields = [
    'elp_color_accent'      => '--elp-accent',
    'elp_color_accent_dark' => '--elp-accent-dark',
    'elp_color_background'  => '--elp-dark',
    'elp_color_surface'     => '--elp-dark-surface',
    'elp_color_text'        => '--elp-text',
    'elp_color_text_muted'  => '--elp-text-muted',
    'elp_color_gold'        => '--elp-gold',
];
$color_overrides = [];
foreach ( $color_fields as $field_key => $css_var ) {
    $val = get_field( $field_key, $post_id );
    if ( ! empty( $val ) ) {
        $color_overrides[] = esc_attr( $css_var ) . ':' . esc_attr( $val );
    }
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title( '|', true, 'right' ); bloginfo( 'name' ); ?></title>
    <?php wp_head(); ?>
    <?php if ( ! empty( $color_overrides ) ) : ?>
    <style>:root{<?php echo implode( ';', $color_overrides ); ?>}</style>
    <?php endif; ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- Top Bar -->
<div class="elp-top-bar">
    <?php echo esc_html( $brand['name'] ); ?>
    <?php if ( $has_partner && $partner_name ) : ?>
        <span class="elp-separator">&times;</span>
        <?php echo esc_html( $partner_name ); ?>
    <?php endif; ?>
</div>

<div class="elp-container">

    <!-- Logos -->
    <div class="elp-logos">
        <?php if ( $brand['logo_url'] ) : ?>
        <div class="elp-logo<?php echo $brand['invert'] ? ' elp-logo--invert' : ''; ?>">
            <?php if ( $brand['website'] ) : ?>
                <a href="<?php echo esc_url( $brand['website'] ); ?>" target="_blank" rel="noopener">
            <?php endif; ?>
            <img src="<?php echo esc_url( $brand['logo_url'] ); ?>"
                 alt="<?php echo esc_attr( $brand['logo_alt'] ?: $brand['name'] ); ?>">
            <?php if ( $brand['website'] ) : ?>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ( $has_partner && is_array( $partner_logo ) && ! empty( $partner_logo['url'] ) ) : ?>
        <div class="elp-logo-divider"></div>
        <div class="elp-logo">
            <?php if ( $partner_website ) : ?>
                <a href="<?php echo esc_url( $partner_website ); ?>" target="_blank" rel="noopener">
            <?php endif; ?>
            <img src="<?php echo esc_url( $partner_logo['url'] ); ?>"
                 alt="<?php echo esc_attr( $partner_logo['alt'] ?? $partner_name ); ?>">
            <?php if ( $partner_website ) : ?>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Hero Text -->
    <div class="elp-hero-text">
        <?php if ( $event_badge ) : ?>
        <div class="elp-event-badge"><?php echo esc_html( $event_badge ); ?></div>
        <?php endif; ?>

        <h1>
            <?php if ( $event_price ) : ?>
                Book Your <span class="elp-price"><?php echo esc_html( $event_price ); ?></span>
                <?php echo esc_html( $price_label ?: get_the_title() ); ?>
            <?php else : ?>
                <?php echo esc_html( get_the_title() ); ?>
            <?php endif; ?>
        </h1>

        <?php if ( $description ) : ?>
        <div class="elp-subheadline">
            <?php echo wp_kses_post( $description ); ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Event Details Grid -->
    <?php if ( $date_display || $location_name || $time_display ) : ?>
    <div class="elp-event-details">
        <?php if ( $date_display ) : ?>
        <div class="elp-event-detail">
            <div class="elp-detail-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4A90D9" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><rect x="7" y="14" width="4" height="4" rx="0.5" fill="#4A90D9" stroke="none"/></svg>
            </div>
            <div class="elp-detail-label"><?php esc_html_e( 'Date', 'event-landing-pages' ); ?></div>
            <div class="elp-detail-value"><?php echo esc_html( $date_display ); ?></div>
        </div>
        <?php endif; ?>

        <?php if ( $location_name ) : ?>
        <div class="elp-event-detail">
            <div class="elp-detail-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" stroke="#E84C6A" stroke-width="1.5"/><circle cx="12" cy="9" r="2.5" fill="#E84C6A"/></svg>
            </div>
            <div class="elp-detail-label"><?php esc_html_e( 'Location', 'event-landing-pages' ); ?></div>
            <div class="elp-detail-value"><?php echo esc_html( $location_name ); ?></div>
        </div>
        <?php endif; ?>

        <?php if ( $time_display ) : ?>
        <div class="elp-event-detail">
            <div class="elp-detail-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#8B9DC3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
            </div>
            <div class="elp-detail-label"><?php esc_html_e( 'Time Slots', 'event-landing-pages' ); ?></div>
            <div class="elp-detail-value"><?php echo esc_html( $time_display ); ?></div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Booking Section -->
    <div class="elp-form-section">
        <h2><?php echo esc_html( $cta_label ); ?></h2>

        <?php if ( $duration_label ) : ?>
        <p class="elp-form-subtitle"><?php echo esc_html( $duration_label ); ?></p>
        <?php endif; ?>

        <?php if ( 'timeslots' === $booking_method ) : ?>
        <!-- Time Slot Picker -->
        <div class="elp-time-slots visible" id="elpTimeSlots">
            <div class="elp-time-slots-header">
                <span class="elp-time-slots-date" id="elpTimeSlotsDate"></span>
                <span class="elp-time-slots-label" id="elpTimeSlotsLabel"><?php esc_html_e( 'Loading available times...', 'event-landing-pages' ); ?></span>
            </div>
            <div class="elp-time-slots-grid" id="elpTimeSlotsGrid">
                <div class="elp-loading-spinner"><span><?php esc_html_e( 'Checking availability...', 'event-landing-pages' ); ?></span></div>
            </div>
        </div>

        <!-- Contact Form (shown after selecting time) -->
        <div class="elp-contact-form" id="elpContactForm">
            <div class="elp-selected-time-badge" id="elpSelectedTimeBadge"></div>
            <form id="elpBookingForm">
                <div class="elp-field-row">
                    <div class="elp-field">
                        <label for="elpFirstName"><?php esc_html_e( 'First Name', 'event-landing-pages' ); ?></label>
                        <input type="text" id="elpFirstName" name="firstName" required>
                    </div>
                    <div class="elp-field">
                        <label for="elpLastName"><?php esc_html_e( 'Last Name', 'event-landing-pages' ); ?></label>
                        <input type="text" id="elpLastName" name="lastName" required>
                    </div>
                </div>
                <div class="elp-field">
                    <label for="elpEmail"><?php esc_html_e( 'Email', 'event-landing-pages' ); ?></label>
                    <input type="email" id="elpEmail" name="email" required>
                </div>
                <div class="elp-field">
                    <label for="elpPhone"><?php esc_html_e( 'Phone Number', 'event-landing-pages' ); ?></label>
                    <input type="tel" id="elpPhone" name="phone">
                </div>
                <button type="submit" class="elp-btn-submit" id="elpSubmitBtn"><?php echo esc_html( $cta_label ); ?></button>
            </form>
        </div>

        <!-- Confirmation -->
        <div id="elpConfirmation" style="display:none;"></div>

        <p class="elp-spots-notice" id="elpSpotsNotice"><strong><?php esc_html_e( 'Limited availability', 'event-landing-pages' ); ?></strong> — <?php esc_html_e( 'secure yours today.', 'event-landing-pages' ); ?></p>

        <?php elseif ( 'hubspot_form' === $booking_method ) : ?>
        <!-- HubSpot Form Embed -->
        <div id="elpHubspotFormTarget"></div>
        <?php endif; ?>
    </div>

</div>

<!-- Footer -->
<footer class="elp-footer">
    &copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?>
    <?php if ( $brand['name'] ) : ?>
        <?php echo esc_html( $brand['name'] ); ?>
    <?php endif; ?>
    <?php if ( $brand['website'] ) : ?>
        &middot; <a href="<?php echo esc_url( $brand['website'] ); ?>"><?php echo esc_html( wp_parse_url( $brand['website'], PHP_URL_HOST ) ); ?></a>
    <?php endif; ?>
</footer>

<?php wp_footer(); ?>
</body>
</html>
