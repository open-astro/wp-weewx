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
$temp_unit = WPWeeWX_Settings::get_temp_unit();
$convert_temp_value = function( $value ) use ( $temp_unit ) {
	if ( ! is_numeric( $value ) ) {
		return $value;
	}
	$value = (float) $value;
	if ( 'c' === $temp_unit ) {
		return ( $value - 32 ) * 5 / 9;
	}
	return $value;
};
$convert_temp_series = function( $series ) use ( $convert_temp_value ) {
	if ( ! is_array( $series ) ) {
		return $series;
	}
	return array_map(
		function( $value ) use ( $convert_temp_value ) {
			return is_numeric( $value ) ? $convert_temp_value( $value ) : $value;
		},
		$series
	);
};
$is_temp_field = function( $field ) {
	$field = strtolower( (string) $field );
	return false !== strpos( $field, 'temp' ) ||
		false !== strpos( $field, 'dew' ) ||
		false !== strpos( $field, 'wind_chill' ) ||
		false !== strpos( $field, 'wind chill' ) ||
		false !== strpos( $field, 'heat index' );
};
$format_lcd = function( $value, $field = '' ) use ( $convert_temp_value, $is_temp_field, $temp_unit ) {
	if ( null === $value || '' === $value || ! is_numeric( $value ) ) {
		return '—';
	}
	$val = (float) $value;
	if ( $is_temp_field( $field ) && 'c' === $temp_unit ) {
		$val = $convert_temp_value( $val );
	}
	$suffix = $is_temp_field( $field ) ? ' ' . strtoupper( $temp_unit ) : '';
	return number_format_i18n( $val, 2 ) . $suffix;
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
	'dewpoint'        => __( 'Dew Point', 'wpweewx' ),
	'dew_point'       => __( 'Dew Point', 'wpweewx' ),
	'wind_direction'  => __( 'Wind Dir', 'wpweewx' ),
	'wind_speed'      => __( 'Wind Speed', 'wpweewx' ),
	'wind_gust'       => __( 'Wind Gust', 'wpweewx' ),
	'rain'            => __( 'Rain', 'wpweewx' ),
	'rain_rate'       => __( 'Rain Rate', 'wpweewx' ),
	'humidity'        => __( 'Humidity', 'wpweewx' ),
	'wind_chill'      => __( 'Wind Chill', 'wpweewx' ),
	'barometer'       => __( 'Barometer', 'wpweewx' ),
);
$lcd_field_label = function( $field ) use ( $lcd_label_map ) {
	if ( isset( $lcd_label_map[ $field ] ) ) {
		return $lcd_label_map[ $field ];
	}
	return implode( ' ', array_map( 'ucfirst', explode( '_', $field ) ) );
};
$lcd_field_index = array();
if ( ! empty( $lcd_fields ) ) {
	$lcd_field_index = array_flip( $lcd_fields );
}
$lcd_latest_fields = $lcd_fields;
if ( empty( $lcd_latest_fields ) && is_array( $lcd_latest ) ) {
	$lcd_latest_fields = array_keys( $lcd_latest );
	if ( ! empty( $lcd_latest_fields ) ) {
		$lcd_latest_fields = array_values(
			array_merge(
				array( 'timestamp_epoch' ),
				array_diff( $lcd_latest_fields, array( 'timestamp_epoch' ) )
			)
		);
	}
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
$series_has_numeric = function( $series ) {
	if ( ! is_array( $series ) ) {
		return false;
	}
	foreach ( $series as $value ) {
		if ( is_numeric( $value ) ) {
			return true;
		}
	}
	return false;
};
$extract_summary_series_fallback = function( $items, $paths ) use ( $extract_summary_series, $series_has_numeric ) {
	if ( ! is_array( $paths ) ) {
		return $extract_summary_series( $items, (string) $paths );
	}
	foreach ( $paths as $path ) {
		$series = $extract_summary_series( $items, $path );
		if ( $series_has_numeric( $series ) ) {
			return $series;
		}
	}
	return $extract_summary_series( $items, (string) $paths[0] );
};
$build_labels_from_epochs = function( $epochs, $format_callback ) {
	if ( ! is_array( $epochs ) || empty( $epochs ) ) {
		return array();
	}
	$labels = array();
	foreach ( $epochs as $epoch ) {
		if ( is_numeric( $epoch ) ) {
			$labels[] = call_user_func( $format_callback, $epoch );
		} else {
			$labels[] = '';
		}
	}
	return $labels;
};
$bin_wind_dir = function( $series ) {
	$labels = array( 'N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW' );
	$bins = array_fill( 0, 8, 0 );
	if ( ! is_array( $series ) ) {
		return array( $labels, $bins );
	}
	foreach ( $series as $value ) {
		if ( ! is_numeric( $value ) ) {
			continue;
		}
		$deg = fmod( (float) $value, 360.0 );
		if ( $deg < 0 ) {
			$deg += 360.0;
		}
		$idx = (int) floor( ( $deg + 22.5 ) / 45.0 ) % 8;
		$bins[ $idx ]++;
	}
	return array( $labels, $bins );
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
$render_chart = function( $title, $series_list, $x_start, $x_end, $axis_ticks = null, $chart_options = array() ) use ( $format_value, $series_has_numeric, $series_minmax ) {
	$chart_type = isset( $chart_options['type'] ) ? $chart_options['type'] : 'line';
	$series_list = is_array( $series_list ) ? $series_list : array();
	$filtered_series = array();
	foreach ( $series_list as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$values = isset( $item['values'] ) && is_array( $item['values'] ) ? $item['values'] : array();
		if ( ! $series_has_numeric( $values ) ) {
			continue;
		}
		$filtered_series[] = array(
			'values' => $values,
			'class'  => isset( $item['class'] ) ? $item['class'] : 'primary',
			'type'   => isset( $item['type'] ) ? $item['type'] : null,
			'label'  => isset( $item['label'] ) ? $item['label'] : '',
		);
	}

	$min = null;
	$max = null;
	if ( ! empty( $filtered_series ) ) {
		$values_list = array();
		foreach ( $filtered_series as $item ) {
			$values_list[] = $item['values'];
		}
		list( $min, $max ) = $series_minmax( $values_list );
	}

	$axis_labels = null;
	if ( is_array( $axis_ticks ) && ! empty( $axis_ticks ) ) {
		$axis_labels = $axis_ticks;
	} else {
		$axis_labels = array( $max, $min );
	}

	$payload = null;
	if ( ! empty( $filtered_series ) ) {
		$max_len = 0;
		foreach ( $filtered_series as $item ) {
			$max_len = max( $max_len, count( $item['values'] ) );
		}
		$labels = array();
		if ( isset( $chart_options['labels'] ) && is_array( $chart_options['labels'] ) ) {
			$labels = $chart_options['labels'];
		} elseif ( $max_len > 0 ) {
			$labels = array_fill( 0, $max_len, '' );
		}
		$datasets = array();
		$legend_items = array();
		foreach ( $filtered_series as $item ) {
			$datasets[] = array(
				'data'  => $item['values'],
				'class' => $item['class'],
				'type'  => $item['type'],
				'label' => $item['label'],
			);
			if ( ! empty( $item['label'] ) ) {
				$legend_items[] = array(
					'label' => $item['label'],
					'class' => $item['class'],
				);
			}
		}
		$payload = array(
			'labels'   => $labels,
			'datasets' => $datasets,
			'type'     => isset( $chart_options['type'] ) ? $chart_options['type'] : 'line',
		);
		if ( isset( $chart_options['y_min'] ) && is_numeric( $chart_options['y_min'] ) ) {
			$payload['yMin'] = (float) $chart_options['y_min'];
		}
		if ( isset( $chart_options['y_max'] ) && is_numeric( $chart_options['y_max'] ) ) {
			$payload['yMax'] = (float) $chart_options['y_max'];
		}
	}
	?>
	<div class="weewx-weather__chart-card<?php echo 'radar' === $chart_type ? ' weewx-weather__chart-card--radar' : ''; ?>">
		<div class="weewx-weather__chart-title"><?php echo esc_html( $title ); ?></div>
		<?php if ( ! empty( $legend_items ) ) : ?>
			<div class="weewx-weather__chart-legend" role="presentation">
				<?php foreach ( $legend_items as $legend ) : ?>
					<span class="weewx-weather__chart-legend-item">
						<span class="weewx-weather__chart-legend-swatch weewx-weather__chart-legend-swatch--<?php echo esc_attr( $legend['class'] ); ?>"></span>
						<span class="weewx-weather__chart-legend-label"><?php echo esc_html( $legend['label'] ); ?></span>
					</span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<div class="weewx-weather__chart-body">
			<div class="weewx-weather__chart-axis">
				<?php foreach ( $axis_labels as $tick ) : ?>
					<span><?php echo esc_html( $format_value( $tick ) ); ?></span>
				<?php endforeach; ?>
			</div>
			<div class="weewx-weather__chart-plot">
				<?php if ( $payload ) : ?>
					<canvas class="weewx-weather__chart-canvas" aria-hidden="true"></canvas>
					<script type="application/json" class="weewx-weather__chart-data"><?php echo wp_json_encode( $payload ); ?></script>
				<?php else : ?>
					<div class="weewx-weather__chart-empty"><?php esc_html_e( 'No data', 'wpweewx' ); ?></div>
				<?php endif; ?>
			</div>
		</div>
		<div class="weewx-weather__chart-x">
			<span><?php echo esc_html( $x_start ); ?></span>
			<span><?php echo esc_html( $x_end ); ?></span>
		</div>
	</div>
	<?php
};
$lcd_current = array();
if ( isset( $lcd_data['lcd_datasheet']['current'] ) && is_array( $lcd_data['lcd_datasheet']['current'] ) ) {
	$lcd_current = $lcd_data['lcd_datasheet']['current'];
}
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
		<div class="weewx-weather__header-actions">
			<div class="weewx-weather__unit-toggle" data-unit-toggle>
				<button type="button" class="weewx-weather__unit-button <?php echo 'f' === $temp_unit ? 'is-active' : ''; ?>" data-unit="f">F</button>
				<button type="button" class="weewx-weather__unit-button <?php echo 'c' === $temp_unit ? 'is-active' : ''; ?>" data-unit="c">C</button>
			</div>
			<button class="weewx-weather__reload" type="button" onclick="window.location.reload()">
				<?php esc_html_e( 'Reload', 'wpweewx' ); ?>
			</button>
		</div>
	</div>

	<div class="weewx-weather__tabs" data-tabs="<?php echo esc_attr( ( $lcd_latest || ! empty( $lcd_daily_rows ) || ! empty( $lcd_weekly ) || ! empty( $lcd_monthly ) || ! empty( $lcd_yearly ) ) && 'lcd' === $source ? $lcd_tabs_id : $tabs_id ); ?>">
		<?php if ( 'lcd' === $source && ( $lcd_latest || ! empty( $lcd_daily_rows ) || ! empty( $lcd_weekly ) || ! empty( $lcd_monthly ) || ! empty( $lcd_yearly ) ) ) : ?>
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
					$altitude_display = ( 'lcd' === $source )
						? WPWeeWX_Renderer::display_value( $data, 'station.altitude_meters' )
						: WPWeeWX_Renderer::display_value( $data, 'station.altitude (meters)' );
					if ( 'lcd' === $source && '—' === $altitude_display ) {
						$altitude_display = WPWeeWX_Renderer::display_value( $data, 'station.altitude (meters)' );
					}
					WPWeeWX_Renderer::metric_row( __( 'Altitude (m)', 'wpweewx' ), $altitude_display );
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
				<?php if ( 'lcd' === $source ) : ?>
					<?php
					$extra_label_1 = WPWeeWX_Settings::get( 'wpweewx_lcd_extra_temp1_label' );
					$extra_label_2 = WPWeeWX_Settings::get( 'wpweewx_lcd_extra_temp2_label' );
					$extra_label_3 = WPWeeWX_Settings::get( 'wpweewx_lcd_extra_temp3_label' );
					ob_start();
					WPWeeWX_Renderer::metric_row( $extra_label_1, WPWeeWX_Renderer::display_value( $lcd_current, 'extraTemp1' ) );
					WPWeeWX_Renderer::metric_row( $extra_label_2, WPWeeWX_Renderer::display_value( $lcd_current, 'extraTemp2' ) );
					WPWeeWX_Renderer::metric_row( $extra_label_3, WPWeeWX_Renderer::display_value( $lcd_current, 'extraTemp3' ) );
					$buildings_html = ob_get_clean();
					WPWeeWX_Renderer::card( __( 'Buildings', 'wpweewx' ), $buildings_html );
					?>
				<?php endif; ?>

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
		<?php if ( $lcd_latest || ! empty( $lcd_daily_rows ) || ! empty( $lcd_weekly ) || ! empty( $lcd_monthly ) || ! empty( $lcd_yearly ) ) : ?>
			<?php if ( 'lcd' !== $source ) : ?>
				<div class="weewx-weather__tabs weewx-weather__tabs--lcd" data-tabs="<?php echo esc_attr( $lcd_tabs_id ); ?>">
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
				</div>
			<?php endif; ?>
			<?php ob_start(); ?>
			<div class="weewx-weather__tab-panel is-active" data-tab-panel="<?php echo esc_attr( $lcd_tabs_id . '-latest' ); ?>" role="tabpanel">
						<?php
						if ( $lcd_latest ) {
							WPWeeWX_Renderer::metric_row( __( 'Timestamp', 'wpweewx' ), $format_epoch( $lcd_latest['timestamp_epoch'] ?? null ) );
							foreach ( $lcd_latest_fields as $field ) {
								if ( 'timestamp_epoch' === $field ) {
									continue;
								}
								WPWeeWX_Renderer::metric_row( $lcd_field_label( $field ), $format_lcd( $lcd_latest[ $field ] ?? null, $field ) );
							}
						} else {
							WPWeeWX_Renderer::metric_row( __( 'Status', 'wpweewx' ), __( 'No latest row available.', 'wpweewx' ) );
						}
						?>
					</div>

					<div class="weewx-weather__tab-panel" data-tab-panel="<?php echo esc_attr( $lcd_tabs_id . '-daily' ); ?>" role="tabpanel">
						<?php
						if ( ! empty( $lcd_daily_recent ) && ! empty( $lcd_fields ) ) :
							$temp_series = $convert_temp_series( $extract_daily_series( 'temperature' ) );
							$dewpoint_series = $convert_temp_series( $extract_daily_series( 'dewpoint' ) );
							if ( ! $series_has_numeric( $dewpoint_series ) && isset( $lcd_field_index['dew_point'] ) ) {
								$dewpoint_series = $convert_temp_series( $extract_daily_series( 'dew_point' ) );
							}
							$wind_chill_series = $convert_temp_series( $extract_daily_series( 'wind_chill' ) );
							$wind_dir_series = $extract_daily_series( 'wind_direction' );
							$humidity_series = $extract_daily_series( 'humidity' );
							$barometer_series = $extract_daily_series( 'barometer' );
							$wind_speed_series = $extract_daily_series( 'wind_speed' );
							$wind_gust_series = $extract_daily_series( 'wind_gust' );
							$rain_series = $extract_daily_series( 'rain' );
							$rain_rate_series = $extract_daily_series( 'rain_rate' );
							$daily_timestamps = $extract_daily_series( 'timestamp_epoch' );
							$daily_labels = $build_labels_from_epochs( $daily_timestamps, $format_time_only );
							?>
							<div class="weewx-weather__chart-grid">
								<?php
								$time_start = $format_time_only( $daily_start );
								$time_end   = $format_time_only( $daily_end );
								$daily_chart_options = array(
									'labels' => $daily_labels,
								);

								$render_chart(
									__( 'Temperature', 'wpweewx' ),
									array(
										array( 'values' => $temp_series, 'class' => 'primary', 'label' => __( 'Temp', 'wpweewx' ) ),
									),
									$time_start,
									$time_end,
									null,
									$daily_chart_options
								);

								if ( $series_has_numeric( $dewpoint_series ) ) {
									$render_chart(
										__( 'Dew Point', 'wpweewx' ),
										array(
											array( 'values' => $dewpoint_series, 'class' => 'secondary', 'label' => __( 'Dew Pt', 'wpweewx' ) ),
										),
										$time_start,
										$time_end,
										null,
										$daily_chart_options
									);
								}

								if ( $series_has_numeric( $wind_chill_series ) ) {
									$render_chart(
										__( 'Wind Chill', 'wpweewx' ),
										array(
											array( 'values' => $wind_chill_series, 'class' => 'tertiary', 'label' => __( 'Wind Chill', 'wpweewx' ) ),
										),
										$time_start,
										$time_end,
										null,
										$daily_chart_options
									);
								}

								list( $wind_dir_labels, $wind_dir_distribution ) = $bin_wind_dir( $wind_dir_series );
								$render_chart(
									__( 'Wind Dir', 'wpweewx' ),
									array(
										array( 'values' => $wind_dir_distribution, 'class' => 'primary', 'label' => __( 'Direction', 'wpweewx' ) ),
									),
									$time_start,
									$time_end,
									array( max( $wind_dir_distribution ), 0 ),
									array(
										'type'   => 'radar',
										'labels' => $wind_dir_labels,
									)
								);

								$render_chart(
									__( 'Wind Speed / Gust', 'wpweewx' ),
									array(
										array( 'values' => $wind_speed_series, 'class' => 'primary', 'type' => 'line', 'label' => __( 'Speed', 'wpweewx' ) ),
										array( 'values' => $wind_gust_series, 'class' => 'secondary', 'type' => 'bar', 'label' => __( 'Gust', 'wpweewx' ) ),
									),
									$time_start,
									$time_end,
									null,
									$daily_chart_options
								);

								$render_chart(
									__( 'Rain', 'wpweewx' ),
									array(
										array( 'values' => $rain_series, 'class' => 'primary', 'label' => __( 'Rain', 'wpweewx' ) ),
									),
									$time_start,
									$time_end,
									null,
									$daily_chart_options
								);

								$render_chart(
									__( 'Rain Rate', 'wpweewx' ),
									array(
										array( 'values' => $rain_rate_series, 'class' => 'secondary', 'label' => __( 'Rate', 'wpweewx' ) ),
									),
									$time_start,
									$time_end,
									null,
									$daily_chart_options
								);

								$render_chart(
									__( 'Humidity', 'wpweewx' ),
									array(
										array( 'values' => $humidity_series, 'class' => 'secondary', 'label' => __( 'Humidity', 'wpweewx' ) ),
									),
									$time_start,
									$time_end,
									null,
									$daily_chart_options
								);

								$render_chart(
									__( 'Barometer', 'wpweewx' ),
									array(
										array( 'values' => $barometer_series, 'class' => 'tertiary', 'label' => __( 'Barometer', 'wpweewx' ) ),
									),
									$time_start,
									$time_end,
									null,
									$daily_chart_options
								);
								?>
							</div>
						<?php else : ?>
							<?php esc_html_e( 'No daily capture rows available.', 'wpweewx' ); ?>
						<?php endif; ?>
					</div>

					<div class="weewx-weather__tab-panel" data-tab-panel="<?php echo esc_attr( $lcd_tabs_id . '-weekly' ); ?>" role="tabpanel">
						<?php if ( ! empty( $lcd_weekly ) ) : ?>
							<?php
							$temp_min = $convert_temp_series( $extract_summary_series( $lcd_weekly, 'temperature.min' ) );
							$temp_max = $convert_temp_series( $extract_summary_series( $lcd_weekly, 'temperature.max' ) );
							$temp_avg = $convert_temp_series( $extract_summary_series( $lcd_weekly, 'temperature.avg' ) );
							$dewpoint_min = $convert_temp_series( $extract_summary_series_fallback( $lcd_weekly, array( 'dewpoint.min', 'dew_point.min' ) ) );
							$dewpoint_max = $convert_temp_series( $extract_summary_series_fallback( $lcd_weekly, array( 'dewpoint.max', 'dew_point.max' ) ) );
							$dewpoint_avg = $convert_temp_series( $extract_summary_series_fallback( $lcd_weekly, array( 'dewpoint.avg', 'dew_point.avg' ) ) );
							$wind_chill_min = $convert_temp_series( $extract_summary_series( $lcd_weekly, 'wind_chill.min' ) );
							$wind_chill_max = $convert_temp_series( $extract_summary_series( $lcd_weekly, 'wind_chill.max' ) );
							$wind_chill_avg = $convert_temp_series( $extract_summary_series( $lcd_weekly, 'wind_chill.avg' ) );
							$humidity_min = $extract_summary_series( $lcd_weekly, 'humidity.min' );
							$humidity_max = $extract_summary_series( $lcd_weekly, 'humidity.max' );
							$humidity_avg = $extract_summary_series( $lcd_weekly, 'humidity.avg' );
							$barometer_min = $extract_summary_series( $lcd_weekly, 'barometer.min' );
							$barometer_max = $extract_summary_series( $lcd_weekly, 'barometer.max' );
							$barometer_avg = $extract_summary_series( $lcd_weekly, 'barometer.avg' );
							$wind_speed_min = array();
							$wind_speed_avg = $extract_summary_series( $lcd_weekly, 'wind.speed_avg' );
							$wind_speed_max = $extract_summary_series( $lcd_weekly, 'wind.speed_max' );
							$wind_gust_series = $extract_summary_series( $lcd_weekly, 'wind.gust_max' );
							$wind_dir_series = $extract_summary_series_fallback( $lcd_weekly, array( 'wind_direction.avg', 'wind_direction' ) );
							$rain_total = $extract_summary_series( $lcd_weekly, 'rain.total' );
							$rain_rate_series = $extract_summary_series( $lcd_weekly, 'rain.rate_max' );
							$weekly_labels = $build_labels_from_epochs(
								$extract_summary_series_fallback( $lcd_weekly, array( 'period_start_epoch', 'period_end_epoch' ) ),
								$format_date_only
							);
							?>
							<div class="weewx-weather__chart-grid">
								<?php
								list( $weekly_start, $weekly_end ) = $summary_range( $lcd_weekly );
								$weekly_start_label = $format_date_only( $weekly_start );
								$weekly_end_label   = $format_date_only( $weekly_end );
								$weekly_chart_options = array(
									'labels' => $weekly_labels,
								);

								$render_chart(
									__( 'Temperature (min/max/avg)', 'wpweewx' ),
									array(
										array( 'values' => $temp_min, 'class' => 'secondary', 'label' => __( 'Min', 'wpweewx' ) ),
										array( 'values' => $temp_avg, 'class' => 'primary', 'label' => __( 'Avg', 'wpweewx' ) ),
										array( 'values' => $temp_max, 'class' => 'tertiary', 'label' => __( 'Max', 'wpweewx' ) ),
									),
									$weekly_start_label,
									$weekly_end_label,
									null,
									$weekly_chart_options
								);

								if ( $series_has_numeric( $dewpoint_min ) || $series_has_numeric( $dewpoint_avg ) || $series_has_numeric( $dewpoint_max ) ) {
									$render_chart(
										__( 'Dew Point (min/max/avg)', 'wpweewx' ),
										array(
											array( 'values' => $dewpoint_min, 'class' => 'secondary', 'label' => __( 'Min', 'wpweewx' ) ),
											array( 'values' => $dewpoint_avg, 'class' => 'primary', 'label' => __( 'Avg', 'wpweewx' ) ),
											array( 'values' => $dewpoint_max, 'class' => 'tertiary', 'label' => __( 'Max', 'wpweewx' ) ),
										),
										$weekly_start_label,
										$weekly_end_label,
										null,
										$weekly_chart_options
									);
								}

								if ( $series_has_numeric( $wind_chill_min ) || $series_has_numeric( $wind_chill_avg ) || $series_has_numeric( $wind_chill_max ) ) {
									$render_chart(
										__( 'Wind Chill (min/max/avg)', 'wpweewx' ),
										array(
											array( 'values' => $wind_chill_min, 'class' => 'secondary', 'label' => __( 'Min', 'wpweewx' ) ),
											array( 'values' => $wind_chill_avg, 'class' => 'primary', 'label' => __( 'Avg', 'wpweewx' ) ),
											array( 'values' => $wind_chill_max, 'class' => 'tertiary', 'label' => __( 'Max', 'wpweewx' ) ),
										),
										$weekly_start_label,
										$weekly_end_label,
										null,
										$weekly_chart_options
									);
								}

								$render_chart(
									__( 'Humidity (min/max/avg)', 'wpweewx' ),
									array(
										array( 'values' => $humidity_min, 'class' => 'secondary', 'label' => __( 'Min', 'wpweewx' ) ),
										array( 'values' => $humidity_avg, 'class' => 'primary', 'label' => __( 'Avg', 'wpweewx' ) ),
										array( 'values' => $humidity_max, 'class' => 'tertiary', 'label' => __( 'Max', 'wpweewx' ) ),
									),
									$weekly_start_label,
									$weekly_end_label,
									null,
									$weekly_chart_options
								);

								$render_chart(
									__( 'Barometer (min/max/avg)', 'wpweewx' ),
									array(
										array( 'values' => $barometer_min, 'class' => 'secondary', 'label' => __( 'Min', 'wpweewx' ) ),
										array( 'values' => $barometer_avg, 'class' => 'primary', 'label' => __( 'Avg', 'wpweewx' ) ),
										array( 'values' => $barometer_max, 'class' => 'tertiary', 'label' => __( 'Max', 'wpweewx' ) ),
									),
									$weekly_start_label,
									$weekly_end_label,
									null,
									$weekly_chart_options
								);

								$render_chart(
									__( 'Wind Speed / Gust (min/max/avg)', 'wpweewx' ),
									array(
										array( 'values' => $wind_speed_min, 'class' => 'secondary', 'label' => __( 'Min', 'wpweewx' ) ),
										array( 'values' => $wind_speed_avg, 'class' => 'primary', 'label' => __( 'Avg', 'wpweewx' ) ),
										array( 'values' => $wind_speed_max, 'class' => 'tertiary', 'label' => __( 'Max', 'wpweewx' ) ),
										array( 'values' => $wind_gust_series, 'class' => 'quaternary', 'label' => __( 'Gust', 'wpweewx' ) ),
									),
									$weekly_start_label,
									$weekly_end_label,
									null,
									$weekly_chart_options
								);

								list( $weekly_wind_labels, $weekly_wind_bins ) = $bin_wind_dir( $wind_dir_series );
								$render_chart(
									__( 'Wind Dir (avg)', 'wpweewx' ),
									array(
										array( 'values' => $weekly_wind_bins, 'class' => 'primary', 'label' => __( 'Direction', 'wpweewx' ) ),
									),
									$weekly_start_label,
									$weekly_end_label,
									array( max( $weekly_wind_bins ), 0 ),
									array(
										'type'   => 'radar',
										'labels' => $weekly_wind_labels,
									)
								);

								$render_chart(
									__( 'Rain Total', 'wpweewx' ),
									array(
										array( 'values' => $rain_total, 'class' => 'primary', 'label' => __( 'Total', 'wpweewx' ) ),
									),
									$weekly_start_label,
									$weekly_end_label,
									null,
									$weekly_chart_options
								);

								$render_chart(
									__( 'Rain Rate', 'wpweewx' ),
									array(
										array( 'values' => $rain_rate_series, 'class' => 'primary', 'label' => __( 'Rate', 'wpweewx' ) ),
									),
									$weekly_start_label,
									$weekly_end_label,
									null,
									$weekly_chart_options
								);
								?>
							</div>
						<?php else : ?>
							<?php esc_html_e( 'No weekly summaries available.', 'wpweewx' ); ?>
						<?php endif; ?>
					</div>

					<div class="weewx-weather__tab-panel" data-tab-panel="<?php echo esc_attr( $lcd_tabs_id . '-monthly' ); ?>" role="tabpanel">
						<?php if ( ! empty( $lcd_monthly ) ) : ?>
							<?php
							$temp_min = $convert_temp_series( $extract_summary_series( $lcd_monthly, 'temperature.min' ) );
							$temp_max = $convert_temp_series( $extract_summary_series( $lcd_monthly, 'temperature.max' ) );
							$temp_avg = $convert_temp_series( $extract_summary_series( $lcd_monthly, 'temperature.avg' ) );
							$dewpoint_min = $convert_temp_series( $extract_summary_series_fallback( $lcd_monthly, array( 'dewpoint.min', 'dew_point.min' ) ) );
							$dewpoint_max = $convert_temp_series( $extract_summary_series_fallback( $lcd_monthly, array( 'dewpoint.max', 'dew_point.max' ) ) );
							$dewpoint_avg = $convert_temp_series( $extract_summary_series_fallback( $lcd_monthly, array( 'dewpoint.avg', 'dew_point.avg' ) ) );
							$wind_chill_min = $convert_temp_series( $extract_summary_series( $lcd_monthly, 'wind_chill.min' ) );
							$wind_chill_max = $convert_temp_series( $extract_summary_series( $lcd_monthly, 'wind_chill.max' ) );
							$wind_chill_avg = $convert_temp_series( $extract_summary_series( $lcd_monthly, 'wind_chill.avg' ) );
							$humidity_min = $extract_summary_series( $lcd_monthly, 'humidity.min' );
							$humidity_max = $extract_summary_series( $lcd_monthly, 'humidity.max' );
							$humidity_avg = $extract_summary_series( $lcd_monthly, 'humidity.avg' );
							$barometer_min = $extract_summary_series( $lcd_monthly, 'barometer.min' );
							$barometer_max = $extract_summary_series( $lcd_monthly, 'barometer.max' );
							$barometer_avg = $extract_summary_series( $lcd_monthly, 'barometer.avg' );
							$wind_speed_min = array();
							$wind_speed_avg = $extract_summary_series( $lcd_monthly, 'wind.speed_avg' );
							$wind_speed_max = $extract_summary_series( $lcd_monthly, 'wind.speed_max' );
							$wind_gust_series = $extract_summary_series( $lcd_monthly, 'wind.gust_max' );
							$wind_dir_series = $extract_summary_series_fallback( $lcd_monthly, array( 'wind_direction.avg', 'wind_direction' ) );
							$rain_total = $extract_summary_series( $lcd_monthly, 'rain.total' );
							$rain_rate_series = $extract_summary_series( $lcd_monthly, 'rain.rate_max' );
							$monthly_labels = $build_labels_from_epochs(
								$extract_summary_series_fallback( $lcd_monthly, array( 'period_start_epoch', 'period_end_epoch' ) ),
								$format_date_only
							);
							?>
							<div class="weewx-weather__chart-grid">
								<?php
								list( $monthly_start, $monthly_end ) = $summary_range( $lcd_monthly );
								$monthly_start_label = $format_date_only( $monthly_start );
								$monthly_end_label   = $format_date_only( $monthly_end );
								$monthly_chart_options = array(
									'labels' => $monthly_labels,
								);

								$render_chart(
									__( 'Temperature (min/max/avg)', 'wpweewx' ),
									array(
										array( 'values' => $temp_min, 'class' => 'secondary', 'label' => __( 'Min', 'wpweewx' ) ),
										array( 'values' => $temp_avg, 'class' => 'primary', 'label' => __( 'Avg', 'wpweewx' ) ),
										array( 'values' => $temp_max, 'class' => 'tertiary', 'label' => __( 'Max', 'wpweewx' ) ),
									),
									$monthly_start_label,
									$monthly_end_label,
									null,
									$monthly_chart_options
								);

								if ( $series_has_numeric( $dewpoint_min ) || $series_has_numeric( $dewpoint_avg ) || $series_has_numeric( $dewpoint_max ) ) {
									$render_chart(
										__( 'Dew Point (min/max/avg)', 'wpweewx' ),
										array(
											array( 'values' => $dewpoint_min, 'class' => 'secondary', 'label' => __( 'Min', 'wpweewx' ) ),
											array( 'values' => $dewpoint_avg, 'class' => 'primary', 'label' => __( 'Avg', 'wpweewx' ) ),
											array( 'values' => $dewpoint_max, 'class' => 'tertiary', 'label' => __( 'Max', 'wpweewx' ) ),
										),
										$monthly_start_label,
										$monthly_end_label,
										null,
										$monthly_chart_options
									);
								}

								if ( $series_has_numeric( $wind_chill_min ) || $series_has_numeric( $wind_chill_avg ) || $series_has_numeric( $wind_chill_max ) ) {
									$render_chart(
										__( 'Wind Chill (min/max/avg)', 'wpweewx' ),
										array(
											array( 'values' => $wind_chill_min, 'class' => 'secondary', 'label' => __( 'Min', 'wpweewx' ) ),
											array( 'values' => $wind_chill_avg, 'class' => 'primary', 'label' => __( 'Avg', 'wpweewx' ) ),
											array( 'values' => $wind_chill_max, 'class' => 'tertiary', 'label' => __( 'Max', 'wpweewx' ) ),
										),
										$monthly_start_label,
										$monthly_end_label,
										null,
										$monthly_chart_options
									);
								}

								$render_chart(
									__( 'Humidity (min/max/avg)', 'wpweewx' ),
									array(
										array( 'values' => $humidity_min, 'class' => 'secondary', 'label' => __( 'Min', 'wpweewx' ) ),
										array( 'values' => $humidity_avg, 'class' => 'primary', 'label' => __( 'Avg', 'wpweewx' ) ),
										array( 'values' => $humidity_max, 'class' => 'tertiary', 'label' => __( 'Max', 'wpweewx' ) ),
									),
									$monthly_start_label,
									$monthly_end_label,
									null,
									$monthly_chart_options
								);

								$render_chart(
									__( 'Barometer (min/max/avg)', 'wpweewx' ),
									array(
										array( 'values' => $barometer_min, 'class' => 'secondary', 'label' => __( 'Min', 'wpweewx' ) ),
										array( 'values' => $barometer_avg, 'class' => 'primary', 'label' => __( 'Avg', 'wpweewx' ) ),
										array( 'values' => $barometer_max, 'class' => 'tertiary', 'label' => __( 'Max', 'wpweewx' ) ),
									),
									$monthly_start_label,
									$monthly_end_label,
									null,
									$monthly_chart_options
								);

								$render_chart(
									__( 'Wind Speed / Gust (min/max/avg)', 'wpweewx' ),
									array(
										array( 'values' => $wind_speed_min, 'class' => 'secondary', 'label' => __( 'Min', 'wpweewx' ) ),
										array( 'values' => $wind_speed_avg, 'class' => 'primary', 'label' => __( 'Avg', 'wpweewx' ) ),
										array( 'values' => $wind_speed_max, 'class' => 'tertiary', 'label' => __( 'Max', 'wpweewx' ) ),
										array( 'values' => $wind_gust_series, 'class' => 'quaternary', 'label' => __( 'Gust', 'wpweewx' ) ),
									),
									$monthly_start_label,
									$monthly_end_label,
									null,
									$monthly_chart_options
								);

								list( $monthly_wind_labels, $monthly_wind_bins ) = $bin_wind_dir( $wind_dir_series );
								$render_chart(
									__( 'Wind Dir (avg)', 'wpweewx' ),
									array(
										array( 'values' => $monthly_wind_bins, 'class' => 'primary', 'label' => __( 'Direction', 'wpweewx' ) ),
									),
									$monthly_start_label,
									$monthly_end_label,
									array( max( $monthly_wind_bins ), 0 ),
									array(
										'type'   => 'radar',
										'labels' => $monthly_wind_labels,
									)
								);

								$render_chart(
									__( 'Rain Total', 'wpweewx' ),
									array(
										array( 'values' => $rain_total, 'class' => 'primary', 'label' => __( 'Total', 'wpweewx' ) ),
									),
									$monthly_start_label,
									$monthly_end_label,
									null,
									$monthly_chart_options
								);

								$render_chart(
									__( 'Rain Rate', 'wpweewx' ),
									array(
										array( 'values' => $rain_rate_series, 'class' => 'primary', 'label' => __( 'Rate', 'wpweewx' ) ),
									),
									$monthly_start_label,
									$monthly_end_label,
									null,
									$monthly_chart_options
								);
								?>
							</div>
						<?php else : ?>
							<?php esc_html_e( 'No monthly summaries available.', 'wpweewx' ); ?>
						<?php endif; ?>
					</div>

					<div class="weewx-weather__tab-panel" data-tab-panel="<?php echo esc_attr( $lcd_tabs_id . '-yearly' ); ?>" role="tabpanel">
						<?php if ( ! empty( $lcd_yearly ) ) : ?>
							<?php
							$temp_min = $convert_temp_series( $extract_summary_series( $lcd_yearly, 'temperature.min' ) );
							$temp_max = $convert_temp_series( $extract_summary_series( $lcd_yearly, 'temperature.max' ) );
							$temp_avg = $convert_temp_series( $extract_summary_series( $lcd_yearly, 'temperature.avg' ) );
							$dewpoint_min = $convert_temp_series( $extract_summary_series_fallback( $lcd_yearly, array( 'dewpoint.min', 'dew_point.min' ) ) );
							$dewpoint_max = $convert_temp_series( $extract_summary_series_fallback( $lcd_yearly, array( 'dewpoint.max', 'dew_point.max' ) ) );
							$dewpoint_avg = $convert_temp_series( $extract_summary_series_fallback( $lcd_yearly, array( 'dewpoint.avg', 'dew_point.avg' ) ) );
							$wind_chill_min = $convert_temp_series( $extract_summary_series( $lcd_yearly, 'wind_chill.min' ) );
							$wind_chill_max = $convert_temp_series( $extract_summary_series( $lcd_yearly, 'wind_chill.max' ) );
							$wind_chill_avg = $convert_temp_series( $extract_summary_series( $lcd_yearly, 'wind_chill.avg' ) );
							$humidity_min = $extract_summary_series( $lcd_yearly, 'humidity.min' );
							$humidity_max = $extract_summary_series( $lcd_yearly, 'humidity.max' );
							$humidity_avg = $extract_summary_series( $lcd_yearly, 'humidity.avg' );
							$barometer_min = $extract_summary_series( $lcd_yearly, 'barometer.min' );
							$barometer_max = $extract_summary_series( $lcd_yearly, 'barometer.max' );
							$barometer_avg = $extract_summary_series( $lcd_yearly, 'barometer.avg' );
							$wind_speed_min = array();
							$wind_speed_avg = $extract_summary_series( $lcd_yearly, 'wind.speed_avg' );
							$wind_speed_max = $extract_summary_series( $lcd_yearly, 'wind.speed_max' );
							$wind_gust_series = $extract_summary_series( $lcd_yearly, 'wind.gust_max' );
							$wind_dir_series = $extract_summary_series_fallback( $lcd_yearly, array( 'wind_direction.avg', 'wind_direction' ) );
							$rain_total = $extract_summary_series( $lcd_yearly, 'rain.total' );
							$rain_rate_series = $extract_summary_series( $lcd_yearly, 'rain.rate_max' );
							$yearly_labels = $build_labels_from_epochs(
								$extract_summary_series_fallback( $lcd_yearly, array( 'period_start_epoch', 'period_end_epoch' ) ),
								$format_date_only
							);
							?>
							<div class="weewx-weather__chart-grid">
								<?php
								list( $yearly_start, $yearly_end ) = $summary_range( $lcd_yearly );
								$yearly_start_label = $format_date_only( $yearly_start );
								$yearly_end_label   = $format_date_only( $yearly_end );
								$yearly_chart_options = array(
									'labels' => $yearly_labels,
								);

								$render_chart(
									__( 'Temperature (min/max/avg)', 'wpweewx' ),
									array(
										array( 'values' => $temp_min, 'class' => 'secondary', 'label' => __( 'Min', 'wpweewx' ) ),
										array( 'values' => $temp_avg, 'class' => 'primary', 'label' => __( 'Avg', 'wpweewx' ) ),
										array( 'values' => $temp_max, 'class' => 'tertiary', 'label' => __( 'Max', 'wpweewx' ) ),
									),
									$yearly_start_label,
									$yearly_end_label,
									null,
									$yearly_chart_options
								);

								if ( $series_has_numeric( $dewpoint_min ) || $series_has_numeric( $dewpoint_avg ) || $series_has_numeric( $dewpoint_max ) ) {
									$render_chart(
										__( 'Dew Point (min/max/avg)', 'wpweewx' ),
										array(
											array( 'values' => $dewpoint_min, 'class' => 'secondary', 'label' => __( 'Min', 'wpweewx' ) ),
											array( 'values' => $dewpoint_avg, 'class' => 'primary', 'label' => __( 'Avg', 'wpweewx' ) ),
											array( 'values' => $dewpoint_max, 'class' => 'tertiary', 'label' => __( 'Max', 'wpweewx' ) ),
										),
										$yearly_start_label,
										$yearly_end_label,
										null,
										$yearly_chart_options
									);
								}

								if ( $series_has_numeric( $wind_chill_min ) || $series_has_numeric( $wind_chill_avg ) || $series_has_numeric( $wind_chill_max ) ) {
									$render_chart(
										__( 'Wind Chill (min/max/avg)', 'wpweewx' ),
										array(
											array( 'values' => $wind_chill_min, 'class' => 'secondary', 'label' => __( 'Min', 'wpweewx' ) ),
											array( 'values' => $wind_chill_avg, 'class' => 'primary', 'label' => __( 'Avg', 'wpweewx' ) ),
											array( 'values' => $wind_chill_max, 'class' => 'tertiary', 'label' => __( 'Max', 'wpweewx' ) ),
										),
										$yearly_start_label,
										$yearly_end_label,
										null,
										$yearly_chart_options
									);
								}

								$render_chart(
									__( 'Humidity (min/max/avg)', 'wpweewx' ),
									array(
										array( 'values' => $humidity_min, 'class' => 'secondary', 'label' => __( 'Min', 'wpweewx' ) ),
										array( 'values' => $humidity_avg, 'class' => 'primary', 'label' => __( 'Avg', 'wpweewx' ) ),
										array( 'values' => $humidity_max, 'class' => 'tertiary', 'label' => __( 'Max', 'wpweewx' ) ),
									),
									$yearly_start_label,
									$yearly_end_label,
									null,
									$yearly_chart_options
								);

								$render_chart(
									__( 'Barometer (min/max/avg)', 'wpweewx' ),
									array(
										array( 'values' => $barometer_min, 'class' => 'secondary', 'label' => __( 'Min', 'wpweewx' ) ),
										array( 'values' => $barometer_avg, 'class' => 'primary', 'label' => __( 'Avg', 'wpweewx' ) ),
										array( 'values' => $barometer_max, 'class' => 'tertiary', 'label' => __( 'Max', 'wpweewx' ) ),
									),
									$yearly_start_label,
									$yearly_end_label,
									null,
									$yearly_chart_options
								);

								$render_chart(
									__( 'Wind Speed / Gust (min/max/avg)', 'wpweewx' ),
									array(
										array( 'values' => $wind_speed_min, 'class' => 'secondary', 'label' => __( 'Min', 'wpweewx' ) ),
										array( 'values' => $wind_speed_avg, 'class' => 'primary', 'label' => __( 'Avg', 'wpweewx' ) ),
										array( 'values' => $wind_speed_max, 'class' => 'tertiary', 'label' => __( 'Max', 'wpweewx' ) ),
										array( 'values' => $wind_gust_series, 'class' => 'quaternary', 'label' => __( 'Gust', 'wpweewx' ) ),
									),
									$yearly_start_label,
									$yearly_end_label,
									null,
									$yearly_chart_options
								);

								list( $yearly_wind_labels, $yearly_wind_bins ) = $bin_wind_dir( $wind_dir_series );
								$render_chart(
									__( 'Wind Dir (avg)', 'wpweewx' ),
									array(
										array( 'values' => $yearly_wind_bins, 'class' => 'primary', 'label' => __( 'Direction', 'wpweewx' ) ),
									),
									$yearly_start_label,
									$yearly_end_label,
									array( max( $yearly_wind_bins ), 0 ),
									array(
										'type'   => 'radar',
										'labels' => $yearly_wind_labels,
									)
								);

								$render_chart(
									__( 'Rain Total', 'wpweewx' ),
									array(
										array( 'values' => $rain_total, 'class' => 'primary', 'label' => __( 'Total', 'wpweewx' ) ),
									),
									$yearly_start_label,
									$yearly_end_label,
									null,
									$yearly_chart_options
								);

								$render_chart(
									__( 'Rain Rate', 'wpweewx' ),
									array(
										array( 'values' => $rain_rate_series, 'class' => 'primary', 'label' => __( 'Rate', 'wpweewx' ) ),
									),
									$yearly_start_label,
									$yearly_end_label,
									null,
									$yearly_chart_options
								);
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
							var tabsId = <?php echo json_encode( $lcd_tabs_id ); ?>;
							var root = document.querySelector('[data-tabs="' + tabsId + '"]');
							if (!root) {
								return;
							}
							var buttons = root.querySelectorAll('.weewx-weather__tab-button');
							var widget = root.closest('.weewx-weather');
							var panels = widget ? widget.querySelectorAll('[data-tab-panel^="' + tabsId + '-"]') : [];
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
