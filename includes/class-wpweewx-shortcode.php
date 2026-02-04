<?php
/**
 * Shortcode registration.
 *
 * @package WPWeeWX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPWeeWX_Shortcode {
	/**
	 * Register shortcode.
	 */
	public static function register() {
		add_shortcode( 'weewx_weather', array( __CLASS__, 'handle' ) );
	}

	/**
	 * Shortcode handler.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function handle( $atts ) {
		$defaults = array(
			'source'  => WPWeeWX_Settings::get( 'wpweewx_default_source' ),
			'view'    => WPWeeWX_Settings::get( 'wpweewx_default_view' ),
			'theme'   => WPWeeWX_Settings::get( 'wpweewx_default_theme' ),
			'show'    => '',
		);

		$atts = shortcode_atts( $defaults, $atts, 'weewx_weather' );

		$source = WPWeeWX_Settings::sanitize_source( $atts['source'] );
		$view   = WPWeeWX_Settings::sanitize_view( $atts['view'] );
		$theme  = WPWeeWX_Settings::sanitize_theme( $atts['theme'] );

		$payload = WPWeeWX_Fetcher::get_data( $source, true );
		if ( empty( $payload['data'] ) ) {
			$message = isset( $payload['error'] ) ? $payload['error'] : __( 'Unable to load weather data.', 'wpweewx' );
			return '<div class="weewx-weather weewx-weather--error">' . esc_html( $message ) . '</div>';
		}

		return WPWeeWX_Renderer::render(
			$view,
			$payload,
			array(
				'source' => $source,
				'theme'  => $theme,
				'show'   => $atts['show'],
			)
		);
	}
}
