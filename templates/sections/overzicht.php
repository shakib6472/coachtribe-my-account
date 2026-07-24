<?php
/**
 * Overzicht (dashboard) — secties voor het account.
 *
 * @package CoachTribe_My_Account
 */

defined( 'ABSPATH' ) || exit;

$ct_sections_dir = COACHTRIBE_MY_ACCOUNT_PATH . 'templates/sections/';
?>
<div class="ct-account-overzicht ct-account-overzicht--saas">
	<div class="ct-account-overzicht__main">
		<?php
		include $ct_sections_dir . 'subscription.php';
		include $ct_sections_dir . 'profiel.php';
		include $ct_sections_dir . 'snelle-acties.php';
		?>
	</div>
</div>
