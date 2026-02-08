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
	'wind_direction'  => __( 'Wind Direction', 'wpweewx' ),
	'wind_speed'      => __( 'Wind Speed', 'wpweewx' ),
	'wind_gust'       => __( 'Wind Gust', 'wpweewx' ),
	'rain'            => __( 'Rain', 'wpweewx' ),
	'rain_rate'       => __( 'Rain Rate', 'wpweewx' ),
	'humidity'        => __( 'Humidity', 'wpweewx' ),
	'wind_chill'      => __( 'Wind Chill', 'wpweewx' ),
	'barometer'       => __( 'Barometer', 'wpweewx' ),
	'sqm'             => __( 'SQM', 'wpweewx' ),
	'sqmTemp'         => __( 'SQM Temp', 'wpweewx' ),
	'sqmtemp'         => __( 'SQM Temp', 'wpweewx' ),
	'sqm_temp'        => __( 'SQM Temp', 'wpweewx' ),
	'sqmTime'         => __( 'SQM Time', 'wpweewx' ),
	'sqm_time'        => __( 'SQM Time', 'wpweewx' ),
	'nelm'            => __( 'NELM', 'wpweewx' ),
	'cdm2'            => __( 'cd/m2', 'wpweewx' ),
	'nsu'             => __( 'NSU', 'wpweewx' ),
	'solarAlt'        => __( 'Solar Alt', 'wpweewx' ),
	'solaralt'        => __( 'Solar Alt', 'wpweewx' ),
	'solar_alt'       => __( 'Solar Alt', 'wpweewx' ),
	'lunarAlt'        => __( 'Lunar Alt', 'wpweewx' ),
	'lunaralt'        => __( 'Lunar Alt', 'wpweewx' ),
	'lunar_alt'       => __( 'Lunar Alt', 'wpweewx' ),
	'lunarPhase'      => __( 'Lunar Phase', 'wpweewx' ),
	'lunarphase'      => __( 'Lunar Phase', 'wpweewx' ),
	'lunar_phase'     => __( 'Lunar Phase', 'wpweewx' ),
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
$sqm_latest = null;
if ( ! empty( $lcd_daily_rows ) && ! empty( $lcd_field_index ) ) {
	$sqm_fields = array(
		'sqm',
		'sqmTemp',
		'sqmtemp',
		'sqm_temp',
		'sqmTime',
		'sqm_time',
		'nelm',
		'cdm2',
		'cd_m2',
		'nsu',
		'solarAlt',
		'solaralt',
		'solar_alt',
		'lunarAlt',
		'lunaralt',
		'lunar_alt',
		'lunarPhase',
		'lunarphase',
		'lunar_phase',
	);
	for ( $i = count( $lcd_daily_rows ) - 1; $i >= 0; $i-- ) {
		$row = $lcd_daily_rows[ $i ];
		if ( ! is_array( $row ) ) {
			continue;
		}
		$has_value = false;
		foreach ( $sqm_fields as $field ) {
			if ( ! isset( $lcd_field_index[ $field ] ) ) {
				continue;
			}
			$idx = $lcd_field_index[ $field ];
			$val = isset( $row[ $idx ] ) ? $row[ $idx ] : null;
			if ( is_numeric( $val ) ) {
				$has_value = true;
				break;
			}
		}
		if ( ! $has_value ) {
			continue;
		}
		$sqm_latest = array();
		foreach ( $lcd_fields as $index => $field ) {
			$sqm_latest[ $field ] = isset( $row[ $index ] ) ? $row[ $index ] : null;
		}
		break;
	}
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
$extract_daily_series_fallback = function( $fields ) use ( $extract_daily_series ) {
	$fields = is_array( $fields ) ? $fields : array( $fields );
	$has_numeric = function( $series ) {
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
	foreach ( $fields as $field ) {
		$series = $extract_daily_series( $field );
		if ( $has_numeric( $series ) ) {
			return $series;
		}
	}
	$first = reset( $fields );
	return $extract_daily_series( false !== $first ? $first : '' );
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
$build_summary_series_set = function( $items, $roots ) use ( $extract_summary_series_fallback, $series_has_numeric ) {
	$roots = is_array( $roots ) ? array_values( array_filter( $roots ) ) : array( (string) $roots );
	$build_paths = function( $suffix ) use ( $roots ) {
		$paths = array();
		foreach ( $roots as $root ) {
			$paths[] = '' === $suffix ? $root : $root . '.' . $suffix;
		}
		return $paths;
	};

	$min = $extract_summary_series_fallback( $items, $build_paths( 'min' ) );
	$avg = $extract_summary_series_fallback( $items, $build_paths( 'avg' ) );
	$max = $extract_summary_series_fallback( $items, $build_paths( 'max' ) );

	if ( $series_has_numeric( $min ) || $series_has_numeric( $avg ) || $series_has_numeric( $max ) ) {
		return array(
			array( 'values' => $min, 'class' => 'secondary', 'label' => __( 'Min', 'wpweewx' ) ),
			array( 'values' => $avg, 'class' => 'primary', 'label' => __( 'Avg', 'wpweewx' ) ),
			array( 'values' => $max, 'class' => 'tertiary', 'label' => __( 'Max', 'wpweewx' ) ),
		);
	}

	$single = $extract_summary_series_fallback(
		$items,
		array_merge(
			$build_paths( 'value' ),
			$build_paths( 'last' ),
			$build_paths( '' )
		)
	);
	if ( $series_has_numeric( $single ) ) {
		return array(
			array( 'values' => $single, 'class' => 'primary', 'label' => __( 'Value', 'wpweewx' ) ),
		);
	}

	return array();
};
$sqm_metrics = array(
	array(
		'title'         => __( 'SQM', 'wpweewx' ),
		'daily_fields'  => array( 'sqm' ),
		'summary_roots' => array( 'sqm' ),
	),
	array(
		'title'         => __( 'SQM Temp', 'wpweewx' ),
		'daily_fields'  => array( 'sqmTemp', 'sqm_temp' ),
		'summary_roots' => array( 'sqmTemp', 'sqm_temp' ),
	),
	array(
		'title'         => __( 'NELM', 'wpweewx' ),
		'daily_fields'  => array( 'nelm' ),
		'summary_roots' => array( 'nelm' ),
	),
	array(
		'title'         => __( 'cd/m2', 'wpweewx' ),
		'daily_fields'  => array( 'cdm2', 'cd_m2' ),
		'summary_roots' => array( 'cdm2', 'cd_m2' ),
		'value_format'  => 'sci',
	),
	array(
		'title'         => __( 'NSU', 'wpweewx' ),
		'daily_fields'  => array( 'nsu' ),
		'summary_roots' => array( 'nsu' ),
	),
	array(
		'title'         => __( 'Solar Alt', 'wpweewx' ),
		'daily_fields'  => array( 'solarAlt', 'solar_alt' ),
		'summary_roots' => array( 'solarAlt', 'solar_alt' ),
		'value_format'  => 'fixed:2',
	),
	array(
		'title'         => __( 'Lunar Alt', 'wpweewx' ),
		'daily_fields'  => array( 'lunarAlt', 'lunar_alt' ),
		'summary_roots' => array( 'lunarAlt', 'lunar_alt' ),
		'value_format'  => 'fixed:2',
	),
	array(
		'title'         => __( 'Lunar Phase', 'wpweewx' ),
		'daily_fields'  => array( 'lunarPhase', 'lunar_phase' ),
		'summary_roots' => array( 'lunarPhase', 'lunar_phase' ),
		'value_format'  => 'fixed:2',
	),
);
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
$format_value_fixed = function( $value, $precision ) {
	if ( ! is_numeric( $value ) ) {
		return '—';
	}
	$precision = is_numeric( $precision ) ? (int) $precision : 2;
	return number_format_i18n( (float) $value, $precision );
};
$format_value_sci = function( $value ) {
	if ( ! is_numeric( $value ) ) {
		return '—';
	}
	return sprintf( '%.3e', (float) $value );
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
$render_chart = function( $title, $series_list, $x_start, $x_end, $axis_ticks = null, $chart_options = array() ) use ( $format_value, $format_value_fixed, $format_value_sci, $series_has_numeric, $series_minmax ) {
	$chart_type = isset( $chart_options['type'] ) ? $chart_options['type'] : 'line';
	$show_series_labels = ! empty( $chart_options['show_series_labels'] );
	$series_list = is_array( $series_list ) ? $series_list : array();
	$value_format = isset( $chart_options['value_format'] ) ? $chart_options['value_format'] : null;
	$fixed_precision = null;
	if ( is_string( $value_format ) && 0 === strpos( $value_format, 'fixed:' ) ) {
		$fixed_precision = (int) substr( $value_format, strlen( 'fixed:' ) );
	} elseif ( is_int( $value_format ) ) {
		$fixed_precision = $value_format;
	}
	$format_value_fn = function( $value ) use ( $format_value, $format_value_fixed, $format_value_sci, $value_format, $fixed_precision ) {
		if ( 'sci' === $value_format ) {
			return $format_value_sci( $value );
		}
		if ( null !== $fixed_precision ) {
			return $format_value_fixed( $value, $fixed_precision );
		}
		return $format_value( $value );
	};
	$filtered_series = array();
	$normalize_value = function( $value ) use ( $fixed_precision ) {
		if ( ! is_numeric( $value ) ) {
			return $value;
		}
		$number = (float) $value;
		if ( null !== $fixed_precision ) {
			return round( $number, $fixed_precision );
		}
		return $number;
	};
	foreach ( $series_list as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$values = isset( $item['values'] ) && is_array( $item['values'] ) ? array_map( $normalize_value, $item['values'] ) : array();
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

	$min_label = $format_value_fn( $min );
	$max_label = $format_value_fn( $max );

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
			list( $series_min, $series_max ) = $series_minmax( array( $item['values'] ) );
			$datasets[] = array(
				'data'  => $item['values'],
				'class' => $item['class'],
				'type'  => $item['type'],
				'label' => $item['label'],
			);
			$legend_items[] = array(
				'label'     => isset( $item['label'] ) ? $item['label'] : '',
				'class'     => $item['class'],
				'min_label' => $format_value_fn( $series_min ),
				'max_label' => $format_value_fn( $series_max ),
			);
		}
		$payload = array(
			'labels'   => $labels,
			'datasets' => $datasets,
			'type'     => isset( $chart_options['type'] ) ? $chart_options['type'] : 'line',
		);
		if ( isset( $chart_options['value_format'] ) ) {
			$payload['valueFormat'] = is_string( $chart_options['value_format'] ) ? $chart_options['value_format'] : (string) $chart_options['value_format'];
		}
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
			<div class="weewx-weather__chart-series" role="presentation">
				<?php foreach ( $legend_items as $legend ) : ?>
					<div class="weewx-weather__chart-series-row">
						<span class="weewx-weather__chart-legend-item">
							<span class="weewx-weather__chart-legend-swatch weewx-weather__chart-legend-swatch--<?php echo esc_attr( $legend['class'] ); ?>"></span>
							<?php if ( $show_series_labels && ! empty( $legend['label'] ) ) : ?>
								<span class="weewx-weather__chart-legend-label"><?php echo esc_html( $legend['label'] ); ?></span>
							<?php endif; ?>
						</span>
						<span class="weewx-weather__chart-minmax">
							<span><?php echo esc_html__( 'Min', 'wpweewx' ); ?> <?php echo esc_html( $legend['min_label'] ); ?></span>
							<span>/</span>
							<span><?php echo esc_html__( 'Max', 'wpweewx' ); ?> <?php echo esc_html( $legend['max_label'] ); ?></span>
						</span>
					</div>
				<?php endforeach; ?>
			</div>
		<?php elseif ( ! empty( $filtered_series ) ) : ?>
			<div class="weewx-weather__chart-minmax">
				<span><?php echo esc_html__( 'Min', 'wpweewx' ); ?> <?php echo esc_html( $min_label ); ?></span>
				<span>/</span>
				<span><?php echo esc_html__( 'Max', 'wpweewx' ); ?> <?php echo esc_html( $max_label ); ?></span>
			</div>
		<?php endif; ?>
		<div class="weewx-weather__chart-body">
			<div class="weewx-weather__chart-plot">
				<?php if ( $payload ) : ?>
					<canvas class="weewx-weather__chart-canvas" aria-hidden="true"></canvas>
					<script type="application/json" class="weewx-weather__chart-data"><?php echo wp_json_encode( $payload ); ?></script>
				<?php else : ?>
					<div class="weewx-weather__chart-empty"><?php esc_html_e( 'No data', 'wpweewx' ); ?></div>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
};
$lcd_current = array();
if ( isset( $lcd_data['lcd_datasheet']['current'] ) && is_array( $lcd_data['lcd_datasheet']['current'] ) ) {
	$lcd_current = $lcd_data['lcd_datasheet']['current'];
}
$has_lcd_datasheet = ! empty( $lcd_current ) || $lcd_latest || ! empty( $lcd_daily_rows ) || ! empty( $lcd_weekly ) || ! empty( $lcd_monthly ) || ! empty( $lcd_yearly );
$show_sqm = (int) WPWeeWX_Settings::get( 'wpweewx_show_sqm' ) === 1;
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

	<div class="weewx-weather__tabs" data-tabs="<?php echo esc_attr( $has_lcd_datasheet && 'lcd' === $source ? $lcd_tabs_id : $tabs_id ); ?>">
		<?php if ( 'lcd' === $source && $has_lcd_datasheet ) : ?>
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
		<?php if ( $has_lcd_datasheet ) : ?>
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
							$lcd_latest_excluded_fields = array(
								'sqm',
								'sqmTemp',
								'sqmtemp',
								'sqm_temp',
								'sqmTime',
								'sqm_time',
								'nelm',
								'cdm2',
								'cd_m2',
								'nsu',
								'solarAlt',
								'solaralt',
								'solar_alt',
								'lunarAlt',
								'lunaralt',
								'lunar_alt',
								'lunarPhase',
								'lunarphase',
								'lunar_phase',
							);
							foreach ( $lcd_latest_fields as $field ) {
								if ( 'timestamp_epoch' === $field ) {
									continue;
								}
								if ( in_array( $field, $lcd_latest_excluded_fields, true ) ) {
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
									__( 'Wind Direction', 'wpweewx' ),
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
									array_merge(
										$daily_chart_options,
										array(
											'show_series_labels' => true,
										)
									)
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
									array_merge(
										$weekly_chart_options,
										array(
											'show_series_labels' => true,
										)
									)
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
									__( 'Wind Direction (avg)', 'wpweewx' ),
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
									array_merge(
										$monthly_chart_options,
										array(
											'show_series_labels' => true,
										)
									)
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
									__( 'Wind Direction (avg)', 'wpweewx' ),
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
									array_merge(
										$yearly_chart_options,
										array(
											'show_series_labels' => true,
										)
									)
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
									__( 'Wind Direction (avg)', 'wpweewx' ),
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
			<?php
			$has_sqm_current = null !== WPWeeWX_Parser::get( $lcd_current, 'sqm' ) ||
				null !== WPWeeWX_Parser::get( $lcd_current, 'sqmTemp' ) ||
				null !== WPWeeWX_Parser::get( $lcd_current, 'sqmTime' ) ||
				null !== WPWeeWX_Parser::get( $lcd_current, 'sqm_time' ) ||
				null !== WPWeeWX_Parser::get( $lcd_current, 'nelm' ) ||
				null !== WPWeeWX_Parser::get( $lcd_current, 'cdm2' ) ||
				null !== WPWeeWX_Parser::get( $lcd_current, 'nsu' ) ||
				null !== WPWeeWX_Parser::get( $lcd_current, 'solarAlt' ) ||
				null !== WPWeeWX_Parser::get( $lcd_current, 'lunarAlt' ) ||
				null !== WPWeeWX_Parser::get( $lcd_current, 'lunarPhase' );
			$has_sqm_sections = $show_sqm && ( $has_sqm_current || ! empty( $sqm_latest ) || ! empty( $lcd_daily_recent ) || ! empty( $lcd_weekly ) || ! empty( $lcd_monthly ) || ! empty( $lcd_yearly ) );
			if ( $has_sqm_sections ) :
				ob_start();
				$sqm_time_value = WPWeeWX_Parser::get( $lcd_current, 'sqmTime' );
				if ( ! is_numeric( $sqm_time_value ) ) {
					$sqm_time_value = WPWeeWX_Parser::get( $lcd_current, 'sqm_time' );
				}
				if ( ! is_numeric( $sqm_time_value ) && is_array( $sqm_latest ) && isset( $sqm_latest['sqmTime'] ) ) {
					$sqm_time_value = $sqm_latest['sqmTime'];
				}
				if ( ! is_numeric( $sqm_time_value ) && is_array( $sqm_latest ) && isset( $sqm_latest['sqm_time'] ) ) {
					$sqm_time_value = $sqm_latest['sqm_time'];
				}
				if ( ! is_numeric( $sqm_time_value ) && is_array( $sqm_latest ) && isset( $sqm_latest['timestamp_epoch'] ) ) {
					$sqm_time_value = $sqm_latest['timestamp_epoch'];
				}
				$sqm_display = $has_sqm_current ? $lcd_current : $sqm_latest;
				$has_sqm_display = ! empty( $sqm_display );
				?>
				<div class="weewx-weather__tab-panel is-active" data-tab-panel="<?php echo esc_attr( $lcd_tabs_id . '-latest' ); ?>" role="tabpanel">
					<?php if ( $has_sqm_display ) : ?>
						<?php if ( is_numeric( $sqm_time_value ) ) : ?>
							<?php WPWeeWX_Renderer::metric_row( __( 'SQM Time', 'wpweewx' ), $format_epoch( $sqm_time_value ) ); ?>
						<?php endif; ?>
						<?php WPWeeWX_Renderer::metric_row( __( 'SQM', 'wpweewx' ), WPWeeWX_Renderer::display_value( $sqm_display, 'sqm' ) ); ?>
						<?php WPWeeWX_Renderer::metric_row( __( 'SQM Temp', 'wpweewx' ), WPWeeWX_Renderer::display_value( $sqm_display, 'sqmTemp' ) ); ?>
						<?php WPWeeWX_Renderer::metric_row( __( 'NELM', 'wpweewx' ), WPWeeWX_Renderer::display_value( $sqm_display, 'nelm' ) ); ?>
						<?php WPWeeWX_Renderer::metric_row( __( 'cd/m2', 'wpweewx' ), WPWeeWX_Renderer::display_value( $sqm_display, 'cdm2' ) ); ?>
						<?php WPWeeWX_Renderer::metric_row( __( 'NSU', 'wpweewx' ), WPWeeWX_Renderer::display_value( $sqm_display, 'nsu' ) ); ?>
						<?php WPWeeWX_Renderer::metric_row( __( 'Solar Alt', 'wpweewx' ), WPWeeWX_Renderer::display_value( $sqm_display, 'solarAlt' ) ); ?>
						<?php WPWeeWX_Renderer::metric_row( __( 'Lunar Alt', 'wpweewx' ), WPWeeWX_Renderer::display_value( $sqm_display, 'lunarAlt' ) ); ?>
						<?php WPWeeWX_Renderer::metric_row( __( 'Lunar Phase', 'wpweewx' ), WPWeeWX_Renderer::display_value( $sqm_display, 'lunarPhase' ) ); ?>
					<?php else : ?>
						<?php esc_html_e( 'No SQM latest data available.', 'wpweewx' ); ?>
					<?php endif; ?>
				</div>
				<div class="weewx-weather__tab-panel" data-tab-panel="<?php echo esc_attr( $lcd_tabs_id . '-daily' ); ?>" role="tabpanel">
					<div class="weewx-weather__chart-grid">
						<?php
						$sqm_daily_has_chart = false;
						$sqm_daily_timestamps = $extract_daily_series( 'sqmTime' );
						if ( ! $series_has_numeric( $sqm_daily_timestamps ) ) {
							$sqm_daily_timestamps = $extract_daily_series( 'timestamp_epoch' );
						}
						$sqm_daily_labels = $build_labels_from_epochs( $sqm_daily_timestamps, $format_time_only );
						$sqm_daily_chart_options = array(
							'labels' => $sqm_daily_labels,
						);
						$sqm_daily_start = $format_time_only( $daily_start );
						$sqm_daily_end = $format_time_only( $daily_end );
						foreach ( $sqm_metrics as $metric ) {
							$sqm_daily_series = $extract_daily_series_fallback( $metric['daily_fields'] );
							if ( ! $series_has_numeric( $sqm_daily_series ) ) {
								continue;
							}
							$chart_options = $sqm_daily_chart_options;
							if ( ! empty( $metric['value_format'] ) ) {
								$chart_options['value_format'] = $metric['value_format'];
							}
							$sqm_daily_has_chart = true;
							$render_chart(
								$metric['title'],
								array(
									array( 'values' => $sqm_daily_series, 'class' => 'primary', 'label' => __( 'Value', 'wpweewx' ) ),
								),
								$sqm_daily_start,
								$sqm_daily_end,
								null,
								$chart_options
							);
						}
						if ( ! $sqm_daily_has_chart ) {
							esc_html_e( 'No SQM daily captures available.', 'wpweewx' );
						}
						?>
					</div>
				</div>

				<div class="weewx-weather__tab-panel" data-tab-panel="<?php echo esc_attr( $lcd_tabs_id . '-weekly' ); ?>" role="tabpanel">
					<div class="weewx-weather__chart-grid">
						<?php
						$sqm_weekly_has_chart = false;
						list( $sqm_weekly_start, $sqm_weekly_end ) = $summary_range( $lcd_weekly );
						$sqm_weekly_labels = $build_labels_from_epochs(
							$extract_summary_series_fallback( $lcd_weekly, array( 'period_start_epoch', 'period_end_epoch' ) ),
							$format_date_only
						);
						$sqm_weekly_chart_options = array(
							'labels'             => $sqm_weekly_labels,
							'show_series_labels' => true,
						);
						foreach ( $sqm_metrics as $metric ) {
							$sqm_weekly_series = $build_summary_series_set( $lcd_weekly, $metric['summary_roots'] );
							if ( empty( $sqm_weekly_series ) ) {
								continue;
							}
							$chart_options = $sqm_weekly_chart_options;
							if ( ! empty( $metric['value_format'] ) ) {
								$chart_options['value_format'] = $metric['value_format'];
							}
							$sqm_weekly_has_chart = true;
							$render_chart(
								$metric['title'],
								$sqm_weekly_series,
								$format_date_only( $sqm_weekly_start ),
								$format_date_only( $sqm_weekly_end ),
								null,
								$chart_options
							);
						}
						if ( ! $sqm_weekly_has_chart ) {
							esc_html_e( 'No SQM weekly summaries available.', 'wpweewx' );
						}
						?>
					</div>
				</div>

				<div class="weewx-weather__tab-panel" data-tab-panel="<?php echo esc_attr( $lcd_tabs_id . '-monthly' ); ?>" role="tabpanel">
					<div class="weewx-weather__chart-grid">
						<?php
						$sqm_monthly_has_chart = false;
						list( $sqm_monthly_start, $sqm_monthly_end ) = $summary_range( $lcd_monthly );
						$sqm_monthly_labels = $build_labels_from_epochs(
							$extract_summary_series_fallback( $lcd_monthly, array( 'period_start_epoch', 'period_end_epoch' ) ),
							$format_date_only
						);
						$sqm_monthly_chart_options = array(
							'labels'             => $sqm_monthly_labels,
							'show_series_labels' => true,
						);
						foreach ( $sqm_metrics as $metric ) {
							$sqm_monthly_series = $build_summary_series_set( $lcd_monthly, $metric['summary_roots'] );
							if ( empty( $sqm_monthly_series ) ) {
								continue;
							}
							$chart_options = $sqm_monthly_chart_options;
							if ( ! empty( $metric['value_format'] ) ) {
								$chart_options['value_format'] = $metric['value_format'];
							}
							$sqm_monthly_has_chart = true;
							$render_chart(
								$metric['title'],
								$sqm_monthly_series,
								$format_date_only( $sqm_monthly_start ),
								$format_date_only( $sqm_monthly_end ),
								null,
								$chart_options
							);
						}
						if ( ! $sqm_monthly_has_chart ) {
							esc_html_e( 'No SQM monthly summaries available.', 'wpweewx' );
						}
						?>
					</div>
				</div>

				<div class="weewx-weather__tab-panel" data-tab-panel="<?php echo esc_attr( $lcd_tabs_id . '-yearly' ); ?>" role="tabpanel">
					<div class="weewx-weather__chart-grid">
						<?php
						$sqm_yearly_has_chart = false;
						list( $sqm_yearly_start, $sqm_yearly_end ) = $summary_range( $lcd_yearly );
						$sqm_yearly_labels = $build_labels_from_epochs(
							$extract_summary_series_fallback( $lcd_yearly, array( 'period_start_epoch', 'period_end_epoch' ) ),
							$format_date_only
						);
						$sqm_yearly_chart_options = array(
							'labels'             => $sqm_yearly_labels,
							'show_series_labels' => true,
						);
						foreach ( $sqm_metrics as $metric ) {
							$sqm_yearly_series = $build_summary_series_set( $lcd_yearly, $metric['summary_roots'] );
							if ( empty( $sqm_yearly_series ) ) {
								continue;
							}
							$chart_options = $sqm_yearly_chart_options;
							if ( ! empty( $metric['value_format'] ) ) {
								$chart_options['value_format'] = $metric['value_format'];
							}
							$sqm_yearly_has_chart = true;
							$render_chart(
								$metric['title'],
								$sqm_yearly_series,
								$format_date_only( $sqm_yearly_start ),
								$format_date_only( $sqm_yearly_end ),
								null,
								$chart_options
							);
						}
						if ( ! $sqm_yearly_has_chart ) {
							esc_html_e( 'No SQM yearly summaries available.', 'wpweewx' );
						}
						?>
					</div>
				</div>
				<?php
				$sqm_main_html = ob_get_clean();
				?>
				<div class="weewx-weather__full-span">
					<?php WPWeeWX_Renderer::card( __( 'SQM', 'wpweewx' ), $sqm_main_html ); ?>
				</div>
			<?php endif; ?>
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
							function syncPanels(target, activeButton) {
								if (!target) {
									return;
								}
								buttons.forEach(function (btn) {
									var isActive = btn === activeButton || btn.getAttribute('data-tab') === target;
									btn.classList.toggle('is-active', isActive);
									btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
								});
								panels.forEach(function (panel) {
									panel.classList.toggle('is-active', panel.getAttribute('data-tab-panel') === target);
								});
							}

							var initial = root.querySelector('.weewx-weather__tab-button.is-active');
							if (initial) {
								syncPanels(initial.getAttribute('data-tab'), initial);
							}

							buttons.forEach(function (button) {
								button.addEventListener('click', function () {
									syncPanels(button.getAttribute('data-tab'), button);
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
			function syncPanels(target, activeButton) {
				if (!target) {
					return;
				}
				buttons.forEach(function (btn) {
					var isActive = btn === activeButton || btn.getAttribute('data-tab') === target;
					btn.classList.toggle('is-active', isActive);
					btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
				});
				panels.forEach(function (panel) {
					panel.classList.toggle('is-active', panel.getAttribute('data-tab-panel') === target);
				});
			}

			var initial = root.querySelector('.weewx-weather__tab-button.is-active');
			if (initial) {
				syncPanels(initial.getAttribute('data-tab'), initial);
			}

			buttons.forEach(function (button) {
				button.addEventListener('click', function () {
					syncPanels(button.getAttribute('data-tab'), button);
				});
			});
		})();
	</script>
</div>
