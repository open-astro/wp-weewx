<?php
/**
 * Card partial.
 *
 * @package WPWeeWX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="weewx-weather__card">
	<h3 class="weewx-weather__card-title"><?php echo esc_html( $title ); ?></h3>
	<div class="weewx-weather__card-body">
		<?php echo wp_kses( $content, WPWeeWX_Renderer::allowed_html() ); ?>
	</div>
</div>
