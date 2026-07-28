<?php
/**
 * Sectie: Snelle acties (drie kaarten — horizontale rij, ref. layout). Member-type-aware:
 *   - woocommerce : invoices, change payment method, cancel subscription (in-app).
 *   - plug_and_pay: all three link to the external Plug&Pay portal.
 *   - gratis      : no quick actions (nothing rendered).
 *
 * @package CoachTribe_My_Account
 */

defined( 'ABSPATH' ) || exit;

$ct_member_type = function_exists( 'coachtribe_my_account_get_member_type' ) ? coachtribe_my_account_get_member_type() : 'woocommerce';

// Free members have no quick cards at all; they cancel via the sidebar "Abonnement opzeggen" tab.
if ( 'gratis' === $ct_member_type ) {
	return;
}
$ct_show_billing_cards = true;

$ct_plugandpay_url = function_exists( 'coachtribe_my_account_plugandpay_url' ) ? coachtribe_my_account_plugandpay_url() : '';
$ct_quick_external = ( 'plug_and_pay' === $ct_member_type && '' !== $ct_plugandpay_url );

if ( $ct_quick_external ) {
	// Plug&Pay members manage invoices, payment and cancellation on the external portal.
	$ct_quick_facturen   = $ct_plugandpay_url;
	$ct_quick_betalen    = $ct_plugandpay_url;
	$ct_quick_abonnement = $ct_plugandpay_url;
} else {
	$ct_quick_facturen   = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'facturen' ) : home_url( '/' );
	$ct_quick_betalen    = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'payment-methods' ) : home_url( '/' );
	$ct_quick_abonnement = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'subscriptions' ) : home_url( '/' );

	$ct_quick_facturen   = apply_filters( 'coachtribe_my_account_quick_link_facturen', $ct_quick_facturen );
	$ct_quick_betalen    = apply_filters( 'coachtribe_my_account_quick_link_payment_methods', $ct_quick_betalen );
	$ct_quick_abonnement = apply_filters( 'coachtribe_my_account_quick_link_subscriptions', $ct_quick_abonnement );
}

// External links open in a new tab and must not be intercepted by the SPA tab router.
$ct_ext_attrs = ' target="_blank" rel="noopener noreferrer"';

// Subscription cancellation always happens inside our own website (never the portal).
$ct_cancel_url = function_exists( 'coachtribe_my_account_cancellation_url' ) ? coachtribe_my_account_cancellation_url() : '';
if ( '' === $ct_cancel_url ) {
	// Fallback when no cancellation page is configured yet (kept in-site, not the portal).
	$ct_cancel_url = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'subscriptions' ) : home_url( '/' );
}
?>
<section class="ct-account-snella-acties ct-account-snella-acties--saas ct-account-snella-acties--ref" aria-labelledby="ct-account-snella-acties-title">
	<h2 id="ct-account-snella-acties-title" class="ct-account-snella-acties__title screen-reader-text"><?php esc_html_e( 'Snelle Acties', 'coachtribe-my-account' ); ?></h2>

	<div class="ct-account-snella-acties__grid">
		<?php if ( $ct_show_billing_cards ) : ?>
		<a class="ct-account-snella-acties__card ct-account-snella-acties__card--saas ct-account-snella-acties__card--ref" href="<?php echo esc_url( $ct_quick_facturen ); ?>"<?php echo $ct_quick_external ? $ct_ext_attrs : ' data-ct-tab="facturen"'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<span class="ct-account-snella-acties__icon-ring" aria-hidden="true">
				<svg class="ct-account-snella-acties__svg" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
					<path d="M12 3v12m0 0l4-4m-4 4L8 11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					<path d="M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
				</svg>
			</span>
			<span class="ct-account-snella-acties__content">
				<span class="ct-account-snella-acties__card-title"><?php esc_html_e( 'Factuur downloaden', 'coachtribe-my-account' ); ?></span>
				<span class="ct-account-snella-acties__card-text"><?php esc_html_e( 'Download je laatste factuur', 'coachtribe-my-account' ); ?></span>
			</span>
			<span class="ct-account-snella-acties__chevron" aria-hidden="true">
				<svg class="ct-account-snella-acties__chevron-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
					<path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</span>
		</a>

		<a class="ct-account-snella-acties__card ct-account-snella-acties__card--saas ct-account-snella-acties__card--ref" href="<?php echo esc_url( $ct_quick_betalen ); ?>"<?php echo $ct_quick_external ? $ct_ext_attrs : ' data-ct-tab="payment-methods"'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<span class="ct-account-snella-acties__icon-ring" aria-hidden="true">
				<svg class="ct-account-snella-acties__svg" width="24" height="24" viewBox="0 0 40 40" focusable="false" xmlns="http://www.w3.org/2000/svg">
					<rect x="6" y="12" width="28" height="18" rx="3" fill="none" stroke="currentColor" stroke-width="2"/>
					<rect x="6" y="16" width="28" height="5" fill="currentColor" opacity="0.35"/>
					<rect x="22" y="24" width="8" height="3" rx="0.5" fill="currentColor"/>
				</svg>
			</span>
			<span class="ct-account-snella-acties__content">
				<span class="ct-account-snella-acties__card-title"><?php esc_html_e( 'Betaalmethode wijzigen', 'coachtribe-my-account' ); ?></span>
				<span class="ct-account-snella-acties__card-text"><?php esc_html_e( 'Werk je betaalgegevens bij', 'coachtribe-my-account' ); ?></span>
			</span>
			<span class="ct-account-snella-acties__chevron" aria-hidden="true">
				<svg class="ct-account-snella-acties__chevron-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
					<path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</span>
		</a>

		<?php endif; ?>
		<a class="ct-account-snella-acties__card ct-account-snella-acties__card--saas ct-account-snella-acties__card--ref" href="<?php echo esc_url( $ct_cancel_url ); ?>">
			<span class="ct-account-snella-acties__icon-ring" aria-hidden="true">
				<svg class="ct-account-snella-acties__svg" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
					<path d="M4 4v5h5M20 20v-5h-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					<path d="M20 9a8 8 0 00-14.9-3M4 15a8 8 0 0014.9 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</span>
			<span class="ct-account-snella-acties__content">
				<span class="ct-account-snella-acties__card-title"><?php esc_html_e( 'Abonnement opzeggen', 'coachtribe-my-account' ); ?></span>
				<span class="ct-account-snella-acties__card-text"><?php esc_html_e( 'Annuleer hier je abonnement', 'coachtribe-my-account' ); ?></span>
			</span>
			<span class="ct-account-snella-acties__chevron" aria-hidden="true">
				<svg class="ct-account-snella-acties__chevron-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
					<path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</span>
		</a>
	</div>
</section>
