<?php
/**
 * Support-banner onderaan tab-container (twee regels + info-icoon).
 *
 * @package CoachTribe_My_Account
 */

defined( 'ABSPATH' ) || exit;

$ct_support_email = apply_filters( 'coachtribe_my_account_support_email', 'info@coachtribe.nl' );
$ct_support_phone = apply_filters( 'coachtribe_my_account_support_phone_display', '020 - 261 07 07' );
$ct_support_tel   = apply_filters( 'coachtribe_my_account_support_phone_tel', '+31202610707' );

$ct_support_mailto   = 'mailto:' . $ct_support_email;
$ct_support_tel_href = 'tel:' . preg_replace( '/[^0-9+]/', '', $ct_support_tel );
?>
<section
	class="ct-account-support-footer ct-account-support-footer--saas"
	aria-label="<?php esc_attr_e( 'Klantenservice voor abonnement en factuur', 'coachtribe-my-account' ); ?>"
>
	<div class="ct-account-support-footer__row">
		<div class="ct-account-support-footer__info-badge" aria-hidden="true">
			<span class="ct-account-support-footer__info-letter">i</span>
		</div>
		<p class="ct-account-support-footer__message">
			<?php esc_html_e( 'Vragen over je abonnement of factuur? Neem contact op met onze klantenservice via ', 'coachtribe-my-account' ); ?>
			<a class="ct-account-support-footer__inline-link" href="<?php echo esc_url( $ct_support_mailto ); ?>"><?php echo esc_html( $ct_support_email ); ?></a>
			<?php esc_html_e( ' of ', 'coachtribe-my-account' ); ?>
			<a class="ct-account-support-footer__inline-link" href="<?php echo esc_url( $ct_support_tel_href ); ?>"><?php echo esc_html( $ct_support_phone ); ?></a><?php esc_html_e( '.', 'coachtribe-my-account' ); ?>
		</p>
	</div>
</section>
