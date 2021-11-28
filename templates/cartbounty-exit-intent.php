<?php
/**
 * The template for displaying CartBounty Exit Intent form
 *
 * This template can be overridden by copying it to 
 * yourtheme/templates/cartbounty-exit-intent.php or
 * yourtheme/cartbounty-exit-intent.php

 * Please do not change the ID "cartbounty-exit-intent-email".
 * If changed, the abandoned cart will not be captured
 *
 * If a new CartBounty version is released with an updated template file, you
 * may need to replace the old template file with the new one to maintain compatibility.
 * 
 * @package    CartBounty - Save and recover abandoned carts for WooCommerce/Templates
 * @author     Streamline.lv
 * @version    7.1
 */

if (!defined( 'ABSPATH' )){ //Don't allow direct access
	exit;
}?>

<div id="cartbounty-exit-intent-form" class="cartbounty-ei-center">
	<div id="cartbounty-exit-intent-form-container" style="background-color:<?php echo esc_attr( $args['main_color'] ); ?>">
		<div id="cartbounty-exit-intent-close">
			<?php echo apply_filters( 'cartbounty_exit_intent_close_html', sprintf('<svg><line x1="1" y1="11" x2="11" y2="1" stroke="%s" stroke-width="2"/><line x1="1" y1="1" x2="11" y2="11" stroke="%s" stroke-width="2"/></svg>', esc_attr( $args['inverse_color'] ), esc_attr( $args['inverse_color'] ) ) ); ?>
		</div>
		<div id="cartbounty-exit-intent-form-content">
			<?php do_action('cartbounty_exit_intent_start'); ?>
			<div id="cartbounty-exit-intent-form-content-l">
				<?php echo wp_kses_post( apply_filters( 'cartbounty_exit_intent_image_html', sprintf('<img src="%s" />', esc_url( $args['image_url'] ) ) ) ); ?>
			</div>
			<div id="cartbounty-exit-intent-form-content-r">
				<?php echo wp_kses_post( apply_filters( 'cartbounty_exit_intent_title_html', sprintf(
					/* translators: %s - Color code */
					__( '<h2 style="color: %s">%s</h2>', 'woo-save-abandoned-carts' ), esc_attr( $args['inverse_color'] ), $args['heading'] ) ) ); ?>
				<?php do_action('cartbounty_exit_intent_after_title'); ?>
				<?php echo wp_kses_post( apply_filters( 'cartbounty_exit_intent_description_html', sprintf(
					/* translators: %s - Color code */
					__( '<p style="color: %s">%s</p>', 'woo-save-abandoned-carts' ), esc_attr( $args['inverse_color'] ), $args['content'] ) ) );?>
				<form>
					<?php do_action('cartbounty_exit_intent_before_form_fields');
					echo apply_filters( 'cartbounty_exit_intent_field_html', '<input type="email" id="cartbounty-exit-intent-email" required placeholder="'. esc_attr__('Enter your email', 'woo-save-abandoned-carts') .'">' );
					echo wp_kses_post( apply_filters( 'cartbounty_exit_intent_button_html', sprintf('<button type="submit" name="cartbounty-exit-intent-submit" id="cartbounty-exit-intent-submit" class="button" value="submit" style="background-color: %s; color: %s">%s</button>', esc_attr( $args['inverse_color'] ), esc_attr( $args['main_color'] ), esc_html__('Save', 'woo-save-abandoned-carts') ) ) ); ?>
				</form>
			</div>
			<?php do_action('cartbounty_exit_intent_end'); ?>
		</div>
	</div>
	<div id="cartbounty-exit-intent-form-backdrop" style="background-color:<?php echo esc_attr( $args['inverse_color'] ); ?>; opacity: 0;"></div>
</div>