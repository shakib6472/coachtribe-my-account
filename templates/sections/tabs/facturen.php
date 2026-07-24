<?php
/**
 * Tab: Facturen (bestellingen als facturen).
 *
 * @package CoachTribe_My_Account
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wc_get_orders' ) ) {
	echo '<p class="ct-account-invoices-empty">' . esc_html__( 'Facturen zijn niet beschikbaar zonder WooCommerce.', 'coachtribe-my-account' ) . '</p>';
	return;
}

$ct_inv_per_page = (int) apply_filters( 'coachtribe_my_account_invoices_per_page', 10 );
$ct_inv_per_page = max( 1, min( 50, $ct_inv_per_page ) );

$ct_inv_paged = isset( $_GET['ct_invoices_page'] ) ? absint( wp_unslash( $_GET['ct_invoices_page'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$ct_inv_paged = max( 1, $ct_inv_paged );

$ct_inv_query = wc_get_orders(
	array(
		'customer_id' => get_current_user_id(),
		'limit'       => $ct_inv_per_page,
		'page'        => $ct_inv_paged,
		'paginate'    => true,
		'orderby'     => 'date',
		'order'       => 'DESC',
		'type'        => 'shop_order',
	)
);

$ct_inv_orders    = array();
$ct_inv_max_pages = 1;

if ( is_object( $ct_inv_query ) && isset( $ct_inv_query->orders ) ) {
	$ct_inv_orders    = $ct_inv_query->orders;
	$ct_inv_max_pages = isset( $ct_inv_query->max_num_pages ) ? (int) $ct_inv_query->max_num_pages : 1;
} elseif ( is_array( $ct_inv_query ) ) {
	$ct_inv_orders = $ct_inv_query;
}

/**
 * Nederlandse betaalstatus op basis van bestelling.
 *
 * @param WC_Order $order Bestelling.
 * @return string
 */
$ct_invoice_status_label = static function ( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return '';
	}
	if ( $order->has_status( 'refunded' ) ) {
		return __( 'Terugbetaald', 'coachtribe-my-account' );
	}
	if ( $order->has_status( 'failed' ) ) {
		return __( 'Mislukt', 'coachtribe-my-account' );
	}
	if ( $order->has_status( 'cancelled' ) ) {
		return __( 'Geannuleerd', 'coachtribe-my-account' );
	}
	if ( $order->has_status( 'pending' ) ) {
		return __( 'In afwachting van betaling', 'coachtribe-my-account' );
	}
	if ( $order->has_status( 'on-hold' ) ) {
		return __( 'In afwachting', 'coachtribe-my-account' );
	}
	if ( $order->is_paid() ) {
		return __( 'Betaald', 'coachtribe-my-account' );
	}
	return __( 'Niet betaald', 'coachtribe-my-account' );
};

$ct_inv_base_url = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'facturen' ) : '';
?>
<section class="ct-account-invoices" aria-labelledby="ct-account-invoices-title">
	<h2 id="ct-account-invoices-title" class="ct-account-invoices__title"><?php esc_html_e( 'Facturen', 'coachtribe-my-account' ); ?></h2>

	<?php if ( empty( $ct_inv_orders ) ) : ?>
		<p class="ct-account-invoices-empty"><?php esc_html_e( 'Geen facturen gevonden.', 'coachtribe-my-account' ); ?></p>
	<?php else : ?>
		<div class="ct-account-invoices-scroll">
			<table class="ct-account-invoices-table">
				<thead>
					<tr>
						<th class="ct-account-invoice-number" scope="col"><?php esc_html_e( 'Factuurnummer', 'coachtribe-my-account' ); ?></th>
						<th class="ct-account-invoice-date" scope="col"><?php esc_html_e( 'Datum', 'coachtribe-my-account' ); ?></th>
						<th class="ct-account-invoice-amount" scope="col"><?php esc_html_e( 'Bedrag', 'coachtribe-my-account' ); ?></th>
						<th class="ct-account-invoice-status" scope="col"><?php esc_html_e( 'Betaalstatus', 'coachtribe-my-account' ); ?></th>
						<th class="ct-account-invoice-download" scope="col"><?php esc_html_e( 'Downloaden', 'coachtribe-my-account' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $ct_inv_orders as $ct_order ) {
						if ( ! $ct_order instanceof WC_Order ) {
							continue;
						}
						$ct_inv_num   = $ct_order->get_order_number();
						$ct_created   = $ct_order->get_date_created();
						$ct_inv_date  = ( $ct_created && function_exists( 'wc_format_datetime' ) ) ? wc_format_datetime( $ct_created, 'd-m-Y' ) : '—';
						$ct_inv_total = wp_kses_post( $ct_order->get_formatted_order_total() );
						$ct_inv_stat  = $ct_invoice_status_label( $ct_order );
						$ct_dl_url    = apply_filters( 'coachtribe_my_account_invoice_download_url', $ct_order->get_view_order_url(), $ct_order );
						?>
						<tr class="ct-account-invoices-table__row">
							<td class="ct-account-invoice-number" data-label="<?php esc_attr_e( 'Factuurnummer', 'coachtribe-my-account' ); ?>">
								<?php echo esc_html( (string) $ct_inv_num ); ?>
							</td>
							<td class="ct-account-invoice-date" data-label="<?php esc_attr_e( 'Datum', 'coachtribe-my-account' ); ?>">
								<?php echo esc_html( $ct_inv_date ); ?>
							</td>
							<td class="ct-account-invoice-amount" data-label="<?php esc_attr_e( 'Bedrag', 'coachtribe-my-account' ); ?>">
								<?php echo $ct_inv_total; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wc formatted price ?>
							</td>
							<td class="ct-account-invoice-status" data-label="<?php esc_attr_e( 'Betaalstatus', 'coachtribe-my-account' ); ?>">
								<span class="ct-account-invoice-status-badge"><?php echo esc_html( $ct_inv_stat ); ?></span>
							</td>
							<td class="ct-account-invoice-download" data-label="<?php esc_attr_e( 'Downloaden', 'coachtribe-my-account' ); ?>">
								<a class="ct-account-invoice-download-link" href="<?php echo esc_url( $ct_dl_url ); ?>">
									<?php esc_html_e( 'Downloaden', 'coachtribe-my-account' ); ?>
								</a>
							</td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
		</div>

		<?php if ( $ct_inv_max_pages > 1 && $ct_inv_base_url ) : ?>
			<nav class="ct-account-invoices-pagination" aria-label="<?php esc_attr_e( 'Factuurpaginering', 'coachtribe-my-account' ); ?>">
				<?php
				$ct_big      = 999999999;
				$ct_pag_url  = esc_url( add_query_arg( 'ct_invoices_page', $ct_big, $ct_inv_base_url ) );
				$ct_pag_base = str_replace( (string) $ct_big, '%#%', $ct_pag_url );
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paginate_links returns escaped markup
				echo paginate_links(
					array(
						'base'      => $ct_pag_base,
						'format'    => '',
						'current'   => $ct_inv_paged,
						'total'     => $ct_inv_max_pages,
						'type'      => 'list',
						'prev_text' => __( 'Vorige', 'coachtribe-my-account' ),
						'next_text' => __( 'Volgende', 'coachtribe-my-account' ),
					)
				);
				?>
			</nav>
		<?php endif; ?>
	<?php endif; ?>
</section>
