<?php
/**
 * Sidebar navigatie-iconen (SVG, reference layout).
 *
 * @package CoachTribe_My_Account
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'coachtribe_my_account_sidebar_nav_icon' ) ) {
	/**
	 * @param string $name home-solid|home-outline|invoice|settings|lock|logout.
	 */
	function coachtribe_my_account_sidebar_nav_icon( $name ) {
		$common = 'class="ct-account-sidebar__icon-svg" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"';

		switch ( $name ) {
			case 'home-solid':
				return '<svg ' . $common . '><path fill="currentColor" d="M12 3.2 4 10.2V20h5v-6h6v6h5v-9.8L12 3.2z"/></svg>';

			case 'home-outline':
				return '<svg ' . $common . '><path d="M4 10.5 12 4l8 6.5V20h-5v-6H9v6H4v-9.5z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/></svg>';

			case 'invoice':
				return '<svg ' . $common . '><path d="M8 4h8a2 2 0 012 2v14l-4-2.5L12 20l-4-2.5V6a2 2 0 012-2z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><path d="M10 9h4M10 12.5h4M10 16h2.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';

			case 'settings':
				return '<svg ' . $common . '><path d="M12 15a3 3 0 100-6 3 3 0 000 6z" stroke="currentColor" stroke-width="1.75"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>';

			case 'lock':
				return '<svg ' . $common . '><rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.75"/><path d="M8 11V8a4 4 0 118 0v3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>';

			case 'logout':
				return '<svg ' . $common . '><path d="M10 7V6a2 2 0 012-2h6a2 2 0 012 2v12a2 2 0 01-2 2h-6a2 2 0 01-2-2v-1" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/><path d="M4 12h9m0 0-3-3m3 3-3 3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>';

			default:
				return '';
		}
	}
}
