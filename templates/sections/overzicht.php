<?php
/**
 * Overzicht (dashboard) — secties voor het account.
 *
 * @package CoachTribe_My_Account
 */

defined( 'ABSPATH' ) || exit;

$ct_sections_dir = COACHTRIBE_MY_ACCOUNT_PATH . 'templates/sections/';

// WooCommerce members get a separate "Mijn persoonlijke gegevens" card; Plug&Pay/Free
// members have all their details combined inside the subscription card itself.
$ct_ov_is_wc_member = ! function_exists( 'coachtribe_my_account_is_woocommerce_member' ) || coachtribe_my_account_is_woocommerce_member();
?>
<div class="ct-account-overzicht ct-account-overzicht--saas">
	<div class="ct-account-overzicht__main">
		<?php
		include $ct_sections_dir . 'subscription.php';
		if ( $ct_ov_is_wc_member ) {
			include $ct_sections_dir . 'profiel.php';
		}
		include $ct_sections_dir . 'snelle-acties.php';
		?>
	</div>
</div>
