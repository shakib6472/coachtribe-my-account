<?php
/**
 * Inline SVG-markup voor abonnementskaart (zelfde stijl als reference).
 *
 * @package CoachTribe_My_Account
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'coachtribe_my_account_subscription_icon_svg' ) ) {
/**
 * @param string $name crown|sun|calendar|card|wallet|check|cart|sync|arrow-right|chevron-down|info|star|star-outline.
 */
function coachtribe_my_account_subscription_icon_svg( $name ) {
	switch ( $name ) {
		case 'crown':
			/* Drie punten (midden hoger), ronde topjes, basis, dunne streep met kleine tussenruimte — reference. */
			return '<svg class="ct-account-sub__icon-svg ct-account-sub__icon-svg--crown" width="28" height="28" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path fill="currentColor" d="M6.5 15.45L8.05 9.55L10.85 11.45L12 6.85L13.15 11.45L15.95 9.55L17.5 15.45z"/><circle cx="8.05" cy="9.5" r="1.12" fill="currentColor"/><circle cx="12" cy="6.78" r="1.22" fill="currentColor"/><circle cx="15.95" cy="9.5" r="1.12" fill="currentColor"/><rect x="4.9" y="17.88" width="14.2" height="1.68" rx="0.84" fill="currentColor"/></svg>';

		case 'sun':
			return '<svg class="ct-account-sub__icon-svg ct-account-sub__icon-svg--sun" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="3.25" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M12 3.5v2.25M12 18.25V20.5M3.5 12h2.25M18.25 12H20.5M5.8 5.8l1.6 1.6M16.6 16.6l1.6 1.6M18.2 5.8l-1.6 1.6M5.8 18.2l1.6-1.6" stroke="currentColor" stroke-width="1.45" stroke-linecap="round"/></svg>';

		case 'calendar':
			return '<svg class="ct-account-sub__icon-svg ct-account-sub__icon-svg--calendar" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><rect x="4.5" y="7.5" width="15" height="12.5" rx="1.75" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M4.5 11.25h15" stroke="currentColor" stroke-width="1.5"/><path d="M8.25 5.25v3.75M15.75 5.25v3.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="8.25" cy="5.25" r="1.15" stroke="currentColor" stroke-width="1.35" fill="none"/><circle cx="15.75" cy="5.25" r="1.15" stroke="currentColor" stroke-width="1.35" fill="none"/><path d="M8 14.5h2M12 14.5h2M16 14.5h2M8 17h2M12 17h2" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/></svg>';

		case 'card':
			return '<svg class="ct-account-sub__icon-svg ct-account-sub__icon-svg--card" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><rect x="4" y="7" width="16" height="11" rx="2" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M4 10.25h16" stroke="currentColor" stroke-width="1.35"/><rect x="6.5" y="13" width="5" height="3.25" rx="0.5" stroke="currentColor" stroke-width="1.2" fill="none"/><path d="M13.5 14.5h4M13.5 16.25h3" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>';

		case 'wallet':
			return '<svg class="ct-account-sub__icon-svg ct-account-sub__icon-svg--wallet" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M6 11h12v8a1 1 0 01-1 1H7a1 1 0 01-1-1v-8z" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linejoin="round"/><path d="M9 11V9a3 3 0 016 0v2" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/><path d="M17 14h2.5a.75.75 0 01.75.75v2.5a.75.75 0 01-.75.75H17" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" fill="none"/></svg>';

		case 'check':
			return coachtribe_my_account_subscription_icon_svg( 'sun' );

		case 'cart':
			return coachtribe_my_account_subscription_icon_svg( 'card' );

		case 'sync':
			return coachtribe_my_account_subscription_icon_svg( 'wallet' );

		case 'arrow-right':
			return '<svg class="ct-account-sub__icon-svg ct-account-sub__icon-svg--arrow-right" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M5 12h12M13 7l6 5-6 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

		case 'chevron-down':
			return '<svg class="ct-account-sub__icon-svg ct-account-sub__icon-svg--chevron-down" width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M7 10l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

		case 'info':
			return '<svg class="ct-account-sub__icon-svg ct-account-sub__icon-svg--info" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/><path d="M12 10v6M12 7.5v.01" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>';

		case 'star':
			return '<svg class="ct-account-sub__icon-svg ct-account-sub__icon-svg--star" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M12 3.5l2.2 5.5 6 .3-4.7 3.6 1.8 5.7-5.3-3.4-5.3 3.4 1.8-5.7L4 9.3l6-.3L12 3.5z" fill="currentColor"/></svg>';

		case 'star-outline':
			return '<svg class="ct-account-sub__icon-svg ct-account-sub__icon-svg--star-outline" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M12 3.5l2.2 5.5 6 .3-4.7 3.6 1.8 5.7-5.3-3.4-5.3 3.4 1.8-5.7L4 9.3l6-.3L12 3.5z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round" fill="none"/></svg>';

		default:
			return '';
	}
}
}
