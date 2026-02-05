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
$lcd_data = array();
if ( is_array( $lcd_payload ) && isset( $lcd_payload['data'] ) && is_array( $lcd_payload['data'] ) ) {
	$lcd_data = $lcd_payload['data'];
}
$lcd_latest = null;
$lcd_daily_rows = array();
$lcd_fields = array();
$lcd_weekly = array();
$lcd_monthly = array();
$lcd_yearly = array();
if ( isset( $lcd_data['lcd_datasheet']['daily_captures']['fields'], $lcd_data['lcd_datasheet']['daily_captures']['rows'] ) ) {
	$fields = $lcd_data['lcd_datasheet']['daily_captures']['fields'];
	$rows   = $lcd_data['lcd_datasheet']['daily_captures']['rows'];
	if ( is_array( $fields ) && is_array( $rows ) ) {
		$lcd_fields = $fields;
		$lcd_daily_rows = $rows;
	}
	if ( is_array( $fields ) && is_array( $rows ) && ! empty( $rows ) ) {
		$last_row = end( $rows );
		if ( is_array( $last_row ) ) {
			$lcd_latest = array();
			foreach ( $fields as $index => $field ) {
				$lcd_latest[ $field ] = isset( $last_row[ $index ] ) ? $last_row[ $index ] : null;
			}
		}
	}
}
if ( isset( $lcd_data['lcd_datasheet']['weekly_daily_summaries'] ) && is_array( $lcd_data['lcd_datasheet']['weekly_daily_summaries'] ) ) {
	$lcd_weekly = $lcd_data['lcd_datasheet']['weekly_daily_summaries'];
}
if ( isset( $lcd_data['lcd_datasheet']['monthly_daily_summaries'] ) && is_array( $lcd_data['lcd_datasheet']['monthly_daily_summaries'] ) ) {
	$lcd_monthly = $lcd_data['lcd_datasheet']['monthly_daily_summaries'];
}
if ( isset( $lcd_data['lcd_datasheet']['yearly_monthly_summaries'] ) && is_array( $lcd_data['lcd_datasheet']['yearly_monthly_summaries'] ) ) {
	$lcd_yearly = $lcd_data['lcd_datasheet']['yearly_monthly_summaries'];
}
$format_lcd = function( $value ) {
	if ( null === $value || '' === $value || ! is_numeric( $value ) ) {
		return '—';
	}
	return number_format_i18n( (float) $value, 2 );
};
$format_epoch = function( $value ) {
	if ( ! is_numeric( $value ) ) {
		return '—';
	}
	return wp_date(
		get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
		(int) $value,
		wp_timezone()
	);
};
$lcd_tabs_id = wp_unique_id( 'weewx-lcd-' );
$lcd_label_map = array(
	'timestamp_epoch' => __( 'Timestamp', 'wpweewx' ),
	'temperature'     => __( 'Temperature', 'wpweewx' ),
	'wind_direction'  => __( 'Wind Dir', 'wpweewx' ),
	'wind_speed'      => __( 'Wind Speed', 'wpweewx' ),
	'wind_gust'       => __( 'Wind Gust', 'wpweewx' ),
	'rain'            => __( 'Rain', 'wpweewx' ),
	'rain_rate'       => __( 'Rain Rate', 'wpweewx' ),
	'humidity'        => __( 'Humidity', 'wpweewx' ),
	'barometer'       => __( 'Barometer', 'wpweewx' ),
);
$lcd_field_index = array();
if ( ! empty( $lcd_fields ) ) {
	$lcd_field_index = array_flip( $lcd_fields );
}
$lcd_daily_recent = array_slice( $lcd_daily_rows, -24 );
$extract_daily_series = function( $field ) use ( $lcd_field_index, $lcd_daily_recent ) {
	if ( ! isset( $lcd_field_index[ $field ] ) ) {
		return array();
	}
	$idx = $lcd_field_index[ $field ];
	$series = array();
	foreach ( $lcd_daily_recent as $row ) {
		$series[] = isset( $row[ $idx ] ) ? $row[ $idx ] : null;
	}
	return $series;
};
$extract_summary_series = function( $items, $path ) {
	$series = array();
	if ( ! is_array( $items ) ) {
		return $series;
	}
	foreach ( $items as $item ) {
		$value = WPWeeWX_Parser::get( $item, $path );
		$series[] = is_numeric( $value ) ? (float) $value : null;
	}
	return $series;
};
$series_minmax = function( $series_list ) {
	$values = array();
	foreach ( $series_list as $series ) {
		if ( ! is_array( $series ) ) {
			continue;
		}
		foreach ( $series as $value ) {
			if ( is_numeric( $value ) ) {
				$values[] = (float) $value;
			}
		}
	}
	if ( empty( $values ) ) {
		return array( null, null );
	}
	return array( min( $values ), max( $values ) );
};
$format_value = function( $value ) {
	if ( ! is_numeric( $value ) ) {
		return '—';
	}
	return number_format_i18n( (float) $value, 2 );
};
$format_time_only = function( $value ) {
	if ( ! is_numeric( $value ) ) {
		return '—';
	}
	return wp_date( 'H:i', (int) $value, wp_timezone() );
};
$format_date_only = function( $value ) {
	if ( ! is_numeric( $value ) ) {
		return '—';
	}
	return wp_date( 'M j', (int) $value, wp_timezone() );
};
$daily_start = null;
$daily_end = null;
if ( ! empty( $lcd_daily_recent ) && isset( $lcd_field_index['timestamp_epoch'] ) ) {
	$ts_idx = $lcd_field_index['timestamp_epoch'];
	$first_row = $lcd_daily_recent[0];
	$last_row  = $lcd_daily_recent[ count( $lcd_daily_recent ) - 1 ];
	$daily_start = isset( $first_row[ $ts_idx ] ) ? $first_row[ $ts_idx ] : null;
	$daily_end   = isset( $last_row[ $ts_idx ] ) ? $last_row[ $ts_idx ] : null;
}
$summary_range = function( $items ) {
	if ( empty( $items ) || ! is_array( $items ) ) {
		return array( null, null );
	}
	$first = $items[0];
	$last  = $items[ count( $items ) - 1 ];
	return array(
		isset( $first['period_start_epoch'] ) ? $first['period_start_epoch'] : null,
		isset( $last['period_end_epoch'] ) ? $last['period_end_epoch'] : null,
	);
};
$render_chart = function( $title, $svg, $min, $max, $x_start, $x_end ) use ( $format_value ) {
	?>
	<div class="weewx-weather__chart-card">
		<div class="weewx-weather__chart-title"><?php echo esc_html( $title ); ?></div>
		<div class="weewx-weather__chart-body">
			<div class="weewx-weather__chart-axis">
				<span class="weewx-weather__chart-max"><?php echo esc_html( $format_value( $max ) ); ?></span>
				<span class="weewx-weather__chart-min"><?php echo esc_html( $format_value( $min ) ); ?></span>
			</div>
			<div class="weewx-weather__chart-plot">
				<?php echo $svg; ?>
			</div>
		</div>
		<div class="weewx-weather__chart-x">
			<span><?php echo esc_html( $x_start ); ?></span>
			<span><?php echo esc_html( $x_end ); ?></span>
		</div>
	</div>
	<?php
};
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

	<div class="weewx-weather__tabs" data-tabs="<?php echo esc_attr( 'lcd' === $source ? $lcd_tabs_id : $tabs_id ); ?>">
		<?php if ( 'lcd' === $source ) : ?>
			<div class="weewx-weather__tab-buttons" role="tablist" data-tabs="<?php echo esc_attr( $lcd_tabs_id ); ?>">
				<button type="button" class="weewx-weather__tab-button is-active" data-tab="<?php echo esc_attr( $lcd_tabs_id . '-latest' ); ?>" role="tab" aria-selected="true">
					<?php esc_html_e( 'Latest', 'wpweewx' ); ?>
				</button>
				<button type="button" class="weewx-weather__tab-button" data-tab="<?php echo esc_attr( $lcd_tabs_id . '-daily' ); ?>" role="tab" aria-selected="false">
					<?php esc_html_e( 'Daily Captures', 'wpweewx' ); ?>
				</button>
				<button type="button" class="weewx-weather__tab-button" data-tab="<?php echo esc_attr( $lcd_tabs_id . '-weekly' ); ?>" role="tab" aria-selected="false">
					<?php esc_html_e( 'Weekly', 'wpweewx' ); ?>
				</button>
				<button type="button" class="weewx-weather__tab-button" data-tab="<?php echo esc_attr( $lcd_tabs_id . '-monthly' ); ?>" role="tab" aria-selected="false">
					<?php esc_html_e( 'Monthly', 'wpweewx' ); ?>
				</button>
				<button type="button" class="weewx-weather__tab-button" data-tab="<?php echo esc_attr( $lcd_tabs_id . '-yearly' ); ?>" role="tab" aria-selected="false">
					<?php esc_html_e( 'Yearly', 'wpweewx' ); ?>
				</button>
			</div>
		<?php elseif ( ! $show_simple && $panel_allowed( 'extremes' ) ) : ?>
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
					$altitude_path = ( 'lcd' === $source ) ? 'station.altitude_meters' : 'station.altitude (meters)';
					WPWeeWX_Renderer::metric_row( __( 'Altitude (m)', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, $altitude_path ) );
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
				<?php if ( 'lcd' !== $source && $panel_allowed( 'current' ) ) : ?>
					<?php
					ob_start();
					if ( $show_simple ) {
						WPWeeWX_Renderer::metric_row( __( 'Temperature', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.temperature' ) );
						WPWeeWX_Renderer::metric_row( __( 'Humidity', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.humidity' ) );
						WPWeeWX_Renderer::metric_row( __( 'Barometer', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.barometer' ) );
						WPWeeWX_Renderer::metric_row( __( 'Rain Rate', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.rain rate' ) );
					} else {
						WPWeeWX_Renderer::metric_row( __( 'Temperature', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.temperature' ) );
						WPWeeWX_Renderer::metric_row( __( 'Dew Point', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.dewpoint' ) );
						WPWeeWX_Renderer::metric_row( __( 'Humidity', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.humidity' ) );
						WPWeeWX_Renderer::metric_row( __( 'Heat Index', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.heat index' ) );
						WPWeeWX_Renderer::metric_row( __( 'Wind Chill', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.wind chill' ) );
						WPWeeWX_Renderer::metric_row( __( 'Barometer', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.barometer' ) );
						WPWeeWX_Renderer::metric_row( __( 'Rain Rate', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.rain rate' ) );
						WPWeeWX_Renderer::metric_row( __( 'Inside Temperature', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.inside temperature' ) );
						WPWeeWX_Renderer::metric_row( __( 'Inside Humidity', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.inside humidity' ) );
					}
					$current_html = ob_get_clean();
					WPWeeWX_Renderer::card( __( 'Current Conditions', 'wpweewx' ), $current_html );
					?>
				<?php endif; ?>

			</div>
			<div class="weewx-weather__column">
				<?php if ( 'lcd' !== $source && $panel_allowed( 'wind' ) ) : ?>
					<?php
					ob_start();
					WPWeeWX_Renderer::metric_row( __( 'Wind Speed', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.wind speed' ) );
					WPWeeWX_Renderer::metric_row( __( 'Wind Gust', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.wind gust' ) );
					WPWeeWX_Renderer::metric_row( __( 'Wind Direction', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.wind direction' ) );
					$wind_html = ob_get_clean();
					WPWeeWX_Renderer::card( __( 'Wind', 'wpweewx' ), $wind_html );
					?>
				<?php endif; ?>

				<?php if ( 'lcd' !== $source && ! $show_simple && $panel_allowed( 'rain' ) ) : ?>
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
				<?php if ( 'lcd' !== $source && ! $show_simple && $panel_allowed( 'extremes' ) ) : ?>
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
		<?php if ( 'lcd' === $source && ( $lcd_latest || ! empty( $lcd_daily_rows ) || ! empty( $lcd_weekly ) || ! empty( $lcd_monthly ) || ! empty( $lcd_yearly ) ) ) : ?>
			<?php ob_start(); ?>
			<div class="weewx-weather__tab-panel is-active" data-tab-panel="<?php echo esc_attr( $lcd_tabs_id . '-latest' ); ?>" role="tabpanel">
						<?php
						if ( $lcd_latest ) {
							WPWeeWX_Renderer::metric_row( __( 'Timestamp', 'wpweewx' ), $format_epoch( $lcd_latest['timestamp_epoch'] ?? null ) );
							foreach ( $lcd_label_map as $field => $label ) {
								if ( 'timestamp_epoch' === $field ) {
									continue;
								}
								WPWeeWX_Renderer::metric_row( $label, $format_lcd( $lcd_latest[ $field ] ?? null ) );
							}
						} else {
							WPWeeWX_Renderer::metric_row( __( 'Status', 'wpweewx' ), __( 'No latest row available.', 'wpweewx' ) );
						}
						?>
					</div>

					<div class="weewx-weather__tab-panel" data-tab-panel="<?php echo esc_attr( $lcd_tabs_id . '-daily' ); ?>" role="tabpanel">
						<?php
						if ( ! empty( $lcd_daily_recent ) && ! empty( $lcd_fields ) ) :
							$temp_series = $extract_daily_series( 'temperature' );
							$humidity_series = $extract_daily_series( 'humidity' );
							$barometer_series = $extract_daily_series( 'barometer' );
							$wind_speed_series = $extract_daily_series( 'wind_speed' );
							$wind_gust_series = $extract_daily_series( 'wind_gust' );
							$rain_series = $extract_daily_series( 'rain' );
							$rain_rate_series = $extract_daily_series( 'rain_rate' );
							?>
							<div class="weewx-weather__chart-grid">
								<?php
								$time_start = $format_time_only( $daily_start );
								$time_end   = $format_time_only( $daily_end );

								list( $temp_min, $temp_max ) = $series_minmax( array( $temp_series ) );
								$temp_min = 25;
								$temp_svg = WPWeeWX_Renderer::sparkline(
									$temp_series,
									array(
										'height' => 70,
										'min'    => $temp_min,
										'max'    => $temp_max,
									)
								);
								$render_chart( __( 'Temperature', 'wpweewx' ), $temp_svg, $temp_min, $temp_max, $time_start, $time_end );

								$hum_svg = WPWeeWX_Renderer::sparkline( $humidity_series, array( 'height' => 70 ) );
								list( $hum_min, $hum_max ) = $series_minmax( array( $humidity_series ) );
								$render_chart( __( 'Humidity', 'wpweewx' ), $hum_svg, $hum_min, $hum_max, $time_start, $time_end );

								$baro_svg = WPWeeWX_Renderer::sparkline( $barometer_series, array( 'height' => 70 ) );
								list( $baro_min, $baro_max ) = $series_minmax( array( $barometer_series ) );
								$render_chart( __( 'Barometer', 'wpweewx' ), $baro_svg, $baro_min, $baro_max, $time_start, $time_end );

								$wind_svg = WPWeeWX_Renderer::sparkline(
									array(),
									array(
										'height' => 70,
										'series' => array(
											array( 'values' => $wind_speed_series, 'class' => 'primary' ),
											array( 'values' => $wind_gust_series, 'class' => 'secondary' ),
										),
									)
								);
								list( $wind_min, $wind_max ) = $series_minmax( array( $wind_speed_series, $wind_gust_series ) );
								$render_chart( __( 'Wind Speed / Gust', 'wpweewx' ), $wind_svg, $wind_min, $wind_max, $time_start, $time_end );

								$rain_svg = WPWeeWX_Renderer::sparkline(
									array(),
									array(
										'height' => 70,
										'series' => array(
											array( 'values' => $rain_series, 'class' => 'primary' ),
											array( 'values' => $rain_rate_series, 'class' => 'secondary' ),
										),
									)
								);
								list( $rain_min, $rain_max ) = $series_minmax( array( $rain_series, $rain_rate_series ) );
								$render_chart( __( 'Rain / Rain Rate', 'wpweewx' ), $rain_svg, $rain_min, $rain_max, $time_start, $time_end );
								?>
							</div>
						<?php else : ?>
							<?php esc_html_e( 'No daily capture rows available.', 'wpweewx' ); ?>
						<?php endif; ?>
					</div>

					<div class="weewx-weather__tab-panel" data-tab-panel="<?php echo esc_attr( $lcd_tabs_id . '-weekly' ); ?>" role="tabpanel">
						<?php if ( ! empty( $lcd_weekly ) ) : ?>
							<?php
							$temp_min = $extract_summary_series( $lcd_weekly, 'temperature.min' );
							$temp_max = $extract_summary_series( $lcd_weekly, 'temperature.max' );
							$temp_avg = $extract_summary_series( $lcd_weekly, 'temperature.avg' );
							$humidity_min = $extract_summary_series( $lcd_weekly, 'humidity.min' );
							$humidity_max = $extract_summary_series( $lcd_weekly, 'humidity.max' );
							$humidity_avg = $extract_summary_series( $lcd_weekly, 'humidity.avg' );
							$rain_total = $extract_summary_series( $lcd_weekly, 'rain.total' );
							?>
							<div class="weewx-weather__chart-grid">
								<?php
								list( $weekly_start, $weekly_end ) = $summary_range( $lcd_weekly );
								$weekly_start_label = $format_date_only( $weekly_start );
								$weekly_end_label   = $format_date_only( $weekly_end );

								list( $temp_min_val, $temp_max_val ) = $series_minmax( array( $temp_min, $temp_avg, $temp_max ) );
								$temp_min_val = 25;
								$temp_svg = WPWeeWX_Renderer::sparkline(
									array(),
									array(
										'height' => 70,
										'min'    => $temp_min_val,
										'max'    => $temp_max_val,
										'series' => array(
											array( 'values' => $temp_min, 'class' => 'secondary' ),
											array( 'values' => $temp_avg, 'class' => 'primary' ),
											array( 'values' => $temp_max, 'class' => 'tertiary' ),
										),
									)
								);
								$render_chart( __( 'Temperature (min/max/avg)', 'wpweewx' ), $temp_svg, $temp_min_val, $temp_max_val, $weekly_start_label, $weekly_end_label );

								$hum_svg = WPWeeWX_Renderer::sparkline(
									array(),
									array(
										'height' => 70,
										'series' => array(
											array( 'values' => $humidity_min, 'class' => 'secondary' ),
											array( 'values' => $humidity_avg, 'class' => 'primary' ),
											array( 'values' => $humidity_max, 'class' => 'tertiary' ),
										),
									)
								);
								list( $hum_min_val, $hum_max_val ) = $series_minmax( array( $humidity_min, $humidity_avg, $humidity_max ) );
								$render_chart( __( 'Humidity (min/max/avg)', 'wpweewx' ), $hum_svg, $hum_min_val, $hum_max_val, $weekly_start_label, $weekly_end_label );

								$rain_svg = WPWeeWX_Renderer::sparkline( $rain_total, array( 'height' => 70 ) );
								list( $rain_min_val, $rain_max_val ) = $series_minmax( array( $rain_total ) );
								$render_chart( __( 'Rain Total', 'wpweewx' ), $rain_svg, $rain_min_val, $rain_max_val, $weekly_start_label, $weekly_end_label );
								?>
							</div>
						<?php else : ?>
							<?php esc_html_e( 'No weekly summaries available.', 'wpweewx' ); ?>
						<?php endif; ?>
					</div>

					<div class="weewx-weather__tab-panel" data-tab-panel="<?php echo esc_attr( $lcd_tabs_id . '-monthly' ); ?>" role="tabpanel">
						<?php if ( ! empty( $lcd_monthly ) ) : ?>
							<?php
							$temp_min = $extract_summary_series( $lcd_monthly, 'temperature.min' );
							$temp_max = $extract_summary_series( $lcd_monthly, 'temperature.max' );
							$temp_avg = $extract_summary_series( $lcd_monthly, 'temperature.avg' );
							$humidity_min = $extract_summary_series( $lcd_monthly, 'humidity.min' );
							$humidity_max = $extract_summary_series( $lcd_monthly, 'humidity.max' );
							$humidity_avg = $extract_summary_series( $lcd_monthly, 'humidity.avg' );
							$rain_total = $extract_summary_series( $lcd_monthly, 'rain.total' );
							?>
							<div class="weewx-weather__chart-grid">
								<?php
								list( $monthly_start, $monthly_end ) = $summary_range( $lcd_monthly );
								$monthly_start_label = $format_date_only( $monthly_start );
								$monthly_end_label   = $format_date_only( $monthly_end );

								list( $temp_min_val, $temp_max_val ) = $series_minmax( array( $temp_min, $temp_avg, $temp_max ) );
								$temp_min_val = 25;
								$temp_svg = WPWeeWX_Renderer::sparkline(
									array(),
									array(
										'height' => 70,
										'min'    => $temp_min_val,
										'max'    => $temp_max_val,
										'series' => array(
											array( 'values' => $temp_min, 'class' => 'secondary' ),
											array( 'values' => $temp_avg, 'class' => 'primary' ),
											array( 'values' => $temp_max, 'class' => 'tertiary' ),
										),
									)
								);
								$render_chart( __( 'Temperature (min/max/avg)', 'wpweewx' ), $temp_svg, $temp_min_val, $temp_max_val, $monthly_start_label, $monthly_end_label );

								$hum_svg = WPWeeWX_Renderer::sparkline(
									array(),
									array(
										'height' => 70,
										'series' => array(
											array( 'values' => $humidity_min, 'class' => 'secondary' ),
											array( 'values' => $humidity_avg, 'class' => 'primary' ),
											array( 'values' => $humidity_max, 'class' => 'tertiary' ),
										),
									)
								);
								list( $hum_min_val, $hum_max_val ) = $series_minmax( array( $humidity_min, $humidity_avg, $humidity_max ) );
								$render_chart( __( 'Humidity (min/max/avg)', 'wpweewx' ), $hum_svg, $hum_min_val, $hum_max_val, $monthly_start_label, $monthly_end_label );

								$rain_svg = WPWeeWX_Renderer::sparkline( $rain_total, array( 'height' => 70 ) );
								list( $rain_min_val, $rain_max_val ) = $series_minmax( array( $rain_total ) );
								$render_chart( __( 'Rain Total', 'wpweewx' ), $rain_svg, $rain_min_val, $rain_max_val, $monthly_start_label, $monthly_end_label );
								?>
							</div>
						<?php else : ?>
							<?php esc_html_e( 'No monthly summaries available.', 'wpweewx' ); ?>
						<?php endif; ?>
					</div>

					<div class="weewx-weather__tab-panel" data-tab-panel="<?php echo esc_attr( $lcd_tabs_id . '-yearly' ); ?>" role="tabpanel">
						<?php if ( ! empty( $lcd_yearly ) ) : ?>
							<?php
							$temp_min = $extract_summary_series( $lcd_yearly, 'temperature.min' );
							$temp_max = $extract_summary_series( $lcd_yearly, 'temperature.max' );
							$temp_avg = $extract_summary_series( $lcd_yearly, 'temperature.avg' );
							$humidity_min = $extract_summary_series( $lcd_yearly, 'humidity.min' );
							$humidity_max = $extract_summary_series( $lcd_yearly, 'humidity.max' );
							$humidity_avg = $extract_summary_series( $lcd_yearly, 'humidity.avg' );
							$rain_total = $extract_summary_series( $lcd_yearly, 'rain.total' );
							?>
							<div class="weewx-weather__chart-grid">
								<?php
								list( $yearly_start, $yearly_end ) = $summary_range( $lcd_yearly );
								$yearly_start_label = $format_date_only( $yearly_start );
								$yearly_end_label   = $format_date_only( $yearly_end );

								list( $temp_min_val, $temp_max_val ) = $series_minmax( array( $temp_min, $temp_avg, $temp_max ) );
								$temp_min_val = 25;
								$temp_svg = WPWeeWX_Renderer::sparkline(
									array(),
									array(
										'height' => 70,
										'min'    => $temp_min_val,
										'max'    => $temp_max_val,
										'series' => array(
											array( 'values' => $temp_min, 'class' => 'secondary' ),
											array( 'values' => $temp_avg, 'class' => 'primary' ),
											array( 'values' => $temp_max, 'class' => 'tertiary' ),
										),
									)
								);
								$render_chart( __( 'Temperature (min/max/avg)', 'wpweewx' ), $temp_svg, $temp_min_val, $temp_max_val, $yearly_start_label, $yearly_end_label );

								$hum_svg = WPWeeWX_Renderer::sparkline(
									array(),
									array(
										'height' => 70,
										'series' => array(
											array( 'values' => $humidity_min, 'class' => 'secondary' ),
											array( 'values' => $humidity_avg, 'class' => 'primary' ),
											array( 'values' => $humidity_max, 'class' => 'tertiary' ),
										),
									)
								);
								list( $hum_min_val, $hum_max_val ) = $series_minmax( array( $humidity_min, $humidity_avg, $humidity_max ) );
								$render_chart( __( 'Humidity (min/max/avg)', 'wpweewx' ), $hum_svg, $hum_min_val, $hum_max_val, $yearly_start_label, $yearly_end_label );

								$rain_svg = WPWeeWX_Renderer::sparkline( $rain_total, array( 'height' => 70 ) );
								list( $rain_min_val, $rain_max_val ) = $series_minmax( array( $rain_total ) );
								$render_chart( __( 'Rain Total', 'wpweewx' ), $rain_svg, $rain_min_val, $rain_max_val, $yearly_start_label, $yearly_end_label );
								?>
							</div>
						<?php else : ?>
							<?php esc_html_e( 'No yearly summaries available.', 'wpweewx' ); ?>
						<?php endif; ?>
					</div>
					<?php
					$lcd_html = ob_get_clean();
					?>
			<div class="weewx-weather__full-span">
				<?php WPWeeWX_Renderer::card( __( 'LCD Datasheet', 'wpweewx' ), $lcd_html ); ?>
			</div>
					<script>
						(function () {
							var root = document.querySelector('[data-tabs="<?php echo esc_js( $lcd_tabs_id ); ?>"]');
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
			<?php endif; ?>
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
