<?php
/**
 * Settings registration and helpers.
 *
 * @package WPWeeWX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPWeeWX_Settings {
	/**
	 * Option defaults.
	 *
	 * @var array<string, mixed>
	 */
	private static $defaults = array(
		'wpweewx_json_url_main'   => '',
		'wpweewx_json_url_simple' => '',
		'wpweewx_json_url_lcd'    => '',
		'wpweewx_default_source'  => 'main',
		'wpweewx_cache_ttl'       => 300,
		'wpweewx_http_timeout'    => 8,
		'wpweewx_default_view'    => 'dashboard',
		'wpweewx_default_theme'   => 'auto',
	);

	/**
	 * Register settings.
	 */
	public static function register() {
		register_setting(
			'wpweewx_settings',
			'wpweewx_json_url_main',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => self::$defaults['wpweewx_json_url_main'],
			)
		);

		register_setting(
			'wpweewx_settings',
			'wpweewx_json_url_simple',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => self::$defaults['wpweewx_json_url_simple'],
			)
		);

		register_setting(
			'wpweewx_settings',
			'wpweewx_json_url_lcd',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => self::$defaults['wpweewx_json_url_lcd'],
			)
		);

		register_setting(
			'wpweewx_settings',
			'wpweewx_default_source',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_source' ),
				'default'           => self::$defaults['wpweewx_default_source'],
			)
		);

		register_setting(
			'wpweewx_settings',
			'wpweewx_cache_ttl',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( __CLASS__, 'sanitize_positive_int' ),
				'default'           => self::$defaults['wpweewx_cache_ttl'],
			)
		);

		register_setting(
			'wpweewx_settings',
			'wpweewx_http_timeout',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( __CLASS__, 'sanitize_positive_int' ),
				'default'           => self::$defaults['wpweewx_http_timeout'],
			)
		);

		register_setting(
			'wpweewx_settings',
			'wpweewx_default_view',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_view' ),
				'default'           => self::$defaults['wpweewx_default_view'],
			)
		);

		register_setting(
			'wpweewx_settings',
			'wpweewx_default_theme',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_theme' ),
				'default'           => self::$defaults['wpweewx_default_theme'],
			)
		);
	}

	/**
	 * Get option with default.
	 *
	 * @param string $key Option key.
	 * @return mixed
	 */
	public static function get( $key ) {
		if ( array_key_exists( $key, self::$defaults ) ) {
			return get_option( $key, self::$defaults[ $key ] );
		}

		return get_option( $key );
	}

	/**
	 * Sanitize source.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	public static function sanitize_source( $value ) {
		$value = is_string( $value ) ? $value : '';
		return in_array( $value, array( 'main', 'simple', 'lcd' ), true ) ? $value : 'main';
	}

	/**
	 * Sanitize view.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	public static function sanitize_view( $value ) {
		$value = is_string( $value ) ? $value : '';
		return in_array( $value, array( 'dashboard', 'current', 'summary' ), true ) ? $value : 'dashboard';
	}

	/**
	 * Sanitize theme.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	public static function sanitize_theme( $value ) {
		$value = is_string( $value ) ? $value : '';
		return in_array( $value, array( 'auto', 'light', 'dark' ), true ) ? $value : 'auto';
	}

	/**
	 * Sanitize positive integers.
	 *
	 * @param mixed $value Value.
	 * @return int
	 */
	public static function sanitize_positive_int( $value ) {
		$value = is_numeric( $value ) ? (int) $value : 0;
		return ( $value > 0 ) ? $value : 1;
	}
}
