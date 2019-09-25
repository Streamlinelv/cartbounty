<?php
/**
 * The template for displaying CartBounty - Save and recover abandoned carts for WooCommerce Exit Intent form
 *
 * This template can be overridden by copying it to 
 * yourtheme/templates/cartbounty-exit-intent.php or
 * yourtheme/cartbounty-exit-intent.php

 * Please note that you should not change the ID (cartbounty-exit-intent-email) of the Email input field.
 * If changed, the abandoned cart will not be captured
 *
 * If CartBounty - Save and recover abandoned carts for WooCommerce plugin will need to update template files, you
 * might need to copy the new files to your theme to maintain compatibility.
 * 
 * @package    CartBounty - Save and recover abandoned carts for WooCommerce/Templates
 * @author     Streamline.lv
 * @version    3.0
 */

if (!defined( 'ABSPATH' )){ //Don't allow direct access
	exit;
}
$public = new CartBounty_Public(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);

?>

<div id="cartbounty-exit-intent-form" class="cartbounty-ei-center">
	<div id="cartbounty-exit-intent-form-container" style="background-color:<?php echo $args['main_color']; ?>">
		<div id="cartbounty-exit-intent-close">
			<?php echo apply_filters( 'cartbounty_exit_intent_close_html', sprintf('<svg><line x1="1" y1="11" x2="11" y2="1" stroke="%s" stroke-width="2"/><line x1="1" y1="1" x2="11" y2="11" stroke="%s" stroke-width="2"/></svg>', $args['inverse_color'], $args['inverse_color'] ) ); ?>
		</div>
		<div id="cartbounty-exit-intent-form-content">
			<?php do_action('cartbounty_exit_intent_start'); ?>
			<div id="cartbounty-exit-intent-form-content-l">
				<?php echo wp_kses_post( apply_filters( 'cartbounty_exit_intent_image_html', sprintf('<img src="%s" alt="" title=""/>', $public->get_plugin_url() . '/public/assets/abandoned-shopping-cart.gif' ) ) ); ?>
			</div>
			<div id="cartbounty-exit-intent-form-content-r">
				<?php echo wp_kses_post( apply_filters( 'cartbounty_exit_intent_title_html', sprintf(__( '<h2 style="color: %s" >You were not leaving your cart just like that, right?</h2>', CARTBOUNTY_TEXT_DOMAIN ), $args['inverse_color'] ) ) ); ?>
				<?php do_action('cartbounty_exit_intent_after_title'); ?>
				<?php echo wp_kses_post( apply_filters( 'cartbounty_exit_intent_description_html', sprintf(__( '<p style="color: %s" >Just enter your email below to save your shopping cart for later. And, who knows, maybe we will even send you a sweet discount code :)</p>', CARTBOUNTY_TEXT_DOMAIN ), $args['inverse_color'] ) ) );?>
				<form>
					<?php do_action('cartbounty_exit_intent_before_form_fields'); ?>
					<?php echo wp_kses_post( apply_filters( 'cartbounty_exit_intent_email_label_html', sprintf('<label for="cartbounty-exit-intent-email" style="color: %s">%s</label>', $args['inverse_color'], __('Your email:', CARTBOUNTY_TEXT_DOMAIN) ) ) ); ?>
					<?php echo apply_filters( 'cartbounty_exit_intent_email_field_html', '<input type="email" id="cartbounty-exit-intent-email" size="30" required >' ) ; ?>
					<?php echo wp_kses_post( apply_filters( 'cartbounty_exit_intent_button_html', sprintf('<button type="submit" name="cartbounty-exit-intent-submit" id="cartbounty-exit-intent-submit" class="button" value="submit" style="background-color: %s; color: %s">%s</button>', $args['inverse_color'], $args['main_color'], __('Save cart', CARTBOUNTY_TEXT_DOMAIN) ) ) ); ?>
				</form>
			</div>
			<?php do_action('cartbounty_exit_intent_end'); ?>
		</div>
	</div>
	<div id="cartbounty-exit-intent-form-backdrop" style="background-color:<?php echo $args['inverse_color']; ?>; opacity: 0;"></div>
</div>