<?php
/**
 * The template for displaying CartBounty - Save and recover abandoned carts for WooCommerce Exit Intent form
 *
 * This template can be overridden by copying it to 
 * yourtheme/templates/cartbounty-exit-intent.php or
 * yourtheme/cartbounty-exit-intent.php

 * Please do not change the ID "cartbounty-exit-intent-email".
 * If changed, the abandoned cart will not be captured
 *
 * If CartBounty - Save and recover abandoned carts for WooCommerce plugin will need to update template files, you
 * might need to copy the new files to your theme to maintain compatibility.
 * 
 * @package    CartBounty - Save and recover abandoned carts for WooCommerce/Templates
 * @author     Streamline.lv
 * @version    6.1.3
 */

if (!defined( 'ABSPATH' )){ //Don't allow direct access
	exit;
}
$public = new CartBounty_Public(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
$image_id = esc_attr( get_option('cartbounty_exit_intent_image'));
$image_url = $public->get_plugin_url() . '/public/assets/abandoned-shopping-cart.gif';
if($image_id){
	$image = wp_get_attachment_image_src( $image_id, 'full' );
	if(is_array($image)){
		$image_url = $image[0];
	}
}
?>

<div id="cartbounty-exit-intent-form" class="cartbounty-ei-center">
	<div id="cartbounty-exit-intent-form-container" style="background-color:<?php echo $args['main_color']; ?>">
		<div id="cartbounty-exit-intent-close">
			<?php echo apply_filters( 'cartbounty_exit_intent_close_html', sprintf('<svg><line x1="1" y1="11" x2="11" y2="1" stroke="%s" stroke-width="2"/><line x1="1" y1="1" x2="11" y2="11" stroke="%s" stroke-width="2"/></svg>', $args['inverse_color'], $args['inverse_color'] ) ); ?>
		</div>
		<div id="cartbounty-exit-intent-form-content">
			<?php do_action('cartbounty_exit_intent_start'); ?>
			<div id="cartbounty-exit-intent-form-content-l">
				<?php echo wp_kses_post( apply_filters( 'cartbounty_exit_intent_image_html', sprintf('<img src="%s" alt="" title=""/>', $image_url ) ) ); ?>
			</div>
			<div id="cartbounty-exit-intent-form-content-r">
				<?php echo wp_kses_post( apply_filters( 'cartbounty_exit_intent_title_html', sprintf(
					/* translators: %s - Color code */
					__( '<h2 style="color: %s">You were not leaving your cart just like that, right?</h2>', 'woo-save-abandoned-carts' ), $args['inverse_color'] ) ) ); ?>
				<?php do_action('cartbounty_exit_intent_after_title'); ?>
				<?php echo wp_kses_post( apply_filters( 'cartbounty_exit_intent_description_html', sprintf(
					/* translators: %s - Color code */
					__( '<p style="color: %s">Enter your details below to save your shopping cart for later. And, who knows, maybe we will even send you a sweet discount code :)</p>', 'woo-save-abandoned-carts' ), $args['inverse_color'] ) ) );?>
				<form>
					<?php do_action('cartbounty_exit_intent_before_form_fields');
					echo apply_filters( 'cartbounty_exit_intent_field_html', '<input type="email" id="cartbounty-exit-intent-email" required placeholder="'. __('Enter your email', 'woo-save-abandoned-carts') .'">' );
					echo wp_kses_post( apply_filters( 'cartbounty_exit_intent_button_html', sprintf('<button type="submit" name="cartbounty-exit-intent-submit" id="cartbounty-exit-intent-submit" class="button" value="submit" style="background-color: %s; color: %s">%s</button>', $args['inverse_color'], $args['main_color'], __('Save cart', 'woo-save-abandoned-carts') ) ) ); ?>
				</form>
			</div>
			<?php do_action('cartbounty_exit_intent_end'); ?>
		</div>
	</div>
	<div id="cartbounty-exit-intent-form-backdrop" style="background-color:<?php echo $args['inverse_color']; ?>; opacity: 0;"></div>
</div>