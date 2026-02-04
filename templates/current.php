<?php
/**
 * Current view template.
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
?>

<div class="weewx-weather weewx-weather--<?php echo esc_attr( $theme ); ?>">
	<div class="weewx-weather__header">
		<div>
			<h2 class="weewx-weather__title"><?php echo esc_html( $station_name ); ?></h2>
			<div class="weewx-weather__meta">
				<span class="weewx-weather__meta-item">
					<?php echo esc_html( sprintf( __( 'Updated: %s', 'wpweewx' ), $generation_date ) ); ?>
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

	<div class="weewx-weather__grid weewx-weather__grid--single">
		<?php
		ob_start();
		WPWeeWX_Renderer::metric_row( __( 'Temperature', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.temperature' ) );
		WPWeeWX_Renderer::metric_row( __( 'Dew Point', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.dewpoint' ) );
		WPWeeWX_Renderer::metric_row( __( 'Humidity', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.humidity' ) );
		WPWeeWX_Renderer::metric_row( __( 'Barometer', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.barometer' ) );
		WPWeeWX_Renderer::metric_row( __( 'Wind', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.wind speed' ) );
		WPWeeWX_Renderer::metric_row( __( 'Wind Gust', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.wind gust' ) );
		WPWeeWX_Renderer::metric_row( __( 'Wind Direction', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.wind direction' ) );
		WPWeeWX_Renderer::metric_row( __( 'Rain Rate', 'wpweewx' ), WPWeeWX_Renderer::display_value( $data, 'current.rain rate' ) );
		$current_html = ob_get_clean();
		WPWeeWX_Renderer::card( __( 'Current Conditions', 'wpweewx' ), $current_html );
		?>
	</div>
</div>
