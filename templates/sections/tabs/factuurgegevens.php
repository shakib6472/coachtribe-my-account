<?php
/**
 * Tab: Factuurgegevens (editable billing details — WooCommerce members).
 *
 * @package CoachTribe_My_Account
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Customer' ) ) {
	echo '<p class="ct-account-invoices-empty">' . esc_html__( 'Factuurgegevens zijn niet beschikbaar zonder WooCommerce.', 'coachtribe-my-account' ) . '</p>';
	return;
}

$ct_fg_id       = get_current_user_id();
$ct_fg_customer = new WC_Customer( $ct_fg_id );

$ct_fg = array(
	'billing_first_name' => $ct_fg_customer->get_billing_first_name(),
	'billing_last_name'  => $ct_fg_customer->get_billing_last_name(),
	'billing_company'    => $ct_fg_customer->get_billing_company(),
	'billing_address_1'  => $ct_fg_customer->get_billing_address_1(),
	'billing_address_2'  => $ct_fg_customer->get_billing_address_2(),
	'billing_postcode'   => $ct_fg_customer->get_billing_postcode(),
	'billing_city'       => $ct_fg_customer->get_billing_city(),
	'billing_country'    => $ct_fg_customer->get_billing_country(),
);
$ct_fg_vat = (string) get_user_meta( $ct_fg_id, 'billing_vat', true );

$ct_fg_action = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'factuurgegevens' ) : '';

$ct_fg_countries = array();
if ( function_exists( 'WC' ) && WC()->countries ) {
	$ct_fg_countries = WC()->countries->get_countries();
}
?>
<div class="ct-account-settings-center ct-account-settings-center--saas">
	<form class="ct-account-settings-form ct-account-settings-form--saas" method="post" action="<?php echo esc_url( $ct_fg_action ); ?>" autocomplete="on">
		<?php wp_nonce_field( 'coachtribe_factuurgegevens_save', 'coachtribe_factuurgegevens_nonce' ); ?>

		<article class="ct-account-settings-card ct-account-settings-card--open">
			<header class="ct-account-settings-card__header">
				<div class="ct-account-settings-card__headings">
					<h2 class="ct-account-settings-card__title"><?php esc_html_e( 'Persoonsgegevens', 'coachtribe-my-account' ); ?></h2>
				</div>
			</header>
			<div class="ct-account-settings-card__body">
				<div class="ct-account-settings-fields-grid ct-account-settings-fields-grid--billing">
					<div class="ct-account-settings-field">
						<label class="ct-account-settings-field__label" for="ct_fg_first"><?php esc_html_e( 'Voornaam', 'coachtribe-my-account' ); ?></label>
						<input class="ct-account-input" type="text" id="ct_fg_first" name="billing_first_name" value="<?php echo esc_attr( $ct_fg['billing_first_name'] ); ?>" autocomplete="given-name" />
					</div>
					<div class="ct-account-settings-field">
						<label class="ct-account-settings-field__label" for="ct_fg_last"><?php esc_html_e( 'Achternaam', 'coachtribe-my-account' ); ?></label>
						<input class="ct-account-input" type="text" id="ct_fg_last" name="billing_last_name" value="<?php echo esc_attr( $ct_fg['billing_last_name'] ); ?>" autocomplete="family-name" />
					</div>
					<div class="ct-account-settings-field">
						<label class="ct-account-settings-field__label" for="ct_fg_company"><?php esc_html_e( 'Bedrijfsnaam', 'coachtribe-my-account' ); ?></label>
						<input class="ct-account-input" type="text" id="ct_fg_company" name="billing_company" value="<?php echo esc_attr( $ct_fg['billing_company'] ); ?>" autocomplete="organization" />
					</div>
					<div class="ct-account-settings-field">
						<label class="ct-account-settings-field__label" for="ct_fg_vat"><?php esc_html_e( 'BTW-nummer', 'coachtribe-my-account' ); ?></label>
						<input class="ct-account-input" type="text" id="ct_fg_vat" name="billing_vat" value="<?php echo esc_attr( $ct_fg_vat ); ?>" />
					</div>
				</div>
			</div>
		</article>

		<article class="ct-account-settings-card ct-account-settings-card--open">
			<header class="ct-account-settings-card__header">
				<div class="ct-account-settings-card__headings">
					<h2 class="ct-account-settings-card__title"><?php esc_html_e( 'Factuuradres', 'coachtribe-my-account' ); ?></h2>
				</div>
			</header>
			<div class="ct-account-settings-card__body">
				<div class="ct-account-settings-field">
					<label class="ct-account-settings-field__label" for="ct_fg_address1"><?php esc_html_e( 'Adres', 'coachtribe-my-account' ); ?></label>
					<input class="ct-account-input" type="text" id="ct_fg_address1" name="billing_address_1" value="<?php echo esc_attr( $ct_fg['billing_address_1'] ); ?>" autocomplete="address-line1" />
				</div>
				<div class="ct-account-settings-field">
					<label class="ct-account-settings-field__label" for="ct_fg_address2"><?php esc_html_e( 'Adresregel 2 (optioneel)', 'coachtribe-my-account' ); ?></label>
					<input class="ct-account-input" type="text" id="ct_fg_address2" name="billing_address_2" value="<?php echo esc_attr( $ct_fg['billing_address_2'] ); ?>" autocomplete="address-line2" />
				</div>
				<div class="ct-account-settings-fields-grid ct-account-settings-fields-grid--billing">
					<div class="ct-account-settings-field">
						<label class="ct-account-settings-field__label" for="ct_fg_postcode"><?php esc_html_e( 'Postcode', 'coachtribe-my-account' ); ?></label>
						<input class="ct-account-input" type="text" id="ct_fg_postcode" name="billing_postcode" value="<?php echo esc_attr( $ct_fg['billing_postcode'] ); ?>" autocomplete="postal-code" />
					</div>
					<div class="ct-account-settings-field">
						<label class="ct-account-settings-field__label" for="ct_fg_city"><?php esc_html_e( 'Plaats', 'coachtribe-my-account' ); ?></label>
						<input class="ct-account-input" type="text" id="ct_fg_city" name="billing_city" value="<?php echo esc_attr( $ct_fg['billing_city'] ); ?>" autocomplete="address-level2" />
					</div>
					<div class="ct-account-settings-field">
						<label class="ct-account-settings-field__label" for="ct_fg_country"><?php esc_html_e( 'Land', 'coachtribe-my-account' ); ?></label>
						<?php if ( ! empty( $ct_fg_countries ) ) : ?>
						<select class="ct-account-input" id="ct_fg_country" name="billing_country" autocomplete="country">
							<option value=""><?php esc_html_e( '— Selecteer land —', 'coachtribe-my-account' ); ?></option>
							<?php foreach ( $ct_fg_countries as $ct_fg_code => $ct_fg_label ) : ?>
								<option value="<?php echo esc_attr( $ct_fg_code ); ?>" <?php selected( $ct_fg['billing_country'], $ct_fg_code ); ?>><?php echo esc_html( $ct_fg_label ); ?></option>
							<?php endforeach; ?>
						</select>
						<?php else : ?>
						<input class="ct-account-input" type="text" id="ct_fg_country" name="billing_country" value="<?php echo esc_attr( $ct_fg['billing_country'] ); ?>" autocomplete="country" />
						<?php endif; ?>
					</div>
				</div>
			</div>
		</article>

		<div class="ct-account-settings-save-wrap--ref">
			<button type="submit" name="coachtribe_factuurgegevens_submit" value="1" class="ct-account-submit-button ct-account-submit-button--saas ct-account-submit-button--ref-save">
				<svg class="ct-account-submit-button__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
					<path d="M19 21H5a2 2 0 01-2-2V7l4-4h10l4 4v12a2 2 0 01-2 2z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
					<path d="M9 21v-6h6v6M9 3v4h6V3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
				<span class="ct-account-submit-button__label"><?php esc_html_e( 'Wijzigingen opslaan', 'coachtribe-my-account' ); ?></span>
			</button>
		</div>
	</form>
</div>
