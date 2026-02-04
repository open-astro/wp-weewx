<?php
/**
 * Dashboard view template.
 *
 * @package WPWeeWX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$station_name    = WPWeeWX_Renderer::display_value( $data, 'station.location' );
$generation_raw  = WPWeeWX_Parser::get( $data, 'generation.time' );
$generation_date = '';
$generation_time = '';
if ( is_string( $generation_raw ) && false !== strpos( $generation_raw, 'T' ) ) {
	$parts = explode( 'T', $generation_raw, 2 );
	$generation_date = $parts[0];
	$generation_time = $parts[1];
} else {
	$generation_date = WPWeeWX_Renderer::display_value( $data, 'generation.time' );
}
$show_simple  = ( 'simple' === $source );
$station_link = WPWeeWX_Parser::get( $data, 'station.link' );
$link_html    = '—';
if ( ! empty( $station_link ) && is_scalar( $station_link ) ) {
	$parsed = wp_parse_url( (string) $station_link );
	$label  = isset( $parsed['host'] ) ? $parsed['host'] : (string) $station_link;
	$label  = preg_replace( '/^www\./', '', $label );
	$link_html = sprintf(
		'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
		esc_url( (string) $station_link ),
		esc_html( $label )
	);
}
$panel_allowed = function( $panel ) use ( $show_panels ) {
	if ( empty( $show_panels ) ) {
		return true;
	}
	return in_array( $panel, $show_panels, true );
};
$tabs_id = wp_unique_id( 'weewx-tabs-' );
$extreme_periods = array(
	'day'   => __( 'Day', 'wpweewx' ),
	'week'  => __( 'Week', 'wpweewx' ),
	'month' => __( 'Month', 'wpweewx' ),
	'year'  => __( 'Year', 'wpweewx' ),
);
?>

<div class="weewx-weather weewx-weather--<?php echo esc_attr( $theme ); ?>">
	<div class="weewx-weather__header">
		<div>
			<h2 class="weewx-weather__title"><?php echo esc_html( $station_name ); ?></h2>
			<div class="weewx-weather__meta">
				<span class="weewx-weather__meta-item">
					<?php echo esc_html( sprintf( __( 'Generated: %s', 'wpweewx' ), $generation_date ) ); ?>
				</span>
				<?php if ( $generation_time ) : ?>
					<span class="weewx-weather__meta-item"><?php echo esc_html( $generation_time ); ?></span>
				<?php endif; ?>
			</div>
		</div>
		<button class="weewx-weather__reload" type="button" onclick="window.location.reload()">
			<?php esc_html_e( 'Reload', 'wpweewx' ); ?>
		</button>
	</div>

	<div class="weewx-weather__tabs" data-tabs="<?php echo esc_attr( $tabs_id ); ?>">
		<?php if ( ! $show_simple && $panel_allowed( 'extremes' ) ) : ?>
			<div class="weewx-weather__tab-buttons" role="tablist">
				<?php $first_tab = true; ?>
				<?php foreach ( $extreme_periods as $period_key => $period_label ) : ?>
					<button
						type="button"
						class="weewx-weather__tab-button<?php echo $first_tab ? ' is-active' : ''; ?>"
						data-tab="<?php echo esc_attr( $tabs_id . '-' . $period_key ); ?>"
						role="tab"
						aria-selected="<?php echo $first_tab ? 'true' : 'false'; ?>"
					>
						<?php echo esc_html( $period_label ); ?>
					</button>
					<?php $first_tab = false; ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<div class="weewx-weather__columns">
			<div class="weewx-weather__column">
				<?php if ( $panel_allowed( 'station' ) ) : ?>
					<?php
					ob_start();
					WPWeeWX_Renderer::metric_row( __( 'Location', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'station.location' ) );
					WPWeeWX_Renderer::metric_row( __( 'Latitude', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'station.latitude' ) );
					WPWeeWX_Renderer::metric_row( __( 'Longitude', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'station.longitude' ) );
					WPWeeWX_Renderer::metric_row( __( 'Altitude (m)', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'station.altitude (meters)' ) );
					?>
					<div class="weewx-weather__metric">
						<span class="weewx-weather__metric-label"><?php esc_html_e( 'Link', 'wpweewx' ); ?></span>
						<span class="weewx-weather__metric-value"><?php echo wp_kses_post( $link_html ); ?></span>
					</div>
					<?php
					$station_html = ob_get_clean();
					WPWeeWX_Renderer::card( __( 'Station', 'wpweewx' ), $station_html );
					?>
				<?php endif; ?>
			</div>
			<div class="weewx-weather__column">
				<?php if ( $panel_allowed( 'current' ) ) : ?>
					<?php
					ob_start();
					WPWeeWX_Renderer::metric_row( __( 'Temperature', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.temperature' ) );
					WPWeeWX_Renderer::metric_row( __( 'Dew Point', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.dewpoint' ) );
					WPWeeWX_Renderer::metric_row( __( 'Humidity', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.humidity' ) );
					WPWeeWX_Renderer::metric_row( __( 'Heat Index', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.heat index' ) );
					WPWeeWX_Renderer::metric_row( __( 'Wind Chill', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.wind chill' ) );
					WPWeeWX_Renderer::metric_row( __( 'Barometer', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.barometer' ) );
					WPWeeWX_Renderer::metric_row( __( 'Rain Rate', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.rain rate' ) );
					WPWeeWX_Renderer::metric_row( __( 'Inside Temperature', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.inside temperature' ) );
					WPWeeWX_Renderer::metric_row( __( 'Inside Humidity', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.inside humidity' ) );
					$current_html = ob_get_clean();
					WPWeeWX_Renderer::card( __( 'Current Conditions', 'wpweewx' ), $current_html );
					?>
				<?php endif; ?>
			</div>
			<div class="weewx-weather__column">
				<?php if ( $panel_allowed( 'wind' ) ) : ?>
					<?php
					ob_start();
					WPWeeWX_Renderer::metric_row( __( 'Wind Speed', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.wind speed' ) );
					WPWeeWX_Renderer::metric_row( __( 'Wind Gust', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.wind gust' ) );
					WPWeeWX_Renderer::metric_row( __( 'Wind Direction', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.wind direction' ) );
					$wind_html = ob_get_clean();
					WPWeeWX_Renderer::card( __( 'Wind', 'wpweewx' ), $wind_html );
					?>
				<?php endif; ?>

				<?php if ( ! $show_simple && $panel_allowed( 'rain' ) ) : ?>
					<?php
					ob_start();
					WPWeeWX_Renderer::metric_row( __( 'Rain Rate', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.rain rate' ) );
					WPWeeWX_Renderer::metric_row( __( 'Day Total', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'day.rain total' ) );
					WPWeeWX_Renderer::metric_row( __( 'Week Total', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'week.rain total' ) );
					WPWeeWX_Renderer::metric_row( __( 'Month Total', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'month.rain total' ) );
					WPWeeWX_Renderer::metric_row( __( 'Year Total', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'year.rain total' ) );
					$rain_html = ob_get_clean();
					WPWeeWX_Renderer::card( __( 'Rain', 'wpweewx' ), $rain_html );
					?>
				<?php endif; ?>
			</div>
			<div class="weewx-weather__column">
				<?php if ( ! $show_simple && $panel_allowed( 'extremes' ) ) : ?>
					<?php $first_panel = true; ?>
					<?php foreach ( $extreme_periods as $period_key => $period_label ) : ?>
						<div
							class="weewx-weather__tab-panel<?php echo $first_panel ? ' is-active' : ''; ?>"
							data-tab-panel="<?php echo esc_attr( $tabs_id . '-' . $period_key ); ?>"
							role="tabpanel"
						>
							<?php
							ob_start();
							$prefix = $period_key . '.';
							WPWeeWX_Renderer::metric_row( __( 'Max Temperature', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, $prefix . 'max temperature' ) );
							WPWeeWX_Renderer::metric_row( __( 'Min Temperature', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, $prefix . 'min temperature' ) );
							WPWeeWX_Renderer::metric_row( __( 'Max Dewpoint', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, $prefix . 'max dewpoint' ) );
							WPWeeWX_Renderer::metric_row( __( 'Min Dewpoint', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, $prefix . 'min dewpoint' ) );
							WPWeeWX_Renderer::metric_row( __( 'Max Humidity', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, $prefix . 'max humidity' ) );
							WPWeeWX_Renderer::metric_row( __( 'Min Humidity', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, $prefix . 'min humidity' ) );
							WPWeeWX_Renderer::metric_row( __( 'Max Barometer', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, $prefix . 'max barometer' ) );
							WPWeeWX_Renderer::metric_row( __( 'Min Barometer', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, $prefix . 'min barometer' ) );
							WPWeeWX_Renderer::metric_row( __( 'Max Wind Speed', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, $prefix . 'max wind speed' ) );
							WPWeeWX_Renderer::metric_row( __( 'Max Wind Gust', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, $prefix . 'max wind gust' ) );
							WPWeeWX_Renderer::metric_row( __( 'Max Rain Rate', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, $prefix . 'max rain rate' ) );
							WPWeeWX_Renderer::metric_row( __( 'Rain Total', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, $prefix . 'rain total' ) );
							WPWeeWX_Renderer::metric_row( __( 'Max Inside Temp', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, $prefix . 'max inside temperature' ) );
							WPWeeWX_Renderer::metric_row( __( 'Min Inside Temp', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, $prefix . 'min inside temperature' ) );
							WPWeeWX_Renderer::metric_row( __( 'Max Inside Humidity', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, $prefix . 'max inside humidity' ) );
							WPWeeWX_Renderer::metric_row( __( 'Min Inside Humidity', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, $prefix . 'min inside humidity' ) );
							$extremes_html = ob_get_clean();
							WPWeeWX_Renderer::card( sprintf( __( '%s Extremes', 'wpweewx' ), $period_label ), $extremes_html );
							?>
						</div>
						<?php $first_panel = false; ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<script>
		(function () {
			var root = document.querySelector('[data-tabs="<?php echo esc_js( $tabs_id ); ?>"]');
			if (!root) {
				return;
			}
			var buttons = root.querySelectorAll('.weewx-weather__tab-button');
			var panels = root.querySelectorAll('.weewx-weather__tab-panel');
			buttons.forEach(function (button) {
				button.addEventListener('click', function () {
					var target = button.getAttribute('data-tab');
					buttons.forEach(function (btn) {
						btn.classList.toggle('is-active', btn === button);
						btn.setAttribute('aria-selected', btn === button ? 'true' : 'false');
					});
					panels.forEach(function (panel) {
						panel.classList.toggle('is-active', panel.getAttribute('data-tab-panel') === target);
					});
				});
			});
		})();
	</script>
</div>
