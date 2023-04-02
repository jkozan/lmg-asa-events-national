<?php 
/*
	Plugin Name: LMG ASA Events National
	Plugin URI: https://github.com/jkozan/lmg-asa-events-national/
	Description: WordPress Plugin to extend "MyCalendar". Fetches and adds events from ASA National's Calendar.
	Author: Jeremy Kozan
	Author URI: https://www.lmgnow.com/
	Requires at least: 5.1
	Tested up to: 6.1.1
	Stable tag: 1.0.1
	Version: 1.0.1
	Requires PHP: 7.1
	Text Domain: lmgevtasanat
	Domain Path: /languages
	License: GPL v2 or later
*/

/*
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 
	2 of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	with this program. If not, visit: https://www.gnu.org/licenses/
	
	Copyright 2023 Lifestyles Media Group. All rights reserved.
*/

if ( ! defined( 'ABSPATH' ) ) die();

if ( ! class_exists( 'LMG_ASA_Events_National' ) ) :
class LMG_ASA_Events_National {
	
	function __construct() {

		define( 'LMGASAEVTNAT_VERSION',  '1.0.1'                        );
		//define( 'LMGASAEVTNAT_DIR',      trailingslashit( __DIR__ )     );
		define( 'LMGASAEVTNAT_DIR_URL',  plugin_dir_url( __FILE__ )     );
		//define( 'LMGASAEVTNAT_SETTINGS', get_option( 'lmgasaevtnat_settings' ) );

		/*if ( is_admin() ) {
			require_once 'admin/settings.php';
			new LMG_ASA_Events_National_Settings();
		}*/

		add_filter( 'mc_after_calendar', array( $this, 'init' ) );

		add_action( 'shutdown', array( $this, 'ingest' ) );

	}

	function init() {

		wp_enqueue_script( 'lmgasaevtnat', LMGASAEVTNAT_DIR_URL . 'js/script.js',  array( 'jquery' ), LMGASAEVTNAT_VERSION, true );
		//wp_enqueue_style(  'lmgasaevtnat', LMGASAEVTNAT_DIR_URL . 'css/style.css', array(          ), LMGASAEVTNAT_VERSION       );

		add_action( 'wp_footer', array( $this, 'output' ) );

	}

	// fetch something if update is needed (perform max one remote request per page view).
	function ingest() {

		if ( is_admin() ) return false;

		$events = get_option( 'lmgasaevtnat_events' );

		// fetch list of events once per day
		if ( ! isset( $events['fetch_date'] ) || empty( $events['fetch_date'] ) || $this->is_expired( $events['fetch_date'] ) ) {
			@$this->fetch_list( $events );
			return false;
		}

		// fetch one event per pageview if there is an expired data point (expiry is 1 day)
		if ( isset( $events ) && is_array( $events ) && ! empty( $events ) ) {
			@$this->fetch_event( $events );
			return false;
		}

		return false;

	}

	function fetch_event( $items ) {

		$events = $items;

		foreach ( $items as $key => $item ) {

			if ( ! is_array( $item ) ) continue;
			if ( isset( $item['fetch_date'] ) && ! $this->is_expired( $item['fetch_date'] ) ) continue;

			require_once 'includes/simple_html_dom.php';

			$html = file_get_html( $item['url'] );

			$event = $events[$key];

			$event['title']       = trim( $html->find( 'div.mn-page-heading h1',                       0 )->plaintext );
			$event['image']       = trim( $html->find( 'div.mn-image img',                             0 )->src       );
			$event['flyer']       = trim( $html->find( 'div#mn-event-gallery div.mn-gallery-item img', 0 )->src       );
			$event['description'] = trim( $html->find( 'div.mn-details-description div.mn-text',       0 )->innertext );
			$event['location']    = trim( $html->find( 'div.mn-location-description div.mn-text',      0 )->innertext );
			$event['price']       = trim( $html->find( 'div.mn-pricing-description',                   0 )->innertext );

			$event['start_time'] = $this->get_start_time( $html->find( 'div.mn-date', 0 )->plaintext );
			$event['end_time']   = $this->get_end_time(   $html->find( 'div.mn-date', 0 )->plaintext );

			$event['fetch_date']  = date( 'Y-m-d' );

			$events[$key] = $event;

			update_option( 'lmgasaevtnat_events', $events, true );

			break;

		}

		return false;

	}

	function fetch_list( $list ) {

		require_once 'includes/simple_html_dom.php';

		$html = file_get_html( 'https://members.asaonline.com/calendar/Search?mode=0' );
		$items = $html->find( 'div#mn-infinite-scroll div.mn-listing' );

		foreach ( $items as $item ) {

			$event = array();

			$event['url'] = trim( $item->find( 'a.mn-main-heading', 0 )->href );

			// if item exists in list, don't update it. ( it will get updated when it is fetched from fetch_event() )
			if ( isset( $list[$event['url']] ) && ! empty( $list[$event['url']] ) ) continue;

			$event['excerpt'] = trim( $item->find( 'div.mn-description div.mn-text', 0 )->plaintext );
			$event['start_date'] = trim( $item->find( 'span.mn-date', 0 )->plaintext );

			// parse date field to set start & end dates
			if ( false !== strpos( $event['start_date'], '-' ) ) {
				$dates = explode( '-', $event['start_date'] );
				$event['start_date'] = $this->format_date( reset( $dates ) );
				$event['end_date'] = $this->format_date( end( $dates ) );
			} else {
				$event['start_date'] = $this->format_date( $event['start_date'] );
				$event['end_date'] = $event['start_date'];
			}

			// add/update list item
			$list[$event['url']] = $event;

		}

		// set date list was fetched
		$list['fetch_date'] = date( 'Y-m-d' );

		// save/overwrite list in db
		update_option( 'lmgasaevtnat_events', $list, true );

		return false;

	}

	function is_expired( $date = '1970-01-01' ) {

		$current_date = date( 'Y-m-d' );

		if ( $current_date != $date ) {
			return true;
		}

		return false;

	}

	function format_date( $date ) {

		return date( 'Y-m-d', strtotime( trim( $date ) ) );

	}

	function get_start_time( $str ) {

		$str = str_replace( '  ', ' ', trim( $str ) );

		$start_time_r = explode( ' ', $str );

		return trim( $start_time_r[4], '(' ) . ' ' . $start_time_r[5];

	}

	function get_end_time( $str ) {

		$str = str_replace( '  ', ' ', trim( $str ) );

		$start_time_r = explode( ' ', $str );

		if ( 1 === substr_count( $str, '(' ) ) {

			// Tuesday, March 7, 2023 12:00 AM - Saturday, March 11, 2023 12:00 AM (EST)
			return $start_time_r[11] . ' ' . $start_time_r[12];

		} else if ( 2 === substr_count( $str, '(' ) ) {

			// Wednesday, March 8, 2023 (6:00 PM - 8:00 PM) (EST)
			return $start_time_r[7] . ' ' . trim( $start_time_r[8], ')' );

		}

	}

	function output() {

		$events = get_option( 'lmgasaevtnat_events' );

		if ( empty( $events ) ) return false;

		$output = '<div id="asa-events-hidden" style="display: none;">';

		foreach ( $events as $event ) :

			if (
				   ! is_array( $event )
				|| empty( $event['start_date'] )
				|| empty( $event['start_time'] )
				|| empty( $event['end_date'] )
				|| empty( $event['end_time'] )
				|| empty( $event['title'] )
				|| empty( $event['url'] )
			) {
				continue;
			}

			$start_date = date( 'F d, Y', strtotime( $event['start_date'] ) );
			$start_time = strtolower( $event['start_time'] );
			$start_datetime = date( 'Y-m-d\TH:i:s', strtotime( $event['start_date'] . ' ' . $event['start_time'] ) );

			$end_date = date( 'F d, Y', strtotime( $event['end_date'] ) );
			$end_time = strtolower( $event['end_time'] );
			$end_datetime = date( 'Y-m-d\TH:i:s', strtotime( $event['end_date'] . ' ' . $event['end_time'] ) );

			//$start_day = date( 'd', strtotime( $event['start_date'] ) );
			//$start_month = date( 'm', strtotime( $event['start_date'] ) );

			$event_id = wp_unique_id( 'asa-event_' . $start_datetime . '_' );
			$title_id = wp_unique_id( 'asa-event-title_' . $start_datetime . '_' );
			$details_id = wp_unique_id( 'asa-event-details_' . $start_datetime . '_' );

			ob_start();

			?>

				<div id="<?php echo $event_id; ?>" class="mc-mc_calendar_3 calendar-event mc_general future-event mc_primary_general nonrecurring mc-2-hours mc-start-16-00 mc-events mc-event mc_rel_general">
					
					<h3 class="event-title summary" id="<?php echo $title_id; ?>">
						<a href="#<?php echo $details_id; ?>" aria-expanded="true" aria-controls="<?php echo $details_id; ?>" class="calendar open et_smooth_scroll_disabled opl-link url summary has-image">
							<span>
								<svg style="fill:#ffffff" focusable="false" role="img" aria-labelledby="cat_1-3" class="category-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
									<!-- Font Awesome Free 5.15.3 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free (Icons: CC BY 4.0, Fonts: SIL OFL 1.1, Code: MIT License) -->
									<title id="cat_1-3">Category: General</title>
									<path d="M12 192h424c6.6 0 12 5.4 12 12v260c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V204c0-6.6 5.4-12 12-12zm436-44v-36c0-26.5-21.5-48-48-48h-48V12c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v52H160V12c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v52H48C21.5 64 0 85.5 0 112v36c0 6.6 5.4 12 12 12h424c6.6 0 12-5.4 12-12z"></path>
								</svg> <?php echo $start_time; ?>: <?php echo $event['title']; ?>
							</span>
						</a>
					</h3>

					<div id="<?php echo $details_id; ?>" class="details has-image single-details" aria-labelledby="<?php echo $title_id; ?>" style="">

						<button type="button" aria-controls="<?php echo $details_id; ?>" class="mc-toggle close" data-action="shiftforward">
							<span class="dashicons dashicons-dismiss" aria-hidden="true"></span><span class="screen-reader-text">Close</span>
						</button>
						<button type="button" aria-controls="<?php echo $details_id; ?>" class="mc-toggle close" data-action="shiftforward">
							<span class="dashicons dashicons-dismiss" aria-hidden="true"></span>
							<span class="screen-reader-text">Close</span>
						</button>

						<h4 class="mc-title"><?php echo $start_time; ?>: <?php echo $event['title']; ?></h4>
					
						<div class="time-block">
							<p>
								<span class="time-wrapper">
									<span class="event-time dtstart">
										<time class="value-title" datetime="<?php echo $start_datetime; ?>" title="<?php echo $start_datetime; ?>"><?php echo $start_time; ?></time>
									</span> 
									<span class="time-separator"> – </span> 
									<span class="end-time dtend"> 
										<time class="value-title" datetime="<?php echo $end_datetime; ?>" title="<?php echo $end_datetime; ?>"><?php echo $end_time; ?></time>
									</span>
								</span>
								<br />
								<span class="date-wrapper">
									<span class="mc-start-date dtstart" title="<?php echo $start_datetime; ?>" content="<?php echo $start_datetime; ?>"><?php echo $start_date; ?></span>  
								</span>
							</p>
						</div>

						<?php if ( ! empty( $event['flyer'] ) ) : ?>
							<img src="<?php echo $event['flyer']; ?>" class="mc-image photo wp-post-image" alt="<?php echo $event['title']; ?>">
						<?php endif; ?>

						<?php if ( ! empty( $event['description'] ) ) : ?>
							<div class="longdesc description"><?php echo $event['description']; ?></div>
						<?php endif; ?>

						<?php if ( ! empty( $event['location'] ) ) : ?>
							<div class="mc-location">
								<?php echo $event['location']; ?>
								<!-- <div class="address location vcard">
									<div class="adr">
										<div><strong class="org fn">Hi Sign Brewing</strong></div>
										<div class="sub-address">
											<div class="street-address">730 Shady Ln</div>
											<div><span class="locality">Austin</span><span class="mc-sep">, </span><span class="region">TX</span>  <span class="postal-code">78702</span></div>
											<div class="mc-events-link"><a class="location-link" href="https://www.subcontractorsaustintexas.com/asa-locations/hi-sign-brewing/">View Location</a></div>
										</div>
									</div>
									<div class="map"><a href="https://maps.google.com/maps?z=16&amp;daddr=730+Shady+Ln++Austin+TX+78702+" class="url external">Map<span class="screen-reader-text fn"> Hi Sign Brewing</span></a></div>
								</div> -->
							</div>
						<?php endif; ?>
						
						<div class="mc-registration">
							<?php if ( ! empty( $event['price'] ) ) echo $event['price']; ?>
							<br />
							<a class="external" href="<?php echo $event['url']; ?>" data-action="shiftback">Register Now</a>
						</div>

					</div><!--end .details-->

				</div>

			<?php

			$output .= ob_get_clean();

		endforeach;

		$output .= '</div>';

		echo $output;

	}
	
}
new LMG_ASA_Events_National();
endif;
