<?php
/**
 * Tab: Wachtwoord veranderen.
 *
 * @package CoachTribe_My_Account
 */

defined( 'ABSPATH' ) || exit;

$ct_pw_action = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'wachtwoord' ) : '';
?>
<section class="ct-account-password" aria-labelledby="ct-account-password-change-title">
	<h2 id="ct-account-password-change-title" class="ct-account-password__title"><?php esc_html_e( 'Wachtwoord Veranderen', 'coachtribe-my-account' ); ?></h2>

	<form class="ct-account-password-change-form" method="post" action="<?php echo esc_url( $ct_pw_action ); ?>" autocomplete="off">
		<?php wp_nonce_field( 'coachtribe_password_change', 'coachtribe_password_nonce' ); ?>

		<div class="ct-account-password-field">
			<label class="ct-account-password-field__label" for="ct_current_password"><?php esc_html_e( 'Huidig wachtwoord', 'coachtribe-my-account' ); ?></label>
			<input
				class="ct-account-input"
				type="password"
				name="ct_current_password"
				id="ct_current_password"
				autocomplete="current-password"
				required
			/>
		</div>

		<div class="ct-account-password-field">
			<label class="ct-account-password-field__label" for="ct_new_password"><?php esc_html_e( 'Nieuw wachtwoord', 'coachtribe-my-account' ); ?></label>
			<input
				class="ct-account-input"
				type="password"
				name="ct_new_password"
				id="ct_new_password"
				autocomplete="new-password"
				required
			/>
		</div>

		<div class="ct-account-password-field">
			<label class="ct-account-password-field__label" for="ct_confirm_password"><?php esc_html_e( 'Bevestig nieuw wachtwoord', 'coachtribe-my-account' ); ?></label>
			<input
				class="ct-account-input"
				type="password"
				name="ct_confirm_password"
				id="ct_confirm_password"
				autocomplete="new-password"
				required
			/>
		</div>

		<p class="ct-account-password-change-form__hint" id="ct-password-client-error" role="alert" hidden></p>

		<div class="ct-account-password-field ct-account-password-field--submit">
			<button type="submit" name="coachtribe_password_change_submit" value="1" class="ct-account-submit-button">
				<?php esc_html_e( 'Wijzig Wachtwoord', 'coachtribe-my-account' ); ?>
			</button>
		</div>
	</form>
</section>
