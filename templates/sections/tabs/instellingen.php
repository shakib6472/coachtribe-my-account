<?php
/**
 * Tab: Accountinstellingen.
 *
 * @package CoachTribe_My_Account
 */

defined( 'ABSPATH' ) || exit;

$ct_set_user = wp_get_current_user();
$ct_set_id   = (int) $ct_set_user->ID;

$ct_set_email = $ct_set_user->user_email;
$ct_set_phone = get_user_meta( $ct_set_id, 'billing_phone', true );
if ( '' === (string) $ct_set_phone ) {
	$ct_set_phone = get_user_meta( $ct_set_id, 'telefoon', true );
}
$ct_set_phone = is_string( $ct_set_phone ) ? $ct_set_phone : '';

$ct_set_phone = apply_filters( 'coachtribe_my_account_settings_phone_value', $ct_set_phone, $ct_set_user );

$ct_pw_url = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'wachtwoord' ) : '';
$ct_set_form_action = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'instellingen' ) : '';

$ct_billing_fields = array();
if ( class_exists( 'WC_Customer' ) ) {
	$ct_set_customer = new WC_Customer( $ct_set_id );
	$ct_billing_fields = array(
		'billing_first_name' => array(
			'label' => __( 'Voornaam', 'coachtribe-my-account' ),
			'value' => $ct_set_customer->get_billing_first_name(),
		),
		'billing_last_name'  => array(
			'label' => __( 'Achternaam', 'coachtribe-my-account' ),
			'value' => $ct_set_customer->get_billing_last_name(),
		),
		'billing_company'    => array(
			'label' => __( 'Bedrijfsnaam', 'coachtribe-my-account' ),
			'value' => $ct_set_customer->get_billing_company(),
		),
		'billing_address_1'  => array(
			'label' => __( 'Adres', 'coachtribe-my-account' ),
			'value' => $ct_set_customer->get_billing_address_1(),
		),
		'billing_address_2'  => array(
			'label' => __( 'Adres regel 2', 'coachtribe-my-account' ),
			'value' => $ct_set_customer->get_billing_address_2(),
		),
		'billing_postcode'   => array(
			'label' => __( 'Postcode', 'coachtribe-my-account' ),
			'value' => $ct_set_customer->get_billing_postcode(),
		),
		'billing_city'       => array(
			'label' => __( 'Plaats', 'coachtribe-my-account' ),
			'value' => $ct_set_customer->get_billing_city(),
		),
		'billing_country'    => array(
			'label' => __( 'Land', 'coachtribe-my-account' ),
			'value' => $ct_set_customer->get_billing_country(),
		),
	);
}

$ct_hulp_email = apply_filters( 'coachtribe_my_account_support_email', 'info@coachtribe.nl' );
$ct_hulp_phone = apply_filters( 'coachtribe_my_account_support_phone_display', '020 - 261 07 07' );
$ct_hulp_tel   = apply_filters( 'coachtribe_my_account_support_phone_tel', '+31202610707' );
$ct_hulp_mailto = 'mailto:' . $ct_hulp_email;

$ct_sections_dir = COACHTRIBE_MY_ACCOUNT_PATH . 'templates/sections/';
?>
<div class="ct-account-settings-center ct-account-settings-center--saas">
	<form class="ct-account-settings-form ct-account-settings-form--saas" method="post" action="<?php echo esc_url( $ct_set_form_action ); ?>" autocomplete="on">
		<?php wp_nonce_field( 'coachtribe_settings_save', 'coachtribe_settings_nonce' ); ?>

		<article class="ct-account-settings-card ct-account-settings-card--open ct-account-settings-card--profile-ref ct-account-settings-card--profile-gegevens" aria-labelledby="ct-settings-card-profile-title">
			<header class="ct-account-settings-card__header">
				<span class="ct-account-settings-card__icon ct-account-settings-card__icon--profile" aria-hidden="true">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
						<circle cx="12" cy="8" r="3.5" fill="currentColor"/>
						<path d="M6 20c0-3.3 2.7-6 6-6s6 2.7 6 6" fill="currentColor"/>
					</svg>
				</span>
				<div class="ct-account-settings-card__headings">
					<h2 id="ct-settings-card-profile-title" class="ct-account-settings-card__title"><?php esc_html_e( 'Profielgegevens', 'coachtribe-my-account' ); ?></h2>
					<p class="ct-account-settings-card__subtitle"><?php esc_html_e( 'Beheer je persoonlijke gegevens en profielinformatie.', 'coachtribe-my-account' ); ?></p>
				</div>
			</header>
			<div class="ct-account-settings-card__body ct-account-settings-card__body--profile ct-account-settings-profile-gegevens">
				<div class="ct-account-settings-card__avatar-col">
					<?php
					$ct_profile_avatar_layout = 'settings';
					require $ct_sections_dir . 'profile-avatar-header.php';
					?>
				</div>
				<div class="ct-account-settings-card__fields-col">
					<div class="ct-account-settings-fields-grid">
						<div class="ct-account-settings-field">
							<label class="ct-account-settings-field__label" for="ct_settings_username"><?php esc_html_e( 'Gebruikersnaam', 'coachtribe-my-account' ); ?></label>
							<input
								class="ct-account-input ct-account-settings-username"
								type="text"
								id="ct_settings_username"
								value="<?php echo esc_attr( $ct_set_user->user_login ); ?>"
								readonly
								aria-readonly="true"
							/>
							<p class="ct-account-settings-field__hint"><?php esc_html_e( 'Je gebruikersnaam kan niet worden gewijzigd.', 'coachtribe-my-account' ); ?></p>
						</div>

						<div class="ct-account-settings-field">
							<label class="ct-account-settings-field__label" for="ct_settings_phone"><?php esc_html_e( 'Telefoonnummer (optioneel)', 'coachtribe-my-account' ); ?></label>
							<input
								class="ct-account-input"
								type="tel"
								name="ct_settings_phone"
								id="ct_settings_phone"
								value="<?php echo esc_attr( $ct_set_phone ); ?>"
								placeholder="<?php esc_attr_e( 'Voer je telefoonnummer in', 'coachtribe-my-account' ); ?>"
								autocomplete="tel"
							/>
							<p class="ct-account-settings-field__hint"><?php esc_html_e( 'We gebruiken dit alleen voor belangrijke communicatie.', 'coachtribe-my-account' ); ?></p>
						</div>

						<div class="ct-account-settings-field">
							<label class="ct-account-settings-field__label" for="ct_settings_email"><?php esc_html_e( 'E-mailadres', 'coachtribe-my-account' ); ?></label>
							<input
								class="ct-account-input"
								type="email"
								name="ct_settings_email"
								id="ct_settings_email"
								value="<?php echo esc_attr( $ct_set_email ); ?>"
								required
								inputmode="email"
								autocomplete="email"
							/>
							<p class="ct-account-settings-field__hint"><?php esc_html_e( 'Na een wijziging ontvang je een bevestigingsmail.', 'coachtribe-my-account' ); ?></p>
						</div>
					</div>
				</div>
			</div>
		</article>

		<div class="ct-account-settings-stack--ref">
			<details class="ct-account-settings-card ct-account-settings-card--accordion ct-account-settings-card--row-ref">
				<summary class="ct-account-settings-card__summary">
					<span class="ct-account-settings-row-icon" aria-hidden="true">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
							<rect x="3" y="6" width="18" height="12" rx="2" stroke="currentColor" stroke-width="1.75"/>
							<path d="M3 10h18" stroke="currentColor" stroke-width="1.75"/>
							<path d="M7 15h4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
						</svg>
					</span>
					<span class="ct-account-settings-card__summary-text">
						<span class="ct-account-settings-card__title"><?php esc_html_e( 'Factuurgegevens', 'coachtribe-my-account' ); ?></span>
						<span class="ct-account-settings-card__subtitle"><?php esc_html_e( 'Beheer je factuur- en betaalgegevens.', 'coachtribe-my-account' ); ?></span>
					</span>
					<span class="ct-account-settings-card__chevron" aria-hidden="true">
						<svg width="10" height="16" viewBox="0 0 10 16" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
							<path d="M2 2l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</span>
				</summary>
				<div class="ct-account-settings-card__body">
					<div class="ct-account-settings-fields-grid ct-account-settings-fields-grid--billing">
						<?php foreach ( $ct_billing_fields as $ct_bill_key => $ct_bill_field ) : ?>
							<div class="ct-account-settings-field">
								<label class="ct-account-settings-field__label" for="ct_settings_<?php echo esc_attr( $ct_bill_key ); ?>"><?php echo esc_html( $ct_bill_field['label'] ); ?></label>
								<input
									class="ct-account-input ct-account-settings-billing-field"
									type="text"
									id="ct_settings_<?php echo esc_attr( $ct_bill_key ); ?>"
									value="<?php echo esc_attr( $ct_bill_field['value'] ); ?>"
									readonly
									aria-readonly="true"
									tabindex="-1"
								/>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</details>

			<?php if ( '' !== $ct_pw_url ) : ?>
			<a class="ct-account-settings-card ct-account-settings-card--row-ref ct-account-settings-row-link" href="<?php echo esc_url( $ct_pw_url ); ?>" data-ct-tab="wachtwoord">
				<span class="ct-account-settings-row-icon" aria-hidden="true">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
						<rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.75"/>
						<path d="M8 11V8a4 4 0 118 0v3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
					</svg>
				</span>
				<span class="ct-account-settings-card__summary-text">
					<span class="ct-account-settings-card__title"><?php esc_html_e( 'Wachtwoord', 'coachtribe-my-account' ); ?></span>
					<span class="ct-account-settings-card__subtitle"><?php esc_html_e( 'Verander je wachtwoord of reset het als je het bent vergeten.', 'coachtribe-my-account' ); ?></span>
				</span>
				<span class="ct-account-settings-card__chevron" aria-hidden="true">
					<svg width="10" height="16" viewBox="0 0 10 16" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
						<path d="M2 2l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</span>
			</a>
			<?php endif; ?>
		</div>

		<p class="ct-account-settings-form__client-error" id="ct-settings-client-error" role="alert" hidden></p>

		<div class="ct-account-settings-save-wrap--ref">
			<button type="submit" name="coachtribe_settings_submit" value="1" class="ct-account-submit-button ct-account-submit-button--saas ct-account-submit-button--ref-save">
				<svg class="ct-account-submit-button__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
					<path d="M19 21H5a2 2 0 01-2-2V7l4-4h10l4 4v12a2 2 0 01-2 2z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
					<path d="M9 21v-6h6v6M9 3v4h6V3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
				<span class="ct-account-submit-button__label"><?php esc_html_e( 'Wijzigingen opslaan', 'coachtribe-my-account' ); ?></span>
			</button>
		</div>
	</form>
</div>
