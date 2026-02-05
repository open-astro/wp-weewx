<?php
/**
 * Remote fetch and caching.
 *
 * @package WPWeeWX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPWeeWX_Fetcher {
	/**
	 * Fetch data for a source, with caching.
	 *
	 * @param string $source main|simple.
	 * @param bool   $force  Force refresh.
	 * @return array<string, mixed>
	 */
	public static function get_data( $source, $force = false ) {
		$source = WPWeeWX_Settings::sanitize_source( $source );
		$cache_key = self::get_cache_key( $source );

		if ( ! $force ) {
			$cached = get_transient( $cache_key );
			if ( is_array( $cached ) && isset( $cached['data'] ) ) {
				$cached['cache_used'] = true;
				return $cached;
			}
		}

		$live = self::fetch_live( $source, $force );

		if ( isset( $live['data'] ) ) {
			set_transient( $cache_key, $live, WPWeeWX_Settings::get( 'wpweewx_cache_ttl' ) );
			$live['cache_used'] = false;
			return $live;
		}

		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) && isset( $cached['data'] ) ) {
			$cached['cache_used'] = true;
			$cached['warning']    = $live['error'];
			return $cached;
		}

		return $live;
	}

	/**
	 * Fetch LCD datasheet JSON with caching.
	 *
	 * @param bool $force Force refresh.
	 * @return array<string, mixed>
	 */
	public static function get_lcd_data( $force = false ) {
		$cache_key = self::get_lcd_cache_key();

		if ( ! $force ) {
			$cached = get_transient( $cache_key );
			if ( is_array( $cached ) && isset( $cached['data'] ) ) {
				$cached['cache_used'] = true;
				return $cached;
			}
		}

		$live = self::fetch_lcd_live( $force );

		if ( isset( $live['data'] ) ) {
			set_transient( $cache_key, $live, WPWeeWX_Settings::get( 'wpweewx_cache_ttl' ) );
			$live['cache_used'] = false;
			return $live;
		}

		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) && isset( $cached['data'] ) ) {
			$cached['cache_used'] = true;
			$cached['warning']    = $live['error'];
			return $cached;
		}

		return $live;
	}

	/**
	 * Perform a live fetch.
	 *
	 * @param string $source Source.
	 * @param bool   $force  Force refresh.
	 * @return array<string, mixed>
	 */
	public static function fetch_live( $source, $force = false ) {
		$source = WPWeeWX_Settings::sanitize_source( $source );
		$url    = ( 'simple' === $source )
			? WPWeeWX_Settings::get( 'wpweewx_json_url_simple' )
			: WPWeeWX_Settings::get( 'wpweewx_json_url_main' );

		if ( empty( $url ) ) {
			return array(
				'error' => __( 'JSON URL is not configured.', 'wpweewx' ),
			);
		}

		$parsed_url = wp_parse_url( $url );
		if ( empty( $parsed_url['scheme'] ) || ! in_array( $parsed_url['scheme'], array( 'http', 'https' ), true ) ) {
			return array(
				'error' => __( 'Invalid URL scheme. Use http or https.', 'wpweewx' ),
			);
		}

		if ( $force ) {
			$url = add_query_arg( 'wpweewx_ts', time(), $url );
		}

		$args = array(
			'timeout' => WPWeeWX_Settings::get( 'wpweewx_http_timeout' ),
		);

		$start = microtime( true );
		$response = wp_remote_get( $url, $args );
		$duration = microtime( true ) - $start;

		if ( is_wp_error( $response ) ) {
			return array(
				'error'       => $response->get_error_message(),
				'http_status' => 0,
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( 200 !== $code ) {
			return array(
				'error'       => sprintf( __( 'Unexpected HTTP status: %d', 'wpweewx' ), (int) $code ),
				'http_status' => $code,
			);
		}

		$body    = self::normalize_lcd_json_body( $body );
		$decoded = json_decode( $body, true );
		if ( null === $decoded || ! is_array( $decoded ) ) {
			return array(
				'error'       => sprintf( __( 'Invalid JSON response: %s', 'wpweewx' ), json_last_error_msg() ),
				'http_status' => $code,
			);
		}

		return array(
			'fetched_at'  => time(),
			'http_status' => $code,
			'duration'    => $duration,
			'data'        => $decoded,
		);
	}

	/**
	 * Perform a live fetch for LCD datasheet JSON.
	 *
	 * @param bool $force Force refresh.
	 * @return array<string, mixed>
	 */
	public static function fetch_lcd_live( $force = false ) {
		$url = WPWeeWX_Settings::get( 'wpweewx_json_url_lcd' );

		if ( empty( $url ) ) {
			return array(
				'error' => __( 'LCD JSON URL is not configured.', 'wpweewx' ),
			);
		}

		$parsed_url = wp_parse_url( $url );
		if ( empty( $parsed_url['scheme'] ) || ! in_array( $parsed_url['scheme'], array( 'http', 'https' ), true ) ) {
			return array(
				'error' => __( 'Invalid URL scheme. Use http or https.', 'wpweewx' ),
			);
		}

		if ( $force ) {
			$url = add_query_arg( 'wpweewx_ts', time(), $url );
		}

		$args = array(
			'timeout' => WPWeeWX_Settings::get( 'wpweewx_http_timeout' ),
		);

		$start = microtime( true );
		$response = wp_remote_get( $url, $args );
		$duration = microtime( true ) - $start;

		if ( is_wp_error( $response ) ) {
			return array(
				'error'       => $response->get_error_message(),
				'http_status' => 0,
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( 200 !== $code ) {
			return array(
				'error'       => sprintf( __( 'Unexpected HTTP status: %d', 'wpweewx' ), (int) $code ),
				'http_status' => $code,
			);
		}

		$body    = self::normalize_lcd_json_body( $body );
		$decoded = json_decode( $body, true );
		if ( null === $decoded || ! is_array( $decoded ) ) {
			return array(
				'error'       => sprintf( __( 'Invalid JSON response: %s', 'wpweewx' ), json_last_error_msg() ),
				'http_status' => $code,
			);
		}

		return array(
			'fetched_at'  => time(),
			'http_status' => $code,
			'duration'    => $duration,
			'data'        => $decoded,
		);
	}

	/**
	 * Build cache key.
	 *
	 * @param string $source Source.
	 * @return string
	 */
	private static function get_cache_key( $source ) {
		return ( 'simple' === $source ) ? 'wpweewx_cache_simple' : 'wpweewx_cache_main';
	}

	/**
	 * Build LCD cache key.
	 *
	 * @return string
	 */
	private static function get_lcd_cache_key() {
		return 'wpweewx_cache_lcd';
	}

	/**
	 * Normalize LCD JSON with missing values.
	 *
	 * Some generators emit empty values like ", ," or '"min": ,'.
	 * Convert those to explicit nulls so JSON decoding succeeds.
	 *
	 * @param string $body Raw body.
	 * @return string
	 */
	private static function normalize_lcd_json_body( $body ) {
		if ( ! is_string( $body ) || '' === $body ) {
			return $body;
		}

		$normalized = preg_replace( '/:\s*,/', ': null,', $body );
		$normalized = preg_replace( '/,\s*,/', ', null,', $normalized );
		$normalized = preg_replace( '/:\s*(?=[}\]])/', ': null', $normalized );
		$normalized = preg_replace( '/,\s*(?=[}\]])/', ', null', $normalized );
		$normalized = preg_replace( '/,\s*]/', ', null]', $normalized );
		$normalized = preg_replace( '/,\s*}/', ', null}', $normalized );

		return $normalized;
	}

	/**
	 * Test fetch diagnostic output.
	 *
	 * @param string $source Source.
	 * @return array<string, mixed>
	 */
	public static function test_fetch( $source ) {
		$result = ( 'lcd' === $source ) ? self::fetch_lcd_live() : self::fetch_live( $source );

		$sections = array();
		$metrics  = array();
		$generation_time = null;

		if ( isset( $result['data'] ) && is_array( $result['data'] ) ) {
			$sections = array_keys( $result['data'] );

			if ( 'lcd' === $source ) {
				if ( isset( $result['data']['lcd_datasheet']['daily_captures']['fields'] ) && is_array( $result['data']['lcd_datasheet']['daily_captures']['fields'] ) ) {
					$metrics = $result['data']['lcd_datasheet']['daily_captures']['fields'];
				}
			} elseif ( isset( $result['data']['current'] ) && is_array( $result['data']['current'] ) ) {
				$metrics = array_keys( $result['data']['current'] );
			}

			if ( isset( $result['data']['generation']['time'] ) ) {
				$generation_time = $result['data']['generation']['time'];
			} elseif ( isset( $result['data']['generation'] ) ) {
				$generation_time = $result['data']['generation'];
			}
		}

		return array(
			'http_status'     => isset( $result['http_status'] ) ? $result['http_status'] : 0,
			'duration'        => isset( $result['duration'] ) ? $result['duration'] : null,
			'generation_time' => $generation_time,
			'sections'        => $sections,
			'metrics'         => $metrics,
			'error'           => isset( $result['error'] ) ? $result['error'] : null,
		);
	}
}
