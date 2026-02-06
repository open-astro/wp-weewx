<?php
/**
 * Admin UI and actions.
 *
 * @package WPWeeWX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPWeeWX_Admin {
	/**
	 * Hook admin actions.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_post_wpweewx_test_fetch', array( __CLASS__, 'handle_test_fetch' ) );
	}

	/**
	 * Register settings menu.
	 */
	public static function register_menu() {
		add_options_page(
			__( 'WeeWX Weather Settings', 'wpweewx' ),
			__( 'WeeWX Weather', 'wpweewx' ),
			'manage_options',
			'wpweewx-settings',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public static function register_settings() {
		WPWeeWX_Settings::register();
	}

	/**
	 * Handle test fetch action.
	 */
	public static function handle_test_fetch() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wpweewx' ) );
		}

		check_admin_referer( 'wpweewx_test_fetch' );

		$source = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : 'main';
		if ( ! in_array( $source, array( 'main', 'simple', 'lcd' ), true ) ) {
			$source = 'main';
		}

		$result = WPWeeWX_Fetcher::test_fetch( $source );
		set_transient( 'wpweewx_test_result', $result, 60 );

		$redirect = add_query_arg(
			array(
				'page'         => 'wpweewx-settings',
				'wpweewx_test' => $source,
			),
			admin_url( 'options-general.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Render settings page.
	 */
	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$test_source = isset( $_GET['wpweewx_test'] ) ? sanitize_text_field( wp_unslash( $_GET['wpweewx_test'] ) ) : '';
		$test_result = get_transient( 'wpweewx_test_result' );
		delete_transient( 'wpweewx_test_result' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WeeWX Weather Settings', 'wpweewx' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'wpweewx_settings' );
				do_settings_sections( 'wpweewx_settings' );
				?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Simple JSON URL', 'wpweewx' ); ?></th>
						<td>
							<input type="url" class="regular-text" name="wpweewx_json_url_simple" value="<?php echo esc_attr( WPWeeWX_Settings::get( 'wpweewx_json_url_simple' ) ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Main JSON URL', 'wpweewx' ); ?></th>
						<td>
							<input type="url" class="regular-text" name="wpweewx_json_url_main" value="<?php echo esc_attr( WPWeeWX_Settings::get( 'wpweewx_json_url_main' ) ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'LCD Datasheet JSON URL', 'wpweewx' ); ?></th>
						<td>
							<input type="url" class="regular-text" name="wpweewx_json_url_lcd" value="<?php echo esc_attr( WPWeeWX_Settings::get( 'wpweewx_json_url_lcd' ) ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'LCD Extra Temp 1 Label', 'wpweewx' ); ?></th>
						<td>
							<input type="text" class="regular-text" name="wpweewx_lcd_extra_temp1_label" value="<?php echo esc_attr( WPWeeWX_Settings::get( 'wpweewx_lcd_extra_temp1_label' ) ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'LCD Extra Temp 2 Label', 'wpweewx' ); ?></th>
						<td>
							<input type="text" class="regular-text" name="wpweewx_lcd_extra_temp2_label" value="<?php echo esc_attr( WPWeeWX_Settings::get( 'wpweewx_lcd_extra_temp2_label' ) ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'LCD Extra Temp 3 Label', 'wpweewx' ); ?></th>
						<td>
							<input type="text" class="regular-text" name="wpweewx_lcd_extra_temp3_label" value="<?php echo esc_attr( WPWeeWX_Settings::get( 'wpweewx_lcd_extra_temp3_label' ) ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Default Source', 'wpweewx' ); ?></th>
						<td>
							<select name="wpweewx_default_source">
								<option value="simple" <?php selected( WPWeeWX_Settings::get( 'wpweewx_default_source' ), 'simple' ); ?>><?php esc_html_e( 'Simple', 'wpweewx' ); ?></option>
								<option value="main" <?php selected( WPWeeWX_Settings::get( 'wpweewx_default_source' ), 'main' ); ?>><?php esc_html_e( 'Main', 'wpweewx' ); ?></option>
								<option value="lcd" <?php selected( WPWeeWX_Settings::get( 'wpweewx_default_source' ), 'lcd' ); ?>><?php esc_html_e( 'LCD', 'wpweewx' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Cache TTL (seconds)', 'wpweewx' ); ?></th>
						<td>
							<input type="number" name="wpweewx_cache_ttl" min="1" value="<?php echo esc_attr( WPWeeWX_Settings::get( 'wpweewx_cache_ttl' ) ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'HTTP Timeout (seconds)', 'wpweewx' ); ?></th>
						<td>
							<input type="number" name="wpweewx_http_timeout" min="1" value="<?php echo esc_attr( WPWeeWX_Settings::get( 'wpweewx_http_timeout' ) ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Default View', 'wpweewx' ); ?></th>
						<td>
							<select name="wpweewx_default_view">
								<option value="dashboard" <?php selected( WPWeeWX_Settings::get( 'wpweewx_default_view' ), 'dashboard' ); ?>><?php esc_html_e( 'Dashboard', 'wpweewx' ); ?></option>
								<option value="current" <?php selected( WPWeeWX_Settings::get( 'wpweewx_default_view' ), 'current' ); ?>><?php esc_html_e( 'Current', 'wpweewx' ); ?></option>
								<option value="summary" <?php selected( WPWeeWX_Settings::get( 'wpweewx_default_view' ), 'summary' ); ?>><?php esc_html_e( 'Summary', 'wpweewx' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Temperature Unit', 'wpweewx' ); ?></th>
						<td>
							<select name="wpweewx_temp_unit">
								<option value="f" <?php selected( WPWeeWX_Settings::get( 'wpweewx_temp_unit' ), 'f' ); ?>><?php esc_html_e( 'Fahrenheit (F)', 'wpweewx' ); ?></option>
								<option value="c" <?php selected( WPWeeWX_Settings::get( 'wpweewx_temp_unit' ), 'c' ); ?>><?php esc_html_e( 'Celsius (C)', 'wpweewx' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Default Theme', 'wpweewx' ); ?></th>
						<td>
							<select name="wpweewx_default_theme">
								<option value="auto" <?php selected( WPWeeWX_Settings::get( 'wpweewx_default_theme' ), 'auto' ); ?>><?php esc_html_e( 'Auto', 'wpweewx' ); ?></option>
								<option value="light" <?php selected( WPWeeWX_Settings::get( 'wpweewx_default_theme' ), 'light' ); ?>><?php esc_html_e( 'Light', 'wpweewx' ); ?></option>
								<option value="dark" <?php selected( WPWeeWX_Settings::get( 'wpweewx_default_theme' ), 'dark' ); ?>><?php esc_html_e( 'Dark', 'wpweewx' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<h2><?php esc_html_e( 'Test Fetch', 'wpweewx' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'wpweewx_test_fetch' ); ?>
				<input type="hidden" name="action" value="wpweewx_test_fetch" />
				<p>
					<button type="submit" class="button" name="source" value="simple"><?php esc_html_e( 'Test Fetch (Simple JSON)', 'wpweewx' ); ?></button>
					<button type="submit" class="button" name="source" value="main"><?php esc_html_e( 'Test Fetch (Main JSON)', 'wpweewx' ); ?></button>
					<button type="submit" class="button" name="source" value="lcd"><?php esc_html_e( 'Test Fetch (LCD JSON)', 'wpweewx' ); ?></button>
				</p>
			</form>

			<?php if ( $test_source && is_array( $test_result ) ) : ?>
				<h3><?php echo esc_html( sprintf( __( 'Test Result (%s)', 'wpweewx' ), $test_source ) ); ?></h3>
				<table class="widefat striped">
					<tbody>
						<tr>
							<th><?php esc_html_e( 'HTTP Status', 'wpweewx' ); ?></th>
							<td><?php echo esc_html( (string) $test_result['http_status'] ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Fetch Duration (s)', 'wpweewx' ); ?></th>
							<td><?php echo esc_html( $test_result['duration'] ? number_format_i18n( $test_result['duration'], 3 ) : '—' ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Generation Time', 'wpweewx' ); ?></th>
							<td><?php echo esc_html( $test_result['generation_time'] ? (string) $test_result['generation_time'] : '—' ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Detected Sections', 'wpweewx' ); ?></th>
							<td><?php echo esc_html( ! empty( $test_result['sections'] ) ? implode( ', ', $test_result['sections'] ) : '—' ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Current Metrics', 'wpweewx' ); ?></th>
							<td><?php echo esc_html( ! empty( $test_result['metrics'] ) ? implode( ', ', $test_result['metrics'] ) : '—' ); ?></td>
						</tr>
						<?php if ( ! empty( $test_result['error'] ) ) : ?>
							<tr>
								<th><?php esc_html_e( 'Error', 'wpweewx' ); ?></th>
								<td><?php echo esc_html( $test_result['error'] ); ?></td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}
