<?php
/**
 * "Jouw abonnement" summary card shown above the WooCommerce payment-methods form
 * (Betaalmethode wijzigen). Data comes from the user's active WC subscription.
 *
 * @package CoachTribe_My_Account
 */

defined( 'ABSPATH' ) || exit;

$ct_ps_sub = isset( $GLOBALS['coachtribe_payment_summary_sub'] ) && $GLOBALS['coachtribe_payment_summary_sub'] instanceof WC_Subscription
	? $GLOBALS['coachtribe_payment_summary_sub']
	: null;

if ( ! $ct_ps_sub instanceof WC_Subscription ) {
	return;
}

// Plan / product name.
$ct_ps_plan = '';
foreach ( $ct_ps_sub->get_items() as $ct_ps_item ) {
	if ( $ct_ps_item->is_type( 'line_item' ) ) {
		$ct_ps_plan = $ct_ps_item->get_name();
		break;
	}
}
if ( '' === $ct_ps_plan ) {
	$ct_ps_plan = __( 'Abonnement', 'coachtribe-my-account' );
}

// Price + period.
$ct_ps_period = $ct_ps_sub->get_billing_period();
$ct_ps_per    = '';
if ( 'month' === $ct_ps_period ) {
	$ct_ps_per = __( 'per maand', 'coachtribe-my-account' );
} elseif ( 'year' === $ct_ps_period ) {
	$ct_ps_per = __( 'per jaar', 'coachtribe-my-account' );
} elseif ( 'week' === $ct_ps_period ) {
	$ct_ps_per = __( 'per week', 'coachtribe-my-account' );
}
$ct_ps_price = trim( wp_strip_all_tags( wc_price( $ct_ps_sub->get_total() ) ) );
if ( '' !== $ct_ps_per ) {
	$ct_ps_price .= ' ' . $ct_ps_per;
}

// Status.
$ct_ps_active = $ct_ps_sub->has_status( 'active' );
$ct_ps_status = $ct_ps_active ? __( 'Actief', 'coachtribe-my-account' ) : __( 'Inactief', 'coachtribe-my-account' );

// Payment method.
$ct_ps_pm_raw = $ct_ps_sub->get_payment_method_title();
$ct_ps_pm     = '' !== trim( (string) $ct_ps_pm_raw ) ? wp_strip_all_tags( $ct_ps_pm_raw ) : __( 'Nog niet ingesteld', 'coachtribe-my-account' );

// Next payment.
$ct_ps_next_ts = $ct_ps_sub->get_time( 'next_payment' );
$ct_ps_next    = $ct_ps_next_ts ? wp_date( get_option( 'date_format' ), $ct_ps_next_ts ) : '—';
?>
<section class="ct-payment-summary" aria-labelledby="ct-payment-summary-title">
	<div class="ct-payment-summary__head">
		<span class="ct-payment-summary__icon" aria-hidden="true">
			<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
				<path d="M3 8l4 3 5-6 5 6 4-3-2 11H5L3 8z" stroke="#fff" stroke-width="1.6" stroke-linejoin="round"/>
			</svg>
		</span>
		<h2 id="ct-payment-summary-title" class="ct-payment-summary__title"><?php esc_html_e( 'Jouw abonnement', 'coachtribe-my-account' ); ?></h2>
	</div>

	<dl class="ct-payment-summary__grid">
		<div class="ct-payment-summary__row">
			<dt class="ct-payment-summary__label"><?php esc_html_e( 'Product', 'coachtribe-my-account' ); ?></dt>
			<dd class="ct-payment-summary__value"><?php echo esc_html( $ct_ps_plan ); ?></dd>
		</div>
		<div class="ct-payment-summary__row">
			<dt class="ct-payment-summary__label"><?php esc_html_e( 'Prijs', 'coachtribe-my-account' ); ?></dt>
			<dd class="ct-payment-summary__value"><?php echo esc_html( $ct_ps_price ); ?></dd>
		</div>
		<div class="ct-payment-summary__row">
			<dt class="ct-payment-summary__label"><?php esc_html_e( 'Status', 'coachtribe-my-account' ); ?></dt>
			<dd class="ct-payment-summary__value">
				<span class="ct-payment-summary__badge<?php echo $ct_ps_active ? ' ct-payment-summary__badge--active' : ''; ?>"><?php echo esc_html( $ct_ps_status ); ?></span>
			</dd>
		</div>
		<div class="ct-payment-summary__row">
			<dt class="ct-payment-summary__label"><?php esc_html_e( 'Betaalmethode', 'coachtribe-my-account' ); ?></dt>
			<dd class="ct-payment-summary__value"><?php echo esc_html( $ct_ps_pm ); ?></dd>
		</div>
		<div class="ct-payment-summary__row">
			<dt class="ct-payment-summary__label"><?php esc_html_e( 'Volgende betaling', 'coachtribe-my-account' ); ?></dt>
			<dd class="ct-payment-summary__value"><?php echo esc_html( $ct_ps_next ); ?></dd>
		</div>
	</dl>
</section>
