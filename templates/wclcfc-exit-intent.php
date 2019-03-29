<?php
/**
 * The template for displaying WooCommerce Live Checkout Field Capture Exit Intent form
 *
 * This template can be overridden by copying it to 
 * yourtheme/templates/wclcfc-exit-intent.php or
 * yourtheme/wclcfc-exit-intent.php

 * Please note that you should not change the ID (wclcfc-exit-intent-email) of the Email input field.
 * If changed, the abandoned cart will not be captured
 *
 * If WooCommerce Live Checkout Field Capture plugin will need to update template files, you
 * might need to copy the new files to your theme to maintain compatibility.
 * 
 * @package    WooCommerce Live Checkout Field Capture/Templates
 * @author     Streamline.lv
 * @version    3.0
 */

if (!defined( 'ABSPATH' )){ //Don't allow direct access
	exit;
}
$public = new Woo_Live_Checkout_Field_Capture_Public(WCLCFC_PLUGIN_NAME_SLUG, WCLCFC_VERSION_NUMBER);

?>

<div id="wclcfc-exit-intent-form" class="wclcfc-ei-center">
	<div id="wclcfc-exit-intent-form-container" style="background-color:<?php echo $args['main_color']; ?>">
		<div id="wclcfc-exit-intent-close">
			<?php echo apply_filters( 'wclcfc_exit_intent_close_html', sprintf('<svg><line x1="1" y1="11" x2="11" y2="1" stroke="%s" stroke-width="2"/><line x1="1" y1="1" x2="11" y2="11" stroke="%s" stroke-width="2"/></svg>', $args['inverse_color'], $args['inverse_color'] ) ); ?>
		</div>
		<div id="wclcfc-exit-intent-form-content">
			<?php do_action('wclcfc_exit_intent_start'); ?>
			<div id="wclcfc-exit-intent-form-content-l">
				<?php echo wp_kses_post( apply_filters( 'wclcfc_exit_intent_image_html', sprintf('<img src="%s" alt="" title=""/>', $public->get_plugin_url() . '/public/assets/abandoned-shopping-cart.gif' ) ) ); ?>
			</div>
			<div id="wclcfc-exit-intent-form-content-r">
				<?php echo wp_kses_post( apply_filters( 'wclcfc_exit_intent_title_html', sprintf(__( '<h2 style="color: %s" >You were not leaving your cart just like that, right?</h2>', WCLCFC_TEXT_DOMAIN ), $args['inverse_color'] ) ) ); ?>
				<?php do_action('wclcfc_exit_intent_after_title'); ?>
				<?php echo wp_kses_post( apply_filters( 'wclcfc_exit_intent_description_html', sprintf(__( '<p style="color: %s" >Just enter your email below to save your shopping cart for later. And, who knows, maybe we will even send you a sweet discount code :)</p>', WCLCFC_TEXT_DOMAIN ), $args['inverse_color'] ) ) );?>
				<form>
					<?php do_action('wclcfc_exit_intent_before_form_fields'); ?>
					<?php echo wp_kses_post( apply_filters( 'wclcfc_exit_intent_email_label_html', sprintf('<label for="wclcfc-exit-intent-email" style="color: %s">%s</label>', $args['inverse_color'], __('Your email:', WCLCFC_TEXT_DOMAIN) ) ) ); ?>
					<?php echo apply_filters( 'wclcfc_exit_intent_email_field_html', '<input type="email" id="wclcfc-exit-intent-email" size="30" required >' ) ; ?>
					<?php echo wp_kses_post( apply_filters( 'wclcfc_exit_intent_button_html', sprintf('<button type="submit" name="wclcfc-exit-intent-submit" id="wclcfc-exit-intent-submit" class="button" value="submit" style="background-color: %s; color: %s">%s</button>', $args['inverse_color'], $args['main_color'], __('Save cart', WCLCFC_TEXT_DOMAIN) ) ) ); ?>
				</form>
			</div>
			<?php do_action('wclcfc_exit_intent_end'); ?>
		</div>
	</div>
	<div id="wclcfc-exit-intent-form-backdrop" style="background-color:<?php echo $args['inverse_color']; ?>; opacity: 0;"></div>
</div>