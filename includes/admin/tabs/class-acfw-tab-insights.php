<?php
/**
 * Insights tab: how customers use their account area.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACFW_Tab_Insights' ) ) {

	/**
	 * Page views, banner clicks and personal offers over a period.
	 */
	class ACFW_Tab_Insights extends ACFW_Admin_Tab {

		/**
		 * Periods offered, in days.
		 *
		 * @var int[]
		 */
		const PERIODS = array( 7, 30, 90 );

		/**
		 * No actions on this tab.
		 *
		 * @param string     $action Action slug.
		 * @param ACFW_Items $items  Menu items manager.
		 */
		public function handle( $action, $items ) {}

		/**
		 * The period picked in the address bar ( 30 days by default ).
		 *
		 * @return int
		 */
		protected function days() {
			$days = isset( $_GET['days'] ) ? absint( wp_unslash( $_GET['days'] ) ) : 30; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a read-only filter.
			return in_array( $days, self::PERIODS, true ) ? $days : 30;
		}

		/**
		 * Render the tab.
		 */
		public function render() {
			$days    = $this->days();
			$views   = ACFW_Stats::totals( 'view', $days );
			$daily   = ACFW_Stats::daily( 'view', $days );
			$shown   = ACFW_Stats::totals( 'banner_view', $days );
			$clicks  = ACFW_Stats::totals( 'banner_click', $days );
			$issued  = ACFW_Stats::totals( 'offer_issued', $days );
			$used    = ACFW_Stats::totals( 'offer_used', $days );
			$revenue = ACFW_Stats::totals( 'offer_revenue', $days );
			$base    = admin_url( 'admin.php?page=' . self::PAGE . '&tab=insights' );
			?>
			<div class="acfw-insights">
				<section class="acfw-card acfw-insights-head">
					<div>
						<h2><?php esc_html_e( 'Insights', 'my-account-dashboard-builder' ); ?></h2>
						<p><?php esc_html_e( 'How customers use their account area. Shop managers are not counted.', 'my-account-dashboard-builder' ); ?></p>
					</div>
					<nav class="acfw-segments acfw-insights-period" aria-label="<?php esc_attr_e( 'Period', 'my-account-dashboard-builder' ); ?>">
						<?php foreach ( self::PERIODS as $period ) : ?>
							<?php /* translators: %d: number of days. */ ?>
							<a class="acfw-segment-link<?php echo $period === $days ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'days', $period, $base ) ); ?>"<?php echo $period === $days ? ' aria-current="page"' : ''; ?>><?php echo esc_html( sprintf( _n( 'Last %d day', 'Last %d days', $period, 'my-account-dashboard-builder' ), $period ) ); ?></a>
						<?php endforeach; ?>
					</nav>
				</section>

				<?php if ( ! ACFW_Stats::enabled() ) : ?>
					<p class="acfw-card acfw-insights-off">
						<span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
						<span>
							<?php esc_html_e( 'Usage is not being recorded, so these numbers do not grow.', 'my-account-dashboard-builder' ); ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE . '&tab=general' ) ); ?>"><?php esc_html_e( 'Switch on “Record usage” in Settings.', 'my-account-dashboard-builder' ); ?></a>
						</span>
					</p>
				<?php endif; ?>

				<div class="acfw-insights-tiles">
					<?php
					$this->tile( __( 'Page views', 'my-account-dashboard-builder' ), number_format_i18n( array_sum( $views ) ) );
					$this->tile( __( 'Banner clicks', 'my-account-dashboard-builder' ), number_format_i18n( array_sum( $clicks ) ) );
					$this->tile( __( 'Offers used', 'my-account-dashboard-builder' ), number_format_i18n( array_sum( $used ) ) );
					$this->tile( __( 'Sales from offers', 'my-account-dashboard-builder' ), acfw_plain_price( array_sum( $revenue ) / 100 ) );
					?>
				</div>

				<section class="acfw-card acfw-insights-card">
					<h3><?php esc_html_e( 'Page views per day', 'my-account-dashboard-builder' ); ?></h3>
					<?php $this->chart( $daily ); ?>
				</section>

				<div class="acfw-insights-grid">
					<section class="acfw-card acfw-insights-card">
						<h3><?php esc_html_e( 'Account pages', 'my-account-dashboard-builder' ); ?></h3>
						<?php $this->pages_table( $views ); ?>
					</section>

					<section class="acfw-card acfw-insights-card">
						<h3><?php esc_html_e( 'Banners', 'my-account-dashboard-builder' ); ?></h3>
						<?php $this->banners_table( $shown, $clicks ); ?>

						<h3><?php esc_html_e( 'Personal offers', 'my-account-dashboard-builder' ); ?></h3>
						<?php $this->offers_table( $issued, $used, $revenue ); ?>
					</section>
				</div>
			</div>
			<?php
		}

		/**
		 * One summary tile.
		 *
		 * @param string $label Label.
		 * @param string $value Formatted value.
		 */
		protected function tile( $label, $value ) {
			?>
			<div class="acfw-card acfw-insights-tile">
				<span class="acfw-insights-tile-label"><?php echo esc_html( $label ); ?></span>
				<span class="acfw-insights-tile-value"><?php echo esc_html( $value ); ?></span>
			</div>
			<?php
		}

		/**
		 * Daily views as bars, drawn to one scale.
		 *
		 * @param array $daily Y-m-d => views, oldest first.
		 */
		protected function chart( $daily ) {
			$max = max( 1, (int) max( $daily ) );
			$n   = count( $daily );
			$w   = 640;
			$h   = 150;
			$gap = $n > 40 ? 1 : 3;
			$bw  = max( 1, ( $w - ( $n - 1 ) * $gap ) / $n );
			$i   = 0;
			$sum = array_sum( $daily );
			if ( ! $sum ) {
				echo '<p class="acfw-insights-empty">' . esc_html__( 'No page views in this period yet.', 'my-account-dashboard-builder' ) . '</p>';
				return;
			}
			$first = (string) array_key_first( $daily );
			$last  = (string) array_key_last( $daily );
			?>
			<figure class="acfw-insights-chart">
				<svg viewBox="0 0 <?php echo esc_attr( $w ); ?> <?php echo esc_attr( $h + 22 ); ?>" role="img" aria-label="<?php echo esc_attr( sprintf( /* translators: 1: total views, 2: busiest day's views. */ __( '%1$s page views; the busiest day had %2$s.', 'my-account-dashboard-builder' ), number_format_i18n( $sum ), number_format_i18n( $max ) ) ); ?>">
					<line x1="0" y1="<?php echo esc_attr( $h ); ?>" x2="<?php echo esc_attr( $w ); ?>" y2="<?php echo esc_attr( $h ); ?>" class="acfw-chart-axis" />
					<?php foreach ( $daily as $day => $value ) : ?>
						<?php
						$bh = $value ? max( 2, round( ( $value / $max ) * ( $h - 8 ) ) ) : 0;
						$x  = round( $i * ( $bw + $gap ), 2 );
						++$i;
						?>
						<rect class="acfw-chart-bar" x="<?php echo esc_attr( $x ); ?>" y="<?php echo esc_attr( $h - $bh ); ?>" width="<?php echo esc_attr( round( $bw, 2 ) ); ?>" height="<?php echo esc_attr( $bh ); ?>"><title><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $day . ' 12:00:00' ) ) . ': ' . number_format_i18n( $value ) ); ?></title></rect>
					<?php endforeach; ?>
					<text x="0" y="<?php echo esc_attr( $h + 16 ); ?>" class="acfw-chart-label"><?php echo esc_html( wp_date( 'j M', strtotime( $first . ' 12:00:00' ) ) ); ?></text>
					<text x="<?php echo esc_attr( $w ); ?>" y="<?php echo esc_attr( $h + 16 ); ?>" class="acfw-chart-label" text-anchor="end"><?php echo esc_html( wp_date( 'j M', strtotime( $last . ' 12:00:00' ) ) ); ?></text>
				</svg>
				<?php /* translators: %s: views on the busiest day. */ ?>
				<figcaption><?php echo esc_html( sprintf( __( 'Busiest day: %s views', 'my-account-dashboard-builder' ), number_format_i18n( $max ) ) ); ?></figcaption>
			</figure>
			<?php
		}

		/**
		 * Views per account page; pages in the menu nobody opened are flagged.
		 *
		 * @param array $views Endpoint key => views.
		 */
		protected function pages_table( $views ) {
			$labels = array();
			foreach ( acfw_flatten_items( ACFW()->items->get_items() ) as $key => $item ) {
				if ( 'endpoint' === ( $item['type'] ?? 'endpoint' ) && 'customer-logout' !== $key && ! empty( $item['active'] ) ) {
					$labels[ (string) $key ] = $item['label'];
				}
			}
			$labels += array( 'view-order' => __( 'Order details', 'my-account-dashboard-builder' ) );

			$rows = array();
			foreach ( $labels as $key => $label ) {
				if ( 'view-order' === $key && empty( $views[ $key ] ) ) {
					continue;
				}
				$rows[ $key ] = array( $label, (int) ( $views[ $key ] ?? 0 ) );
			}
			uasort(
				$rows,
				function ( $a, $b ) {
					return $b[1] - $a[1];
				}
			);
			$max = max( 1, (int) max( array_merge( array( 0 ), wp_list_pluck( $rows, 1 ) ) ) );
			?>
			<table class="acfw-insights-table">
				<thead><tr><th scope="col"><?php esc_html_e( 'Page', 'my-account-dashboard-builder' ); ?></th><th scope="col" class="num"><?php esc_html_e( 'Views', 'my-account-dashboard-builder' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( $rows as $key => $row ) : ?>
						<tr<?php echo $row[1] ? '' : ' class="is-unused"'; ?>>
							<td>
								<span class="acfw-insights-name"><?php echo esc_html( $row[0] ); ?></span>
								<?php if ( ! $row[1] ) : ?>
									<span class="acfw-insights-flag"><?php esc_html_e( 'Not opened', 'my-account-dashboard-builder' ); ?></span>
								<?php else : ?>
									<span class="acfw-insights-bar" aria-hidden="true"><i style="width:<?php echo esc_attr( round( 100 * $row[1] / $max, 1 ) ); ?>%"></i></span>
								<?php endif; ?>
							</td>
							<td class="num"><?php echo esc_html( number_format_i18n( $row[1] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		}

		/**
		 * Banner views, clicks and click rate.
		 *
		 * @param array $shown  Slug => views.
		 * @param array $clicks Slug => clicks.
		 */
		protected function banners_table( $shown, $clicks ) {
			$banners = ACFW_Banners::all();
			if ( ! $banners ) {
				echo '<p class="acfw-insights-empty">' . esc_html__( 'No banners yet.', 'my-account-dashboard-builder' ) . '</p>';
				return;
			}
			?>
			<table class="acfw-insights-table">
				<thead><tr><th scope="col"><?php esc_html_e( 'Banner', 'my-account-dashboard-builder' ); ?></th><th scope="col" class="num"><?php esc_html_e( 'Shown', 'my-account-dashboard-builder' ); ?></th><th scope="col" class="num"><?php esc_html_e( 'Clicks', 'my-account-dashboard-builder' ); ?></th><th scope="col" class="num"><?php esc_html_e( 'Click rate', 'my-account-dashboard-builder' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( $banners as $slug => $banner ) : ?>
						<?php
						$s = (int) ( $shown[ $slug ] ?? 0 );
						$c = (int) ( $clicks[ $slug ] ?? 0 );
						?>
						<tr>
							<td><?php echo esc_html( $banner['title'] ? $banner['title'] : $slug ); ?></td>
							<td class="num"><?php echo esc_html( number_format_i18n( $s ) ); ?></td>
							<td class="num"><?php echo esc_html( number_format_i18n( $c ) ); ?></td>
							<td class="num"><?php echo $s ? esc_html( number_format_i18n( 100 * $c / $s, 1 ) . '%' ) : '–'; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		}

		/**
		 * Offer banners: codes given out, codes used, sales from those orders.
		 *
		 * @param array $issued  Slug => codes issued.
		 * @param array $used    Slug => codes used.
		 * @param array $revenue Slug => order totals, in cents.
		 */
		protected function offers_table( $issued, $used, $revenue ) {
			$rows = array();
			foreach ( ACFW_Banners::all() as $slug => $banner ) {
				if ( 'yes' === ( $banner['offer'] ?? 'no' ) || isset( $issued[ $slug ] ) || isset( $used[ $slug ] ) ) {
					$rows[ $slug ] = $banner['title'] ? $banner['title'] : $slug;
				}
			}
			if ( ! $rows ) {
				echo '<p class="acfw-insights-empty">' . esc_html__( 'No personal offers yet. Switch one on in a banner’s Offer tab.', 'my-account-dashboard-builder' ) . '</p>';
				return;
			}
			?>
			<table class="acfw-insights-table">
				<thead><tr><th scope="col"><?php esc_html_e( 'Offer', 'my-account-dashboard-builder' ); ?></th><th scope="col" class="num"><?php esc_html_e( 'Codes given', 'my-account-dashboard-builder' ); ?></th><th scope="col" class="num"><?php esc_html_e( 'Used', 'my-account-dashboard-builder' ); ?></th><th scope="col" class="num"><?php esc_html_e( 'Sales', 'my-account-dashboard-builder' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( $rows as $slug => $title ) : ?>
						<?php
						$i = (int) ( $issued[ $slug ] ?? 0 );
						$u = (int) ( $used[ $slug ] ?? 0 );
						?>
						<tr>
							<td><?php echo esc_html( $title ); ?></td>
							<td class="num"><?php echo esc_html( number_format_i18n( $i ) ); ?></td>
							<td class="num"><?php echo esc_html( number_format_i18n( $u ) . ( $i ? ' (' . number_format_i18n( 100 * $u / $i, 0 ) . '%)' : '' ) ); ?></td>
							<td class="num"><?php echo esc_html( acfw_plain_price( ( $revenue[ $slug ] ?? 0 ) / 100 ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		}
	}
}
