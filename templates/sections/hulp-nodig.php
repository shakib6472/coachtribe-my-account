<?php
/**
 * Zijkaart: Hulp nodig? (dashboard).
 *
 * @package CoachTribe_My_Account
 */

defined( 'ABSPATH' ) || exit;

$ct_hulp_email = apply_filters( 'coachtribe_my_account_support_email', 'info@coachtribe.nl' );
$ct_hulp_phone = apply_filters( 'coachtribe_my_account_support_phone_display', '020 - 261 07 07' );
$ct_hulp_tel   = apply_filters( 'coachtribe_my_account_support_phone_tel', '+31202610707' );
$ct_hulp_mailto = 'mailto:' . $ct_hulp_email;
?>
<aside class="ct-account-hulp-card" aria-labelledby="ct-account-hulp-card-title">
	<div class="ct-account-hulp-card__head">
		<span class="ct-account-hulp-card__head-icon" aria-hidden="true">
			<svg class="ct-account-hulp-card__head-svg" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
				<path d="M6.5 9a2.5 2.5 0 015 0v3a2.5 2.5 0 01-5 0V9z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
				<path d="M9 12h4.5a2.5 2.5 0 012.5 2.5v.5a2 2 0 01-2 2H11" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
				<path d="M12 3v1M5 12H4M20 12h-1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
			</svg>
		</span>
		<h2 id="ct-account-hulp-card-title" class="ct-account-hulp-card__title"><?php esc_html_e( 'Hulp nodig?', 'coachtribe-my-account' ); ?></h2>
	</div>
	<p class="ct-account-hulp-card__intro"><?php esc_html_e( 'Onze klantenservice staat voor je klaar.', 'coachtribe-my-account' ); ?></p>
	<ul class="ct-account-hulp-card__list">
		<li class="ct-account-hulp-card__item">
			<span class="ct-account-hulp-card__icon" aria-hidden="true">
				<svg class="ct-account-hulp-card__svg" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
					<path d="M4 6h16v12H4V6z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
					<path d="M4 8l8 6 8-6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</span>
			<a class="ct-account-hulp-card__link" href="<?php echo esc_url( $ct_hulp_mailto ); ?>"><?php echo esc_html( $ct_hulp_email ); ?></a>
		</li>
		<li class="ct-account-hulp-card__item">
			<span class="ct-account-hulp-card__icon" aria-hidden="true">
				<svg class="ct-account-hulp-card__svg" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
					<path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</span>
			<a class="ct-account-hulp-card__link" href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $ct_hulp_tel ) ); ?>"><?php echo esc_html( $ct_hulp_phone ); ?></a>
		</li>
	</ul>
</aside>
