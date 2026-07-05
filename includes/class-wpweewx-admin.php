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
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_action( 'admin_post_wpweewx_test_fetch', array( __CLASS__, 'handle_test_fetch' ) );
	}

	/**
	 * Enqueue admin styles on the plugin settings page.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public static function enqueue_admin_assets( $hook_suffix ) {
		if ( 'settings_page_wpweewx-settings' !== $hook_suffix ) {
			return;
		}
		wp_enqueue_style(
			'wpweewx-admin',
			WPWEEWX_PLUGIN_URL . 'assets/css/wpweewx-admin.css',
			array(),
			WPWEEWX_VERSION
		);
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
		$test_ok = is_array( $test_result ) && ( empty( $test_result['error'] ) ) && ( isset( $test_result['http_status'] ) && $test_result['http_status'] >= 200 && $test_result['http_status'] < 300 );
		?>
		<div class="wrap wpweewx-admin-wrap">
			<h1 class="wpweewx-admin-title"><?php esc_html_e( 'WeeWX Weather Settings', 'wpweewx' ); ?></h1>
			<p class="wpweewx-admin-description"><?php esc_html_e( 'Configure data sources and display options for your WeeWX weather shortcode.', 'wpweewx' ); ?></p>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'wpweewx_settings' );
				do_settings_sections( 'wpweewx_settings' );
				?>

				<div class="wpweewx-admin-card">
					<div class="wpweewx-admin-card__header"><?php esc_html_e( 'Data sources', 'wpweewx' ); ?></div>
					<div class="wpweewx-admin-card__body">
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><?php esc_html_e( 'Conditions Current URL', 'wpweewx' ); ?></th>
								<td>
									<input type="url" class="regular-text" name="wpweewx_json_url_simple" value="<?php echo esc_attr( WPWeeWX_Settings::get( 'wpweewx_json_url_simple' ) ); ?>" placeholder="https://..." />
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Conditions Summary URL', 'wpweewx' ); ?></th>
								<td>
									<input type="url" class="regular-text" name="wpweewx_json_url_main" value="<?php echo esc_attr( WPWeeWX_Settings::get( 'wpweewx_json_url_main' ) ); ?>" placeholder="https://..." />
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Conditions Dataset URL', 'wpweewx' ); ?></th>
								<td>
									<input type="url" class="regular-text" name="wpweewx_json_url_lcd" value="<?php echo esc_attr( WPWeeWX_Settings::get( 'wpweewx_json_url_lcd' ) ); ?>" placeholder="https://..." />
								</td>
							</tr>
						</table>
					</div>
				</div>

				<div class="wpweewx-admin-card">
					<div class="wpweewx-admin-card__header"><?php esc_html_e( 'Dataset options', 'wpweewx' ); ?></div>
					<div class="wpweewx-admin-card__body">
						<table class="form-table" role="presentation">
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
								<th scope="row"><?php esc_html_e( 'Show SQM Data', 'wpweewx' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="wpweewx_show_sqm" value="1" <?php checked( WPWeeWX_Settings::get( 'wpweewx_show_sqm' ), 1 ); ?> />
										<?php esc_html_e( 'Display SQM metrics and charts (if available).', 'wpweewx' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Default Source', 'wpweewx' ); ?></th>
								<td>
									<select name="wpweewx_default_source">
										<option value="simple" <?php selected( WPWeeWX_Settings::get( 'wpweewx_default_source' ), 'simple' ); ?>><?php esc_html_e( 'Conditions Current', 'wpweewx' ); ?></option>
										<option value="main" <?php selected( WPWeeWX_Settings::get( 'wpweewx_default_source' ), 'main' ); ?>><?php esc_html_e( 'Conditions Summary', 'wpweewx' ); ?></option>
										<option value="lcd" <?php selected( WPWeeWX_Settings::get( 'wpweewx_default_source' ), 'lcd' ); ?>><?php esc_html_e( 'Conditions Dataset', 'wpweewx' ); ?></option>
									</select>
								</td>
							</tr>
						</table>
					</div>
				</div>

				<div class="wpweewx-admin-card">
					<div class="wpweewx-admin-card__header"><?php esc_html_e( 'Caching & network', 'wpweewx' ); ?></div>
					<div class="wpweewx-admin-card__body">
						<table class="form-table" role="presentation">
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
						</table>
					</div>
				</div>

				<div class="wpweewx-admin-card">
					<div class="wpweewx-admin-card__header"><?php esc_html_e( 'Display', 'wpweewx' ); ?></div>
					<div class="wpweewx-admin-card__body">
						<table class="form-table" role="presentation">
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
								<th scope="row"><?php esc_html_e( 'Station Data Unit', 'wpweewx' ); ?></th>
								<td>
									<select name="wpweewx_source_temp_unit">
										<option value="f" <?php selected( WPWeeWX_Settings::get( 'wpweewx_source_temp_unit' ), 'f' ); ?>><?php esc_html_e( 'Fahrenheit (F)', 'wpweewx' ); ?></option>
										<option value="c" <?php selected( WPWeeWX_Settings::get( 'wpweewx_source_temp_unit' ), 'c' ); ?>><?php esc_html_e( 'Celsius (C)', 'wpweewx' ); ?></option>
									</select>
									<p class="description"><?php esc_html_e( 'The unit your WeeWX station reports temperatures in. Used for data feeds without unit metadata (e.g. the dataset charts). Set this to Celsius for metric stations.', 'wpweewx' ); ?></p>
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
					</div>
				</div>

				<div class="wpweewx-admin-actions">
					<?php submit_button(); ?>
				</div>
			</form>

			<div class="wpweewx-admin-card">
				<div class="wpweewx-admin-card__header"><?php esc_html_e( 'Test connections', 'wpweewx' ); ?></div>
				<div class="wpweewx-admin-card__body">
					<p class="description" style="margin-top: 0;"><?php esc_html_e( 'Verify that each URL returns valid JSON. Results appear below.', 'wpweewx' ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'wpweewx_test_fetch' ); ?>
						<input type="hidden" name="action" value="wpweewx_test_fetch" />
						<div class="wpweewx-admin-test-buttons">
							<button type="submit" class="button" name="source" value="simple"><?php esc_html_e( 'Test Current', 'wpweewx' ); ?></button>
							<button type="submit" class="button" name="source" value="main"><?php esc_html_e( 'Test Summary', 'wpweewx' ); ?></button>
							<button type="submit" class="button" name="source" value="lcd"><?php esc_html_e( 'Test Dataset', 'wpweewx' ); ?></button>
						</div>
					</form>

					<?php if ( $test_source && is_array( $test_result ) ) : ?>
						<div class="wpweewx-admin-result">
							<div class="wpweewx-admin-result-header">
								<span class="wpweewx-admin-status wpweewx-admin-status--<?php echo $test_ok ? 'success' : 'error'; ?>">
									<?php echo $test_ok ? esc_html__( 'OK', 'wpweewx' ) : esc_html__( 'Failed', 'wpweewx' ); ?>
								</span>
								<span><?php echo esc_html( sprintf( __( 'Result for %s', 'wpweewx' ), $test_source ) ); ?></span>
							</div>
							<table class="widefat striped wpweewx-admin-result-table">
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
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}
}
