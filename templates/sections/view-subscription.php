<?php
/**
 * View subscription — details, switch plan, cancel link.
 *
 * @package CoachTribe_My_Account
 */

defined( 'ABSPATH' ) || exit;

$ct_vs_sub = function_exists( 'coachtribe_my_account_get_view_subscription_for_current_user' )
	? coachtribe_my_account_get_view_subscription_for_current_user()
	: null;

$ct_vs_dashboard_url = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'dashboard' ) : home_url( '/' );
?>
<section class="ct-account-view-sub" aria-labelledby="ct-account-view-sub-title">
	<p class="ct-account-view-sub__back">
		<a class="ct-account-view-sub__back-link" href="<?php echo esc_url( $ct_vs_dashboard_url ); ?>" data-ct-tab="dashboard">
			<span aria-hidden="true">←</span>
			<?php esc_html_e( 'Terug naar overzicht', 'coachtribe-my-account' ); ?>
		</a>
	</p>

	<?php if ( ! $ct_vs_sub instanceof WC_Subscription ) : ?>
		<div class="ct-account-view-sub__card ct-account-view-sub__card--error">
			<h2 id="ct-account-view-sub-title" class="ct-account-view-sub__title"><?php esc_html_e( 'Abonnement', 'coachtribe-my-account' ); ?></h2>
			<p class="ct-account-view-sub__message"><?php esc_html_e( 'Dit abonnement kon niet worden geladen of je hebt geen toegang.', 'coachtribe-my-account' ); ?></p>
		</div>
	<?php else : ?>
		<?php
		$ct_vs_status    = coachtribe_my_account_subscription_status_display( $ct_vs_sub );
		$ct_vs_plan_name = coachtribe_my_account_get_subscription_plan_display_name( $ct_vs_sub );
		if ( '' === $ct_vs_plan_name ) {
			$ct_vs_plan_name = __( 'Abonnement', 'coachtribe-my-account' );
		}

		$ct_vs_start_ts = $ct_vs_sub->get_time( 'start' );
		$ct_vs_start    = $ct_vs_start_ts
			? wp_date( get_option( 'date_format' ), $ct_vs_start_ts )
			: '—';

		$ct_vs_next_ts = $ct_vs_sub->get_time( 'next_payment' );
		$ct_vs_next    = $ct_vs_next_ts
			? wp_date( get_option( 'date_format' ), $ct_vs_next_ts )
			: '—';

		$ct_vs_amount = wp_kses_post( wc_price( $ct_vs_sub->get_total() ) );

		$ct_vs_pm_raw = $ct_vs_sub->get_payment_method_title();
		$ct_vs_pm     = '' !== trim( (string) $ct_vs_pm_raw )
			? wp_strip_all_tags( $ct_vs_pm_raw )
			: __( 'Nog niet ingesteld', 'coachtribe-my-account' );

		$ct_vs_plans = function_exists( 'coachtribe_my_account_get_switchable_subscription_plans' )
			? coachtribe_my_account_get_switchable_subscription_plans()
			: array();

		$ct_vs_cancel_url = function_exists( 'coachtribe_my_account_get_subscription_cancel_url' )
			? coachtribe_my_account_get_subscription_cancel_url( $ct_vs_sub )
			: '';
		?>

		<h2 id="ct-account-view-sub-title" class="ct-account-view-sub__title"><?php esc_html_e( 'Je abonnement', 'coachtribe-my-account' ); ?></h2>

		<div class="ct-account-view-sub__card ct-account-view-sub__card--details">
			<div class="ct-account-view-sub__details-head">
				<h3 class="ct-account-view-sub__plan-name"><?php echo esc_html( $ct_vs_plan_name ); ?></h3>
				<span class="<?php echo esc_attr( $ct_vs_status['class'] ); ?>"><?php echo esc_html( $ct_vs_status['label'] ); ?></span>
			</div>
			<dl class="ct-account-view-sub__meta">
				<div class="ct-account-view-sub__meta-row">
					<dt><?php esc_html_e( 'Startdatum', 'coachtribe-my-account' ); ?></dt>
					<dd><?php echo esc_html( $ct_vs_start ); ?></dd>
				</div>
				<div class="ct-account-view-sub__meta-row">
					<dt><?php esc_html_e( 'Volgende betaling', 'coachtribe-my-account' ); ?></dt>
					<dd><?php echo esc_html( $ct_vs_next ); ?></dd>
				</div>
				<div class="ct-account-view-sub__meta-row">
					<dt><?php esc_html_e( 'Bedrag per cyclus', 'coachtribe-my-account' ); ?></dt>
					<dd><?php echo $ct_vs_amount; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wc_price ?></dd>
				</div>
				<div class="ct-account-view-sub__meta-row">
					<dt><?php esc_html_e( 'Betaalmethode', 'coachtribe-my-account' ); ?></dt>
					<dd><?php echo esc_html( $ct_vs_pm ); ?></dd>
				</div>
			</dl>
		</div>

		<div class="ct-account-view-sub__card ct-account-view-sub__card--plans">
			<h3 class="ct-account-view-sub__section-title"><?php esc_html_e( 'Wijzig plan', 'coachtribe-my-account' ); ?></h3>
			<p class="ct-account-view-sub__section-intro"><?php esc_html_e( 'Kies een ander abonnement. Je huidige plan is gemarkeerd.', 'coachtribe-my-account' ); ?></p>

			<?php if ( empty( $ct_vs_plans ) ) : ?>
				<p class="ct-account-view-sub__empty"><?php esc_html_e( 'Geen andere abonnementen beschikbaar.', 'coachtribe-my-account' ); ?></p>
			<?php else : ?>
				<ul class="ct-account-view-sub__plan-list">
					<?php
					foreach ( $ct_vs_plans as $ct_vs_plan_product ) {
						if ( ! $ct_vs_plan_product instanceof WC_Product ) {
							continue;
						}
						$ct_vs_pid          = $ct_vs_plan_product->get_id();
						$ct_vs_is_current   = coachtribe_my_account_subscription_has_product( $ct_vs_sub, $ct_vs_pid );
						$ct_vs_switch_url   = coachtribe_my_account_build_switch_to_product_checkout_url( $ct_vs_sub, $ct_vs_pid );
						$ct_vs_desc         = $ct_vs_plan_product->get_short_description();
						if ( '' === trim( wp_strip_all_tags( $ct_vs_desc ) ) ) {
							$ct_vs_desc = $ct_vs_plan_product->get_description();
						}
						?>
						<li class="ct-account-view-sub__plan-item<?php echo $ct_vs_is_current ? ' is-current' : ''; ?>">
							<div class="ct-account-view-sub__plan-body">
								<?php if ( $ct_vs_is_current ) : ?>
									<span class="ct-account-view-sub__current-badge"><?php esc_html_e( 'Huidig plan', 'coachtribe-my-account' ); ?></span>
								<?php endif; ?>
								<h4 class="ct-account-view-sub__plan-item-name"><?php echo esc_html( $ct_vs_plan_product->get_name() ); ?></h4>
								<p class="ct-account-view-sub__plan-item-price"><?php echo wp_kses_post( $ct_vs_plan_product->get_price_html() ); ?></p>
								<?php if ( '' !== trim( wp_strip_all_tags( $ct_vs_desc ) ) ) : ?>
									<div class="ct-account-view-sub__plan-item-features">
										<?php echo wp_kses_post( wpautop( $ct_vs_desc ) ); ?>
									</div>
								<?php endif; ?>
							</div>
							<?php if ( ! $ct_vs_is_current && '' !== $ct_vs_switch_url ) : ?>
								<a class="ct-account-view-sub__plan-select ct-account-subscription__btn ct-account-subscription__btn--primary" href="<?php echo esc_url( $ct_vs_switch_url ); ?>">
									<span class="ct-account-subscription__btn-label"><?php esc_html_e( 'Selecteer dit plan', 'coachtribe-my-account' ); ?></span>
								</a>
							<?php elseif ( ! $ct_vs_is_current ) : ?>
								<p class="ct-account-view-sub__plan-unavailable"><?php esc_html_e( 'Niet beschikbaar om te wisselen.', 'coachtribe-my-account' ); ?></p>
							<?php endif; ?>
						</li>
						<?php
					}
					?>
				</ul>
			<?php endif; ?>
		</div>

		<?php if ( '' !== $ct_vs_cancel_url ) : ?>
			<p class="ct-account-view-sub__cancel">
				<a class="ct-account-view-sub__cancel-link" href="<?php echo esc_url( $ct_vs_cancel_url ); ?>">
					<?php esc_html_e( 'Abonnement opzeggen', 'coachtribe-my-account' ); ?>
				</a>
			</p>
		<?php endif; ?>
	<?php endif; ?>
</section>
