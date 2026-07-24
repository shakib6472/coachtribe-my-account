<?php
/**
 * Sectie: Snelle acties (drie kaarten — horizontale rij, ref. layout).
 *
 * @package CoachTribe_My_Account
 */

defined( 'ABSPATH' ) || exit;

$ct_quick_facturen = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'facturen' ) : home_url( '/' );
$ct_quick_betalen  = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'payment-methods' ) : home_url( '/' );
$ct_quick_abonnement = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'subscriptions' ) : home_url( '/' );

$ct_quick_facturen   = apply_filters( 'coachtribe_my_account_quick_link_facturen', $ct_quick_facturen );
$ct_quick_betalen    = apply_filters( 'coachtribe_my_account_quick_link_payment_methods', $ct_quick_betalen );
$ct_quick_abonnement = apply_filters( 'coachtribe_my_account_quick_link_subscriptions', $ct_quick_abonnement );
?>
<section class="ct-account-snella-acties ct-account-snella-acties--saas ct-account-snella-acties--ref" aria-labelledby="ct-account-snella-acties-title">
	<h2 id="ct-account-snella-acties-title" class="ct-account-snella-acties__title screen-reader-text"><?php esc_html_e( 'Snelle Acties', 'coachtribe-my-account' ); ?></h2>

	<div class="ct-account-snella-acties__grid">
		<a class="ct-account-snella-acties__card ct-account-snella-acties__card--saas ct-account-snella-acties__card--ref" href="<?php echo esc_url( $ct_quick_facturen ); ?>" data-ct-tab="facturen">
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

		<a class="ct-account-snella-acties__card ct-account-snella-acties__card--saas ct-account-snella-acties__card--ref" href="<?php echo esc_url( $ct_quick_betalen ); ?>" data-ct-tab="payment-methods">
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

		<a class="ct-account-snella-acties__card ct-account-snella-acties__card--saas ct-account-snella-acties__card--ref" href="<?php echo esc_url( $ct_quick_abonnement ); ?>" data-ct-tab="subscriptions">
			<span class="ct-account-snella-acties__icon-ring" aria-hidden="true">
				<svg class="ct-account-snella-acties__svg" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
					<path d="M4 4v5h5M20 20v-5h-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					<path d="M20 9a8 8 0 00-14.9-3M4 15a8 8 0 0014.9 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</span>
			<span class="ct-account-snella-acties__content">
				<span class="ct-account-snella-acties__card-title"><?php esc_html_e( 'Abonnement beheren', 'coachtribe-my-account' ); ?></span>
				<span class="ct-account-snella-acties__card-text"><?php esc_html_e( 'Wijzig of annuleer je abonnement', 'coachtribe-my-account' ); ?></span>
			</span>
			<span class="ct-account-snella-acties__chevron" aria-hidden="true">
				<svg class="ct-account-snella-acties__chevron-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
					<path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</span>
		</a>
	</div>
</section>
