<?php
/**
 * Admin notification email template
 *
 * This template can be overridden by copying it to 
 * yourtheme/templates/emails/cartbounty-admin-email-notification.php or
 * yourtheme/templates/cartbounty-admin-email-notification.php or
 * yourtheme/cartbounty-admin-email-notification.php

 * If a new CartBounty version is released with an updated template file, you
 * may need to replace the old template file with the new one to maintain compatibility.

 * @package    CartBounty - Save and recover abandoned carts for WooCommerce/Templates
 * @author     Streamline.lv
 * @version    7.1.2.3
 */

if (!defined( 'ABSPATH' )){ //Don't allow direct access
	exit;
}
$position = is_rtl() ? 'right' : 'left';
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html <?php language_attributes(); ?>>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<title><?php echo esc_html( $args['heading'] ); ?></title>
	<style type="text/css">
		#outlook a {
			padding: 0;
		}

		body{
			width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; margin: 0; padding: 0;
		}

		@media only screen and (max-width: 650px) {

			h1{
				font-size: 22px !important;
			}

			.cartbounty-email-contents,
			.cartbounty-email-title,
			.cartbounty-email-carts{
				padding-left: 10px !important;
				padding-right: 10px !important;
			}

			.cartbounty-email-carts td{
				font-size: 13px !important;
			}
		}
	</style>
</head>
<body>
	<table cellpadding="0" cellspacing="0" border="0" style="background-color: <?php echo esc_attr( $args['background_color'] ); ?>; width: 100%; height: 100%; line-height: 100%; margin: 0; padding: 0; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
		<tr>
			<td style="padding: 30px 10px 20px; margin: 0; width: 100%; height: 100%;">
				<table cellpadding="0" cellspacing="0" border="0" align="center" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
					<tr>
						<td valign="top" style="background-color: <?php echo esc_attr( $args['button_color'] ); ?>; padding: 40px 0; border-top-left-radius: 6px;border-top-right-radius: 6px; border-bottom-right-radius: 0; border-bottom-left-radius: 0;">
							<table cellpadding="0" cellspacing="0" border="0" align="center" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
								<tr>
									<td valign="top" width="650">
										<table cellpadding="0" cellspacing="0" border="0" align="center" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
											<tr>
												<td class="cartbounty-email-title" width="650" valign="top" style="text-align: center; padding: 0 50px;">
													<?php do_action('cartbounty_admin_email_before_title'); ?>
													<?php echo apply_filters( 'cartbounty_admin_email_title_html', sprintf('<h1 style="color: %s; font-size: 28px; line-height: 1.3; letter-spacing: normal; font-weight: 600; margin: 0; padding: 0; display: block; font-family: \'Open Sans\', Roboto, \'San Francisco\', Arial, Helvetica, sans-serif;">%s</h1>', esc_attr( $args['main_color'] ), esc_html( $args['heading'] ) ) );?>
													<?php do_action('cartbounty_admin_email_after_title'); ?>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td valign="top" style="background-color: <?php echo esc_attr( $args['main_color'] ); ?>; padding: 20px 0 50px; border-bottom: 2px solid <?php echo esc_attr( $args['border_color'] );?>;">
							<table cellpadding="0" cellspacing="0" border="0" align="center" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
								<tr>
									<td valign="top" width="650">
										<table cellpadding="0" cellspacing="0" border="0" align="center" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
											<tr>
												<td class="cartbounty-email-contents" width="650" valign="top" style="text-align: center; padding: 0 50px;">
													<?php echo apply_filters( 'cartbounty_admin_email_intro_html', sprintf('<p style="color: %s; font-size: 16px; line-height: 1.3; margin: 10px 0; padding: 0; font-family: \'Open Sans\', Roboto, \'San Francisco\', Arial, Helvetica, sans-serif;">%s</p>', esc_attr( $args['text_color'] ), wp_kses_post( $args['content'] ) ) );?>
													<?php do_action('cartbounty_admin_email_after_intro'); ?>
												</td>
											</tr>
										</table>
										<table cellpadding="0" cellspacing="0" border="0" align="center" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
											<tr>
												<td class="cartbounty-email-carts" width="650" valign="top" style="text-align: center; padding: 20px 50px 0;">
													<table cellpadding="0" cellspacing="0" border="0" align="<?php echo $position; ?>" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; opacity: .3;">
														<tr>
															<td valign="top" style="background-color: <?php echo esc_attr( $args['border_color'] ); ?>; text-align: <?php echo $position; ?>; padding: 13px 5px; padding-<?php echo $position; ?>: 15px; color: <?php echo esc_attr( $args['text_color'] );?>; font-size: 17px; line-height: 1.3; font-family: 'Open Sans', Roboto, 'San Francisco', Arial, Helvetica, sans-serif;"><strong><?php _e('Email', 'woo-save-abandoned-carts'); ?></strong></td>
															<td valign="top" style="background-color: <?php echo esc_attr( $args['border_color'] ); ?>; text-align: <?php echo $position; ?>; padding: 13px 5px; color: <?php echo esc_attr( $args['text_color'] );?>; font-size: 17px; line-height: 1.3; font-family: 'Open Sans', Roboto, 'San Francisco', Arial, Helvetica, sans-serif;"><strong><?php _e('Phone', 'woo-save-abandoned-carts'); ?></strong></td>
															<td valign="top" style="background-color: <?php echo esc_attr( $args['border_color'] ); ?>; text-align: <?php echo $position; ?>; padding: 13px 15px; padding-<?php echo $position; ?>: 5px; color: <?php echo esc_attr( $args['text_color'] );?>; font-size: 17px; line-height: 1.3; font-family: 'Open Sans', Roboto, 'San Francisco', Arial, Helvetica, sans-serif;"><strong><?php _e('Total', 'woo-save-abandoned-carts'); ?></strong></td>
														</tr>
														<tr>
															<td valign="top" style="text-align: <?php echo $position; ?>; padding: 13px 5px; padding-<?php echo $position; ?>: 15px; color: <?php echo esc_attr( $args['text_color'] );?>; font-size: 16px; line-height: 1.3; font-family: 'Open Sans', Roboto, 'San Francisco', Arial, Helvetica, sans-serif; border-bottom: 1px solid <?php echo esc_attr( $args['border_color'] ); ?>;"><a href="#" style="text-decoration:none; color:<?php echo esc_attr( $args['text_color'] ); ?>" rel="nofollow">an*****@demo.com</a></td>
															<td valign="top" style="text-align: <?php echo $position; ?>; padding: 13px 5px; color: <?php echo esc_attr( $args['text_color'] );?>; font-size: 16px; line-height: 1.3; font-family: 'Open Sans', Roboto, 'San Francisco', Arial, Helvetica, sans-serif; border-bottom: 1px solid <?php echo esc_attr( $args['border_color'] ); ?>;">+1378*****</td>
															<td valign="top" style="text-align: <?php echo $position; ?>; padding: 13px 15px; padding-<?php echo $position; ?>: 5px; color: <?php echo esc_attr( $args['text_color'] );?>; font-size: 16px; line-height: 1.3; font-family: 'Open Sans', Roboto, 'San Francisco', Arial, Helvetica, sans-serif; border-bottom: 1px solid <?php echo esc_attr( $args['border_color'] ); ?>;"><?php echo esc_html( $args['total_1'] ); ?></td>
														</tr>
														<tr>
															<td valign="top" style="text-align: <?php echo $position; ?>; padding: 13px 5px; padding-<?php echo $position; ?>: 15px; color: <?php echo esc_attr( $args['text_color'] );?>; font-size: 16px; line-height: 1.3; font-family: 'Open Sans', Roboto, 'San Francisco', Arial, Helvetica, sans-serif; border-bottom: 1px solid <?php echo esc_attr( $args['border_color']); ?>;"><a href="#" style="text-decoration:none; color:<?php echo esc_attr( $args['text_color'] ); ?>" rel="nofollow">mu*****@demo.com</a></td>
															<td valign="top" style="text-align: <?php echo $position; ?>; padding: 13px 5px; color: <?php echo esc_attr( $args['text_color'] );?>; font-size: 16px; line-height: 1.3; font-family: 'Open Sans', Roboto, 'San Francisco', Arial, Helvetica, sans-serif; border-bottom: 1px solid <?php echo esc_attr( $args['border_color'] ); ?>;">+1761*****</td>
															<td valign="top" style="text-align: <?php echo $position; ?>; padding: 13px 15px; padding-<?php echo $position; ?>: 5px; color: <?php echo esc_attr( $args['text_color'] );?>; font-size: 16px; line-height: 1.3; font-family: 'Open Sans', Roboto, 'San Francisco', Arial, Helvetica, sans-serif; border-bottom: 1px solid <?php echo esc_attr( $args['border_color'] ); ?>;"><?php echo esc_html( $args['total_2'] ); ?></td>
														</tr>
														<tr>
															<td valign="top" style="text-align: <?php echo $position; ?>; padding: 13px 5px; padding-<?php echo $position; ?>: 15px; color: <?php echo esc_attr( $args['text_color'] );?>; font-size: 16px; line-height: 1.3; font-family: 'Open Sans', Roboto, 'San Francisco', Arial, Helvetica, sans-serif; border-bottom: 1px solid <?php echo esc_attr( $args['border_color'] ); ?>;"><a href="#" style="text-decoration:none; color:<?php echo esc_attr( $args['text_color'] ); ?>" rel="nofollow">ja*****@demo.com</a></td>
															<td valign="top" style="text-align: <?php echo $position; ?>; padding: 13px 5px; color: <?php echo esc_attr( $args['text_color'] );?>; font-size: 16px; line-height: 1.3; font-family: 'Open Sans', Roboto, 'San Francisco', Arial, Helvetica, sans-serif; border-bottom: 1px solid <?php echo esc_attr( $args['border_color'] ); ?>;">-</td>
															<td valign="top" style="text-align: <?php echo $position; ?>; padding: 13px 15px; padding-<?php echo $position; ?>: 5px; color: <?php echo esc_attr( $args['text_color'] );?>; font-size: 16px; line-height: 1.3; font-family: 'Open Sans', Roboto, 'San Francisco', Arial, Helvetica, sans-serif; border-bottom: 1px solid <?php echo esc_attr( $args['border_color'] ); ?>;"><?php echo esc_html( $args['total_3'] ); ?></td>
														</tr>
													</table>
													<table cellpadding="0" cellspacing="0" border="0" align="<?php echo $position; ?>" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;">
														<tr>
															<td valign="top" style="text-align: center; padding: 30px 0 0; font-size: 16px;">
																<?php echo apply_filters( 'cartbounty_admin_email_get_pro', sprintf('<p style="color: %s; font-size: 16px; line-height: 1.3; margin: 0; padding: 0; font-family: \'Open Sans\', Roboto, \'San Francisco\', Arial, Helvetica, sans-serif;">%s</p>', esc_attr( $args['text_color'] ), wp_kses_post( $args['get_pro_text'] ) ) );?>
															</td>
														</tr>
													</table>
												</td>
											</tr>
										</table>
										<table cellpadding="0" cellspacing="0" border="0" align="center" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
											<tr>
												<td valign="top" style="padding: 30px 0 0; text-align: center;">
													<table cellpadding="0" cellspacing="0" border="0" align="center" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
														<tr>
															<td width="650" valign="middle" style="mso-line-height-rule: exactly; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; padding: 0 50px; text-align: center;">
																<?php echo apply_filters( 'cartbounty_admin_email_button_html', sprintf('<a href="%1$s" title="%4$s" style="margin: 0; outline: none; padding: 0; box-shadow: none;"><span style="padding: 18px 35px; background-color: %3$s; border-radius: 4px; color: %2$s; font-family: \'Open Sans\', Roboto, \'San Francisco\', Arial, Helvetica, sans-serif; display:inline-block; border: 0px none; font-size: 17px; font-weight: bold; line-height: 1; letter-spacing: normal; text-align: center; text-decoration: none; outline: none;">%4$s</span></a>', esc_url($args['carts_link']), esc_attr( $args['main_color'] ), esc_attr( $args['button_color'] ), esc_html__( 'View all carts', 'woo-save-abandoned-carts' ) ) );?>
																<?php do_action('cartbounty_admin_email_after_button'); ?>
															</td>
														</tr>
													</table>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td valign="top" style="padding-top: 30px;">
							<table cellpadding="0" cellspacing="0" border="0" align="center" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
								<tr>
									<td class="cartbounty-email-footer" width="650" valign="top" style="text-align: center; font-size: 12px; line-height: 1.3; color: <?php echo esc_attr( $args['footer_color'] ); ?>; font-family: 'Open Sans', Roboto, 'San Francisco', Arial, Helvetica, sans-serif;">
										<?php do_action('cartbounty_admin_email_footer_end'); ?>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>