<?php
/**
 * Rendering helpers.
 *
 * @package WPWeeWX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPWeeWX_Renderer {
	/**
	 * Render a view template.
	 *
	 * @param string $view   View name.
	 * @param array  $payload Fetch payload.
	 * @param array  $args   Context args.
	 * @return string
	 */
	public static function render( $view, $payload, $args ) {
		$view = WPWeeWX_Settings::sanitize_view( $view );
		$theme = WPWeeWX_Settings::sanitize_theme( $args['theme'] );
		$source = WPWeeWX_Settings::sanitize_source( $args['source'] );

		$data = isset( $payload['data'] ) && is_array( $payload['data'] ) ? $payload['data'] : array();
		$warning = isset( $payload['warning'] ) ? $payload['warning'] : '';
		$cache_used = ! empty( $payload['cache_used'] );
		$lcd_payload = isset( $args['lcd_payload'] ) ? $args['lcd_payload'] : null;

		$show_panels = array();
		if ( ! empty( $args['show'] ) && is_string( $args['show'] ) ) {
			$raw_panels = array_map( 'trim', explode( ',', $args['show'] ) );
			$show_panels = array_filter( $raw_panels );
		}

		$template = WPWEEWX_PLUGIN_DIR . 'templates/' . $view . '.php';
		if ( ! file_exists( $template ) ) {
			return '<div class="weewx-weather weewx-weather--error">' . esc_html__( 'Template not found.', 'wpweewx' ) . '</div>';
		}

		ob_start();
		include $template;
		return ob_get_clean();
	}

	/**
	 * Return a display-safe value.
	 *
	 * @param array  $data Parsed JSON.
	 * @param string $path Dot path.
	 * @return string
	 */
	public static function display_value( $data, $path ) {
		$value = WPWeeWX_Parser::get( $data, $path );
		if ( null === $value || '' === $value ) {
			return '—';
		}

		if ( is_array( $value ) ) {
			if ( isset( $value['value'] ) ) {
				$unit_key = isset( $value['units'] ) ? 'units' : ( isset( $value['unit'] ) ? 'unit' : '' );
				$unit = $unit_key ? $value[ $unit_key ] : '';
				$number = is_numeric( $value['value'] ) ? number_format_i18n( (float) $value['value'], 2 ) : $value['value'];
				return trim( $number . ' ' . $unit );
			}

			$values = array_values( $value );
			if ( count( $values ) >= 2 && is_scalar( $values[0] ) ) {
				return trim( (string) $values[0] . ' ' . (string) $values[1] );
			}
		}

		if ( is_scalar( $value ) ) {
			if ( is_numeric( $value ) ) {
				return number_format_i18n( (float) $value, 2 );
			}
			return (string) $value;
		}

		return '—';
	}

	/**
	 * Render an inline SVG sparkline.
	 *
	 * @param array $series  Series values or array of series definitions.
	 * @param array $options Options like width, height, padding.
	 * @return string
	 */
	public static function sparkline( $series, $options = array() ) {
		$width   = isset( $options['width'] ) ? (int) $options['width'] : 260;
		$height  = isset( $options['height'] ) ? (int) $options['height'] : 80;
		$padding = isset( $options['padding'] ) ? (int) $options['padding'] : 6;

		$series_list = array();
		if ( isset( $options['series'] ) && is_array( $options['series'] ) ) {
			$series_list = $options['series'];
		} elseif ( is_array( $series ) ) {
			$series_list = array(
				array(
					'values' => $series,
				),
			);
		}

		$all_values = array();
		foreach ( $series_list as $item ) {
			if ( empty( $item['values'] ) || ! is_array( $item['values'] ) ) {
				continue;
			}
			foreach ( $item['values'] as $value ) {
				if ( is_numeric( $value ) ) {
					$all_values[] = (float) $value;
				}
			}
		}

		if ( empty( $all_values ) ) {
			return '<div class="weewx-weather__chart-empty">' . esc_html__( 'No data', 'wpweewx' ) . '</div>';
		}

		$min = min( $all_values );
		$max = max( $all_values );
		if ( isset( $options['min'] ) && is_numeric( $options['min'] ) ) {
			$min = (float) $options['min'];
		}
		if ( isset( $options['max'] ) && is_numeric( $options['max'] ) ) {
			$max = (float) $options['max'];
		}
		$range = ( $max - $min );
		if ( 0.0 === $range ) {
			$range = 1.0;
		}

		$inner_width  = $width - ( 2 * $padding );
		$inner_height = $height - ( 2 * $padding );

		$paths = array();
		foreach ( $series_list as $item ) {
			$values = isset( $item['values'] ) && is_array( $item['values'] ) ? $item['values'] : array();
			if ( empty( $values ) ) {
				continue;
			}

			$count = count( $values );
			$step  = ( $count > 1 ) ? ( $inner_width / ( $count - 1 ) ) : 0;
			$d = '';
			$started = false;

			foreach ( $values as $index => $value ) {
				if ( ! is_numeric( $value ) ) {
					$started = false;
					continue;
				}
				$x = $padding + ( $step * $index );
				$y = $padding + $inner_height - ( ( (float) $value - $min ) / $range * $inner_height );

				if ( ! $started ) {
					$d .= 'M ' . $x . ' ' . $y . ' ';
					$started = true;
				} else {
					$d .= 'L ' . $x . ' ' . $y . ' ';
				}
			}

			if ( '' === $d ) {
				continue;
			}

			$class = 'weewx-weather__sparkline-series';
			if ( ! empty( $item['class'] ) ) {
				$class .= ' weewx-weather__sparkline-series--' . sanitize_html_class( $item['class'] );
			}

			$paths[] = sprintf(
				'<path class="%s" d="%s" fill="none" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />',
				esc_attr( $class ),
				esc_attr( trim( $d ) )
			);
		}

		if ( empty( $paths ) ) {
			return '<div class="weewx-weather__chart-empty">' . esc_html__( 'No data', 'wpweewx' ) . '</div>';
		}

		$svg = sprintf(
			'<svg class="weewx-weather__sparkline" viewBox="0 0 %1$d %2$d" role="img" aria-hidden="true">%3$s</svg>',
			(int) $width,
			(int) $height,
			implode( '', $paths )
		);

		$allowed = array(
			'svg'  => array(
				'class'       => true,
				'viewBox'     => true,
				'role'        => true,
				'aria-hidden' => true,
			),
			'path' => array(
				'class' => true,
				'd'     => true,
			),
			'div'  => array(
				'class' => true,
			),
		);

		return wp_kses( $svg, $allowed );
	}

	/**
	 * Render a metric row.
	 *
	 * @param string $label Label.
	 * @param string $value Value.
	 * @return void
	 */
	public static function metric_row( $label, $value ) {
		$path = WPWEEWX_PLUGIN_DIR . 'templates/partials/metric-row.php';
		if ( file_exists( $path ) ) {
			include $path;
		}
	}

	/**
	 * Render a card.
	 *
	 * @param string $title Title.
	 * @param string $content HTML content.
	 * @return void
	 */
	public static function card( $title, $content ) {
		$path = WPWEEWX_PLUGIN_DIR . 'templates/partials/card.php';
		if ( file_exists( $path ) ) {
			include $path;
		}
	}

	/**
	 * Allowed HTML for card content.
	 *
	 * @return array
	 */
	public static function allowed_html() {
		$allowed = wp_kses_allowed_html( 'post' );
		$allowed['svg'] = array(
			'class'       => true,
			'viewBox'     => true,
			'role'        => true,
			'aria-hidden' => true,
		);
		$allowed['path'] = array(
			'class' => true,
			'd'     => true,
			'fill'  => true,
			'stroke-linecap' => true,
			'stroke-linejoin' => true,
			'vector-effect' => true,
		);

		return $allowed;
	}
}
