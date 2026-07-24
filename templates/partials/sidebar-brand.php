<?php
/**
 * Zijbalk-logo (linksboven): CoachTribe merkafbeelding.
 *
 * @package CoachTribe_My_Account
 */

defined( 'ABSPATH' ) || exit;

$ct_brand_link = isset( $ct_logo_link ) ? $ct_logo_link : home_url( '/' );
$ct_brand_url  = isset( $ct_logo_url ) ? $ct_logo_url : '';

if ( '' === $ct_brand_url && function_exists( 'coachtribe_my_account_get_brand_logo_url' ) ) {
	$ct_brand_url = coachtribe_my_account_get_brand_logo_url();
} elseif ( '' === $ct_brand_url ) {
	$ct_brand_url = COACHTRIBE_MY_ACCOUNT_URL . 'assets/images/coachtribe-logo.png';
}
?>
<a class="ct-account-sidebar__logo" href="<?php echo esc_url( $ct_brand_link ); ?>">
	<img
		class="ct-account-sidebar__logo-img"
		src="<?php echo esc_url( $ct_brand_url ); ?>"
		alt="<?php esc_attr_e( 'CoachTribe', 'coachtribe-my-account' ); ?>"
		width="200"
		height="40"
		decoding="async"
	/>
</a>
