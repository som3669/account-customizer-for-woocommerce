<?php
/**
 * Usage numbers for the Insights tab: account page views, banner views and
 * clicks, personal offers issued and used.
 *
 * Daily counters in one small table ( day, kind, ref ), written with a single
 * INSERT … ON DUPLICATE KEY UPDATE, so counting costs one query and never
 * races the way a read-modify-write option did. Counts only, no personal data.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Stats' ) ) {

	/**
	 * Daily usage counters.
	 */
	class ACFW_Stats {

		/**
		 * Schema version of the table.
		 *
		 * @var int
		 */
		const DB_VERSION = 1;

		/**
		 * Option holding the installed schema version.
		 *
		 * @var string
		 */
		const DB_OPTION = 'acfw_db_version';

		/**
		 * Nonce action for the click beacon.
		 *
		 * @var string
		 */
		const NONCE = 'acfw_track';

		/**
		 * Kinds a browser may report ( everything else is counted on the server ).
		 *
		 * @var string[]
		 */
		const BEACON_KINDS = array( 'banner_click' );

		/**
		 * Hook the install check and the click beacon.
		 */
		public function __construct() {
			add_action( 'init', array( __CLASS__, 'maybe_install' ), 5 );
			add_action( 'wp_ajax_acfw_track', array( $this, 'ajax_track' ) );
		}

		/**
		 * The table name.
		 *
		 * @return string
		 */
		public static function table() {
			global $wpdb;
			return $wpdb->prefix . 'acfw_stats';
		}

		/**
		 * Create or update the table when the schema version moved.
		 */
		public static function maybe_install() {
			if ( (int) get_option( self::DB_OPTION, 0 ) >= self::DB_VERSION ) {
				return;
			}
			self::install();
		}

		/**
		 * Create the table.
		 */
		public static function install() {
			global $wpdb;
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$charset = $wpdb->get_charset_collate();
			dbDelta(
				'CREATE TABLE ' . self::table() . " (
				day date NOT NULL,
				kind varchar(20) NOT NULL,
				ref varchar(191) NOT NULL,
				hits bigint(20) unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY  (day,kind,ref),
				KEY kind_day (kind,day)
				) $charset;"
			);
			update_option( self::DB_OPTION, self::DB_VERSION );
		}

		/**
		 * Is counting switched on ( Settings → Record usage )?
		 *
		 * @return bool
		 */
		public static function enabled() {
			return 'yes' === get_option( 'acfw_track_views', 'no' );
		}

		/**
		 * Should the current visitor be counted?
		 *
		 * Customers only: shop managers preview every page and banner, and would
		 * skew the numbers; a preview as a customer is not their visit either.
		 *
		 * @return bool
		 */
		public static function counts_visitor() {
			return self::enabled() && is_user_logged_in() && ! current_user_can( 'manage_woocommerce' ) && ! ( class_exists( 'ACFW_View_As' ) && ACFW_View_As::active() );
		}

		/**
		 * Add to today's counter.
		 *
		 * @param string $kind Counter kind ( view, banner_view, banner_click, offer_issued, offer_used, offer_revenue ).
		 * @param string $ref  What was counted ( an item key, a banner slug ).
		 * @param int    $by   Amount ( revenue is counted in cents ).
		 */
		public static function bump( $kind, $ref, $by = 1 ) {
			global $wpdb;
			$by   = (int) $by;
			$kind = substr( sanitize_key( $kind ), 0, 20 );
			$ref  = substr( (string) $ref, 0, 191 );
			if ( $by <= 0 || '' === $kind || '' === $ref ) {
				return;
			}
			$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare(
					"INSERT INTO {$wpdb->prefix}acfw_stats ( day, kind, ref, hits ) VALUES ( %s, %s, %s, %d ) ON DUPLICATE KEY UPDATE hits = hits + VALUES( hits )",
					current_time( 'Y-m-d' ),
					$kind,
					$ref,
					$by
				)
			);
		}

		/**
		 * The first day of a window of $days days ending today.
		 *
		 * @param int $days Days.
		 * @return string Y-m-d.
		 */
		public static function since( $days ) {
			$days = max( 1, (int) $days );
			return gmdate( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . ' -' . ( $days - 1 ) . ' days' ) );
		}

		/**
		 * Totals per ref over the last $days days.
		 *
		 * @param string $kind Counter kind.
		 * @param int    $days Days.
		 * @return array ref => total
		 */
		public static function totals( $kind, $days ) {
			global $wpdb;
			$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare(
					"SELECT ref, SUM( hits ) AS total FROM {$wpdb->prefix}acfw_stats WHERE kind = %s AND day >= %s GROUP BY ref",
					$kind,
					self::since( $days )
				),
				ARRAY_A
			);
			$out  = array();
			foreach ( (array) $rows as $row ) {
				$out[ (string) $row['ref'] ] = (int) $row['total'];
			}
			return $out;
		}

		/**
		 * Daily totals of one kind over the last $days days, every day present.
		 *
		 * @param string $kind Counter kind.
		 * @param int    $days Days.
		 * @return array Y-m-d => total, oldest first.
		 */
		public static function daily( $kind, $days ) {
			global $wpdb;
			$since  = self::since( $days );
			$rows   = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare(
					"SELECT day, SUM( hits ) AS total FROM {$wpdb->prefix}acfw_stats WHERE kind = %s AND day >= %s GROUP BY day",
					$kind,
					$since
				),
				ARRAY_A
			);
			$by_day = array();
			foreach ( (array) $rows as $row ) {
				$by_day[ (string) $row['day'] ] = (int) $row['total'];
			}
			$out   = array();
			$count = max( 1, (int) $days );
			for ( $i = 0; $i < $count; $i++ ) {
				$day         = gmdate( 'Y-m-d', strtotime( $since . ' +' . $i . ' days' ) );
				$out[ $day ] = $by_day[ $day ] ?? 0;
			}
			return $out;
		}

		/**
		 * AJAX: a click the browser reports ( banner links ).
		 */
		public function ajax_track() {
			check_ajax_referer( self::NONCE, 'nonce' );
			$kind = isset( $_POST['kind'] ) ? sanitize_key( wp_unslash( $_POST['kind'] ) ) : '';
			$ref  = isset( $_POST['ref'] ) ? acfw_sanitize_key( sanitize_title( wp_unslash( $_POST['ref'] ) ) ) : '';
			if ( in_array( $kind, self::BEACON_KINDS, true ) && '' !== $ref && self::counts_visitor() ) {
				// Only banners that exist, so the table cannot be filled with junk.
				if ( 'banner_click' !== $kind || null !== ACFW_Banners::get( $ref ) ) {
					self::bump( $kind, $ref );
				}
			}
			wp_send_json_success();
		}
	}
}
