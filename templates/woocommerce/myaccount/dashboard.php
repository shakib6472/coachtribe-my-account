<?php
/**
 * Dashboard — minimale variant: geen standaard WooCommerce-dashboardtekst,
 * wel de hook voor extensies en toekomstige secties.
 *
 * @package CoachTribe_My_Account
 */

defined( 'ABSPATH' ) || exit;

/**
 * @hooked — diverse extensies kunnen hier inhoud toevoegen.
 */
do_action( 'woocommerce_account_dashboard' );
