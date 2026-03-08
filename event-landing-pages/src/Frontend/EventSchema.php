<?php

namespace EventLandingPages\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Outputs JSON-LD Event schema markup on single event landing pages.
 *
 * All required + recommended properties per Google's Event rich result spec:
 * name, startDate, location, endDate, description, image, eventStatus,
 * eventAttendanceMode, offers, organizer.
 *
 * Filterable via `elp_event_schema` for theme/plugin customization.
 */
class EventSchema {

	public function __construct() {
		add_action( 'wp_head', [ $this, 'output_schema' ] );
	}

	/**
	 * Print the JSON-LD script tag on single elp_event pages.
	 */
	public function output_schema(): void {
		if ( ! is_singular( 'elp_event' ) ) {
			return;
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return;
		}

		$schema = $this->build_schema( $post_id );
		if ( empty( $schema ) ) {
			return;
		}

		echo '<script type="application/ld+json">' . "\n";
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-LD requires unescaped output.
		echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
		echo "\n</script>\n";
	}

	/**
	 * Build the Schema.org Event array from ACF fields and brand data.
	 *
	 * @return array Schema data, or empty array if startDate is missing.
	 */
	private function build_schema( int $post_id ): array {
		$event_start = get_field( 'elp_event_start', $post_id );
		if ( ! $event_start ) {
			return []; // startDate is required by Google.
		}

		$brand         = BrandResolver::resolve( $post_id );
		$event_end     = get_field( 'elp_event_end', $post_id );
		$description   = get_field( 'elp_event_description', $post_id );
		$location_name = get_field( 'elp_location_name', $post_id );
		$location_addr = get_field( 'elp_location_address', $post_id );
		$event_price   = get_field( 'elp_event_price', $post_id );
		$event_image   = get_field( 'elp_event_image', $post_id );

		$timezone = get_field( 'elp_default_timezone', 'option' ) ?: 'America/Denver';

		$schema = [
			'@context'            => 'https://schema.org',
			'@type'               => 'Event',
			'@id'                 => get_permalink( $post_id ) . '#event',
			'name'                => get_the_title( $post_id ),
			'startDate'           => $this->format_date( $event_start, $timezone ),
			'eventStatus'         => 'https://schema.org/EventScheduled',
			'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
			'url'                 => get_permalink( $post_id ),
		];

		// End date.
		if ( $event_end ) {
			$schema['endDate'] = $this->format_date( $event_end, $timezone );
		}

		// Description — strip HTML for structured data.
		if ( $description ) {
			$schema['description'] = wp_strip_all_tags( $description );
		}

		// Image — per-event image, then post thumbnail fallback.
		$image_url = $this->get_image_url( $event_image, $post_id );
		if ( $image_url ) {
			$schema['image'] = [ $image_url ];
		}

		// Location — Place with PostalAddress.
		if ( $location_name || $location_addr ) {
			$location = [ '@type' => 'Place' ];

			if ( $location_name ) {
				$location['name'] = $location_name;
			}
			if ( $location_addr ) {
				$location['address'] = [
					'@type'          => 'PostalAddress',
					'streetAddress'  => $location_addr,
					'addressCountry' => 'US',
				];
			}

			$schema['location'] = $location;
		}

		// Offers — pricing and availability.
		$price_numeric = $this->extract_price( $event_price );

		$offer = [
			'@type'         => 'Offer',
			'url'           => get_permalink( $post_id ),
			'priceCurrency' => 'USD',
			'availability'  => 'https://schema.org/InStock',
		];

		if ( null !== $price_numeric ) {
			$offer['price'] = $price_numeric;

			if ( '0' === $price_numeric ) {
				$schema['isAccessibleForFree'] = true;
			}
		}

		$schema['offers'] = $offer;

		// Organizer — from resolved brand data.
		if ( $brand['name'] || $brand['website'] ) {
			$organizer = [ '@type' => 'Organization' ];

			if ( $brand['name'] ) {
				$organizer['name'] = $brand['name'];
			}
			if ( $brand['website'] ) {
				$organizer['url'] = $brand['website'];
			}

			$schema['organizer'] = $organizer;
		}

		/**
		 * Filter the Event schema array before JSON-LD output.
		 *
		 * @param array $schema  The Schema.org Event data.
		 * @param int   $post_id The event post ID.
		 */
		return apply_filters( 'elp_event_schema', $schema, $post_id );
	}

	/**
	 * Convert stored date (Y-m-d H:i:s) to ISO 8601 with timezone offset.
	 */
	private function format_date( string $date_string, string $timezone_string ): string {
		try {
			$tz = new \DateTimeZone( $timezone_string );
			$dt = new \DateTime( $date_string, $tz );

			return $dt->format( 'Y-m-d\TH:iP' );
		} catch ( \Exception $e ) {
			return $date_string;
		}
	}

	/**
	 * Get the best available image URL for the event.
	 */
	private function get_image_url( $event_image, int $post_id ): string {
		// Per-event ACF image field.
		if ( is_array( $event_image ) && ! empty( $event_image['url'] ) ) {
			return $event_image['url'];
		}

		// Post thumbnail fallback.
		$thumbnail_url = get_the_post_thumbnail_url( $post_id, 'full' );

		return $thumbnail_url ?: '';
	}

	/**
	 * Extract a numeric price string from the display price field.
	 *
	 * Handles formats like "$99", "$99.00", "99", "Free".
	 *
	 * @return string|null Numeric price string (e.g. "99"), or null if empty.
	 */
	private function extract_price( ?string $price_field ): ?string {
		if ( ! $price_field ) {
			return null;
		}

		if ( stripos( $price_field, 'free' ) !== false ) {
			return '0';
		}

		$numeric = preg_replace( '/[^0-9.]/', '', $price_field );

		return '' !== $numeric ? $numeric : null;
	}
}
