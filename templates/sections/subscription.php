<?php
/**
 * Sectie: huidig abonnement (primair op overzicht) — WooCommerce Subscriptions.
 *
 * @package CoachTribe_My_Account
 */

defined( 'ABSPATH' ) || exit;

$ct_ctma_icons = COACHTRIBE_MY_ACCOUNT_PATH . 'templates/partials/subscription-icons-svg.php';
if ( file_exists( $ct_ctma_icons ) ) {
	require_once $ct_ctma_icons;
}

$ct_sub_user_id = get_current_user_id();
$ct_sub         = null;

if ( function_exists( 'wcs_get_users_subscriptions' ) && class_exists( 'WC_Subscription' ) ) {
	$ct_subscriptions = wcs_get_users_subscriptions( $ct_sub_user_id );
	$ct_sub_on_hold   = null;
	$ct_sub_pending   = null;
	$ct_sub_latest    = null;
	$ct_sub_latest_ts = 0;

	foreach ( $ct_subscriptions as $ct_candidate ) {
		if ( ! $ct_candidate instanceof WC_Subscription ) {
			continue;
		}
		if ( $ct_candidate->has_status( 'active' ) ) {
			$ct_sub = $ct_candidate;
			break;
		}
		if ( $ct_candidate->has_status( 'on-hold' ) && null === $ct_sub_on_hold ) {
			$ct_sub_on_hold = $ct_candidate;
		}
		if ( $ct_candidate->has_status( 'pending' ) && null === $ct_sub_pending ) {
			$ct_sub_pending = $ct_candidate;
		}
		$ct_mod = $ct_candidate->get_date_modified();
		$ct_ts  = $ct_mod ? $ct_mod->getTimestamp() : 0;
		if ( $ct_ts >= $ct_sub_latest_ts ) {
			$ct_sub_latest_ts = $ct_ts;
			$ct_sub_latest    = $ct_candidate;
		}
	}

	if ( null === $ct_sub && null !== $ct_sub_on_hold ) {
		$ct_sub = $ct_sub_on_hold;
	} elseif ( null === $ct_sub && null !== $ct_sub_pending ) {
		$ct_sub = $ct_sub_pending;
	} elseif ( null === $ct_sub && null !== $ct_sub_latest ) {
		$ct_sub = $ct_sub_latest;
	}
}

$ct_signup_url = apply_filters( 'coachtribe_my_account_subscriptions_signup_url', home_url( '/aanmelden/' ) );

// --- PMPro membership (only relevant when there is NO WC subscription) ---
$ct_pmpro = ( function_exists( 'pmpro_getMembershipLevelForUser' ) && is_user_logged_in() )
	? pmpro_getMembershipLevelForUser( get_current_user_id() )
	: false;
$ct_has_pmpro = ( $ct_pmpro && ! empty( $ct_pmpro->ID ) );

$ct_show_upgrade_to_pro = false;
$ct_pro_upgrade_url     = '';

$ct_sub_manage_url = '';
if ( $ct_sub instanceof WC_Subscription && function_exists( 'wc_get_account_endpoint_url' ) ) {
	$ct_sub_manage_url = (string) wc_get_account_endpoint_url( 'view-subscription', $ct_sub->get_id() );
}
if ( '' === trim( $ct_sub_manage_url ) && $ct_sub instanceof WC_Subscription && function_exists( 'wc_get_endpoint_url' ) && function_exists( 'wc_get_page_permalink' ) ) {
	$ct_ma_base = wc_get_page_permalink( 'myaccount' );
	if ( $ct_ma_base ) {
		$ct_sub_manage_url = esc_url_raw( wc_get_endpoint_url( 'view-subscription', (string) $ct_sub->get_id(), $ct_ma_base ) );
	}
}
if ( '' === trim( $ct_sub_manage_url ) && $ct_sub instanceof WC_Subscription && function_exists( 'wc_get_account_endpoint_url' ) ) {
	$ct_sub_manage_url = (string) wc_get_account_endpoint_url( 'subscriptions' );
}

if ( isset( $ct_sub ) && $ct_sub instanceof WC_Subscription ) {
	$ct_payment_methods_url = '';
	if ( function_exists( 'wcs_get_all_user_actions_for_subscription' ) ) {
		$ct_sub_actions = wcs_get_all_user_actions_for_subscription( $ct_sub, get_current_user_id() );
		if ( isset( $ct_sub_actions['change_payment_method']['url'] ) && is_string( $ct_sub_actions['change_payment_method']['url'] ) ) {
			$ct_payment_methods_url = $ct_sub_actions['change_payment_method']['url'];
		}
	}
	if ( '' === $ct_payment_methods_url && is_callable( array( $ct_sub, 'get_change_payment_method_url' ) ) ) {
		$ct_payment_methods_url = $ct_sub->get_change_payment_method_url();
	}
	if ( '' === $ct_payment_methods_url && function_exists( 'wc_get_account_endpoint_url' ) ) {
		$ct_payment_methods_url = wc_get_account_endpoint_url( 'payment-methods' );
	}
} else {
	$ct_payment_methods_url = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'payment-methods' ) : '';
}
$ct_facturen_url = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'facturen' ) : '';

$ct_sub_is_active = $ct_sub instanceof WC_Subscription && $ct_sub->has_status( 'active' );

if ( $ct_sub instanceof WC_Subscription ) {
	$ct_plan_name = '';
	foreach ( $ct_sub->get_items() as $ct_item ) {
		if ( $ct_item->is_type( 'line_item' ) ) {
			$ct_plan_name = $ct_item->get_name();
			break;
		}
	}
	if ( '' === $ct_plan_name ) {
		$ct_plan_name = __( 'Abonnement', 'coachtribe-my-account' );
	}

	$ct_billing_period   = $ct_sub->get_billing_period();
	$ct_billing_interval = max( 1, (int) $ct_sub->get_billing_interval() );

	if ( 'month' === $ct_billing_period && 1 === $ct_billing_interval ) {
		$ct_billing_type_label = __( 'Maandabonnement', 'coachtribe-my-account' );
		$ct_cycle_label        = __( 'Maandelijks', 'coachtribe-my-account' );
		$ct_price_field_label  = __( 'Prijs per maand', 'coachtribe-my-account' );
	} elseif ( 'year' === $ct_billing_period && 1 === $ct_billing_interval ) {
		$ct_billing_type_label = __( 'Jaarabonnement', 'coachtribe-my-account' );
		$ct_cycle_label        = __( 'Jaarlijks', 'coachtribe-my-account' );
		$ct_price_field_label  = __( 'Prijs per jaar', 'coachtribe-my-account' );
	} elseif ( 'year' === $ct_billing_period ) {
		$ct_billing_type_label = __( 'Jaarabonnement', 'coachtribe-my-account' );
		$ct_cycle_label        = sprintf(
			/* translators: %d: interval in jaren */
			__( 'Elke %d jaar', 'coachtribe-my-account' ),
			$ct_billing_interval
		);
		$ct_price_field_label = __( 'Prijs per periode', 'coachtribe-my-account' );
	} elseif ( 'month' === $ct_billing_period ) {
		$ct_billing_type_label = __( 'Maandabonnement', 'coachtribe-my-account' );
		$ct_cycle_label        = sprintf(
			/* translators: %d: aantal maanden */
			__( 'Elke %d maanden', 'coachtribe-my-account' ),
			$ct_billing_interval
		);
		$ct_price_field_label = __( 'Prijs per periode', 'coachtribe-my-account' );
	} elseif ( 'week' === $ct_billing_period && 1 === $ct_billing_interval ) {
		$ct_billing_type_label = __( 'Weekabonnement', 'coachtribe-my-account' );
		$ct_cycle_label        = __( 'Wekelijks', 'coachtribe-my-account' );
		$ct_price_field_label  = __( 'Prijs per week', 'coachtribe-my-account' );
	} elseif ( 'day' === $ct_billing_period && 1 === $ct_billing_interval ) {
		$ct_billing_type_label = __( 'Dagabonnement', 'coachtribe-my-account' );
		$ct_cycle_label        = __( 'Dagelijks', 'coachtribe-my-account' );
		$ct_price_field_label  = __( 'Prijs per dag', 'coachtribe-my-account' );
	} else {
		$ct_billing_type_label = __( 'Abonnement', 'coachtribe-my-account' );
		$ct_cycle_label        = __( 'Aangepast', 'coachtribe-my-account' );
		$ct_price_field_label  = __( 'Prijs per periode', 'coachtribe-my-account' );
	}

	$ct_price_html = wp_kses_post( wc_price( $ct_sub->get_total() ) );

	$ct_price_period_text = '';
	if ( 'month' === $ct_billing_period && 1 === $ct_billing_interval ) {
		$ct_price_period_text = __( 'per maand', 'coachtribe-my-account' );
	} elseif ( 'year' === $ct_billing_period && 1 === $ct_billing_interval ) {
		$ct_price_period_text = __( 'per jaar', 'coachtribe-my-account' );
	} elseif ( 'week' === $ct_billing_period && 1 === $ct_billing_interval ) {
		$ct_price_period_text = __( 'per week', 'coachtribe-my-account' );
	} elseif ( 'day' === $ct_billing_period && 1 === $ct_billing_interval ) {
		$ct_price_period_text = __( 'per dag', 'coachtribe-my-account' );
	}

	if ( $ct_sub->has_status( 'active' ) ) {
		$ct_status_class = 'ct-account-sub__badge ct-account-sub__badge--active';
		$ct_status_label = __( 'Actief', 'coachtribe-my-account' );
	} elseif (
		$ct_sub->has_status( 'on-hold' )
		|| $ct_sub->has_status( 'pending' )
		|| $ct_sub->has_status( 'pending-cancel' )
	) {
		$ct_status_class = 'ct-account-sub__badge ct-account-sub__badge--paused';
		$ct_status_label = __( 'Gepauzeerd', 'coachtribe-my-account' );
	} else {
		$ct_status_class = 'ct-account-sub__badge ct-account-sub__badge--cancelled';
		$ct_status_label = __( 'Geannuleerd', 'coachtribe-my-account' );
	}

	$ct_next_ts = $ct_sub->get_time( 'next_payment' );
	if ( $ct_next_ts ) {
		$ct_next_payment = wp_date(
			get_option( 'date_format' ),
			$ct_next_ts
		);
	} else {
		$ct_next_payment = '—';
	}

	$ct_pm_raw = $ct_sub->get_payment_method_title();
	if ( '' === trim( (string) $ct_pm_raw ) ) {
		$ct_payment_method = __( 'Nog niet ingesteld', 'coachtribe-my-account' );
	} else {
		$ct_payment_method = wp_strip_all_tags( $ct_pm_raw );
	}

	$ct_is_pro = coachtribe_my_account_subscription_is_pro_plan( $ct_sub, $ct_plan_name );
	$ct_show_upgrade_to_pro = (bool) apply_filters(
		'coachtribe_my_account_show_upgrade_to_pro',
		( $ct_sub_is_active && ! $ct_is_pro ),
		$ct_sub,
		$ct_plan_name
	);
	$ct_to_pro_product_id = (int) apply_filters( 'coachtribe_my_account_upgrade_to_pro_product_id', 0, $ct_sub, $ct_plan_name );
	$ct_pro_switch_checkout = coachtribe_my_account_build_switch_to_product_checkout_url( $ct_sub, $ct_to_pro_product_id );
	$ct_pro_fallback = $ct_signup_url;
	if ( function_exists( 'wc_get_account_endpoint_url' ) ) {
		$ct_pro_fallback = wc_get_account_endpoint_url( 'subscriptions' );
	}
	$ct_pro_upgrade_url = '' !== $ct_pro_switch_checkout ? $ct_pro_switch_checkout : $ct_pro_fallback;
	$ct_pro_upgrade_url = (string) apply_filters( 'coachtribe_my_account_subscription_upgrade_url', $ct_pro_upgrade_url, $ct_sub );
	if ( '' === trim( $ct_pro_upgrade_url ) && function_exists( 'wc_get_account_endpoint_url' ) ) {
		$ct_pro_upgrade_url = (string) wc_get_account_endpoint_url( 'subscriptions' );
	}
	if ( '' === trim( $ct_pro_upgrade_url ) ) {
		$ct_pro_upgrade_url = (string) $ct_signup_url;
	}

	$ct_change_subscription_url = (string) apply_filters( 'coachtribe_my_account_subscription_change_url', $ct_sub_manage_url, $ct_sub );
	$ct_change_subscription_label = (string) apply_filters( 'coachtribe_my_account_subscription_change_label', __( 'Wijzig abonnement', 'coachtribe-my-account' ), $ct_sub );
	if ( '' === trim( $ct_change_subscription_url ) && $ct_sub instanceof WC_Subscription && function_exists( 'wc_get_endpoint_url' ) && function_exists( 'wc_get_page_permalink' ) ) {
		$ct_ma_base = wc_get_page_permalink( 'myaccount' );
		if ( $ct_ma_base ) {
			$ct_change_subscription_url = esc_url_raw( wc_get_endpoint_url( 'view-subscription', (string) $ct_sub->get_id(), $ct_ma_base ) );
		}
	}
	if ( '' === trim( $ct_change_subscription_url ) && function_exists( 'wc_get_account_endpoint_url' ) ) {
		$ct_change_subscription_url = (string) wc_get_account_endpoint_url( 'subscriptions' );
	}
	if ( '' === trim( $ct_change_subscription_url ) && function_exists( 'wc_get_page_permalink' ) ) {
		$ct_change_subscription_url = (string) wc_get_page_permalink( 'myaccount' );
	}
	$ct_subscription_footnote     = (string) apply_filters(
		'coachtribe_my_account_subscription_footnote_text',
		__( 'Opzeggen of andere opties zijn beschikbaar via \'Beheer abonnement\'.', 'coachtribe-my-account' ),
		$ct_sub
	);
}
?>
<?php
$ct_sections_dir = COACHTRIBE_MY_ACCOUNT_PATH . 'templates/sections/';
?>
<section class="ct-account-subscription ct-account-subscription--ref" aria-labelledby="ct-account-subscription-title">
	<div class="ct-subscription-panel">
		<div class="ct-subscription-panel__head">
			<span class="ct-subscription-panel__icon" aria-hidden="true">
				<?php echo function_exists( 'coachtribe_my_account_subscription_icon_svg' ) ? coachtribe_my_account_subscription_icon_svg( 'crown' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</span>
			<h2 id="ct-account-subscription-title" class="ct-subscription-panel__title"><?php esc_html_e( 'Huidig abonnement', 'coachtribe-my-account' ); ?></h2>
		</div>

		<div class="ct-overview-top ct-overview-top--ref">
			<div class="ct-subscription-card">
				<?php if ( ! $ct_sub instanceof WC_Subscription && ! $ct_has_pmpro ) : ?>
					<div class="ct-account-subscription__empty">
						<p class="ct-account-subscription__empty-text"><?php esc_html_e( 'Je hebt nog geen actief abonnement.', 'coachtribe-my-account' ); ?></p>
						<a class="ct-account-subscription__btn ct-account-subscription__btn--primary" href="<?php echo esc_url( $ct_signup_url ); ?>">
							<span class="ct-account-subscription__btn-label"><?php esc_html_e( 'Bekijk abonnementen', 'coachtribe-my-account' ); ?></span>
							<span class="ct-account-subscription__btn-arrow" aria-hidden="true"><?php echo function_exists( 'coachtribe_my_account_subscription_icon_svg' ) ? coachtribe_my_account_subscription_icon_svg( 'arrow-right' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						</a>
					</div>
			<?php elseif ( ! $ct_sub instanceof WC_Subscription && $ct_has_pmpro ) : ?>
					<table class="ct-subscription-info ct-subscription-info--pmpro" style="border:none;border-collapse:collapse;background:transparent;box-shadow:none;width:auto;margin:0;">
						<tr style="background:transparent;">
							<td style="border:none;background:transparent;padding:8px 0;color:#9ca3af;font-size:15px;"><?php esc_html_e( 'Status', 'coachtribe-my-account' ); ?></td>
							<td style="border:none;background:transparent;padding:8px 18px;color:#9ca3af;"></td>
							<td style="border:none;background:transparent;padding:8px 0;">
								<span style="display:inline-block;color:#3fb950;background:rgba(63,185,80,.12);border:1px solid rgba(63,185,80,.4);border-radius:999px;padding:4px 16px;font-weight:600;font-size:14px;line-height:1;"><?php esc_html_e( 'Actief', 'coachtribe-my-account' ); ?></span>
							</td>
						</tr>
						<tr style="background:transparent;">
							<td style="border:none;background:transparent;padding:8px 0;color:#9ca3af;font-size:15px;"><?php esc_html_e( 'Abonnement', 'coachtribe-my-account' ); ?></td>
							<td style="border:none;background:transparent;padding:8px 18px;color:#9ca3af;">:</td>
							<td style="border:none;background:transparent;padding:8px 0;color:#ffffff;font-weight:700;font-size:15px;"><?php echo esc_html( $ct_pmpro->name ); ?></td>
						</tr>
						<?php
						$ct_source = (string) get_user_meta( get_current_user_id(), 'ct_membership_source', true );
						$ct_source_labels = array(
							'plug_and_pay'  => 'Plug&Pay',
							'Gratis' => 'Gratis',
							'woocommerce'   => 'WooCommerce',
						);
						if ( isset( $ct_source_labels[ $ct_source ] ) ) :
						?>
						<tr style="background:transparent;">
							<td style="border:none;background:transparent;padding:8px 0;color:#9ca3af;font-size:15px;"><?php esc_html_e( 'Betaling via', 'coachtribe-my-account' ); ?></td>
							<td style="border:none;background:transparent;padding:8px 18px;color:#9ca3af;">:</td>
							<td style="border:none;background:transparent;padding:8px 0;color:#ffffff;font-weight:700;font-size:15px;"><?php echo esc_html( $ct_source_labels[ $ct_source ] ); ?></td>
						</tr>
						<?php endif; ?>
					</table>
				<?php else : ?>
					<div class="ct-subscription-info ct-subscription-info--ref-grid">
						<div class="ct-info-row">
							<span class="ct-label"><?php esc_html_e( 'Soort abonnement', 'coachtribe-my-account' ); ?></span>
							<span class="ct-info-sep" aria-hidden="true">:</span>
							<span class="ct-value"><?php echo esc_html( $ct_billing_type_label ); ?></span>
						</div>
						<div class="ct-info-row">
							<span class="ct-label"><?php esc_html_e( 'Status', 'coachtribe-my-account' ); ?></span>
							<span class="ct-info-sep" aria-hidden="true">:</span>
							<span class="ct-value">
								<span class="<?php echo esc_attr( $ct_status_class ); ?>"><?php echo esc_html( $ct_status_label ); ?></span>
							</span>
						</div>
						<div class="ct-info-row">
							<span class="ct-label"><?php esc_html_e( 'Volgende betaling', 'coachtribe-my-account' ); ?></span>
							<span class="ct-info-sep" aria-hidden="true">:</span>
							<span class="ct-value"><?php echo esc_html( $ct_next_payment ); ?></span>
						</div>
						<div class="ct-info-row">
							<span class="ct-label"><?php esc_html_e( 'Bedrag', 'coachtribe-my-account' ); ?></span>
							<span class="ct-info-sep" aria-hidden="true">:</span>
							<span class="ct-value ct-value--price">
								<?php echo $ct_price_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wc_price ?>
								<?php if ( '' !== $ct_price_period_text ) : ?>
									<span class="ct-value__period"><?php echo esc_html( $ct_price_period_text ); ?></span>
								<?php endif; ?>
							</span>
						</div>
						<div class="ct-info-row">
							<span class="ct-label"><?php esc_html_e( 'Betaalmethode', 'coachtribe-my-account' ); ?></span>
							<span class="ct-info-sep" aria-hidden="true">:</span>
							<span class="ct-value ct-value--payment">
								<?php if ( false !== stripos( (string) $ct_payment_method, 'ideal' ) ) : ?>
									<span class="ct-ideal-logo" aria-hidden="true">
										<svg class="ct-ideal-logo__svg" width="40" height="22" viewBox="0 0 40 22" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
											<rect x="0.5" y="0.5" width="39" height="21" rx="3.5" stroke="#ffffff" stroke-width="1"/>
											<rect x="3" y="3" width="34" height="16" rx="2" fill="#CC0066"/>
											<text x="8" y="14.5" fill="#ffffff" font-size="7" font-weight="700" font-family="Arial,sans-serif">i</text>
											<text x="14" y="14.5" fill="#ffffff" font-size="6" font-weight="700" font-family="Arial,sans-serif">DEAL</text>
										</svg>
									</span>
								<?php endif; ?>
								<span class="ct-value__text"><?php echo esc_html( $ct_payment_method ); ?></span>
							</span>
						</div>
						<div class="ct-info-row">
							<span class="ct-label"><?php esc_html_e( 'Factuurcyclus', 'coachtribe-my-account' ); ?></span>
							<span class="ct-info-sep" aria-hidden="true">:</span>
							<span class="ct-value"><?php echo esc_html( $ct_cycle_label ); ?></span>
						</div>
					</div>

					<div class="ct-account-subscription__cta">
						<a class="ct-account-upgrade-btn ct-account-upgrade-btn--ref" href="<?php echo esc_url( $ct_pro_upgrade_url ); ?>" data-ct-tab="upgrade-pro">
							<span class="ct-account-upgrade-btn__star-ring" aria-hidden="true">
								<?php echo function_exists( 'coachtribe_my_account_subscription_icon_svg' ) ? coachtribe_my_account_subscription_icon_svg( 'star' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</span>
							<span class="ct-account-upgrade-btn__label"><?php esc_html_e( 'Upgrade naar Premium', 'coachtribe-my-account' ); ?></span>
						</a>
					</div>
				<?php endif; ?>
			</div>

			<div class="ct-profile-photo-right">
				<?php require $ct_sections_dir . 'profile-avatar-header.php'; ?>
			</div>
		</div>
	</div>
</section>