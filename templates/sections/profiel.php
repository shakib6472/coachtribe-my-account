<?php
/**
 * Sectie: Jouw profiel.
 *
 * @package CoachTribe_My_Account
 */

defined( 'ABSPATH' ) || exit;

$ct_prof_user = wp_get_current_user();
$ct_prof_id   = (int) $ct_prof_user->ID;

$ct_prof_name = $ct_prof_user->display_name;
if ( '' === trim( (string) $ct_prof_name ) ) {
	$first = get_user_meta( $ct_prof_id, 'first_name', true );
	$last  = get_user_meta( $ct_prof_id, 'last_name', true );
	$full  = trim( $first . ' ' . $last );
	$ct_prof_name = '' !== $full ? $full : $ct_prof_user->user_login;
}

$ct_prof_email = $ct_prof_user->user_email;

$ct_prof_phone = '';
foreach ( array( 'billing_phone', 'shipping_phone', 'phone', 'telefoon' ) as $ct_prof_meta_key ) {
	$ct_candidate = get_user_meta( $ct_prof_id, $ct_prof_meta_key, true );
	if ( is_string( $ct_candidate ) && '' !== trim( $ct_candidate ) ) {
		$ct_prof_phone = trim( $ct_candidate );
		break;
	}
}

$ct_prof_phone = apply_filters( 'coachtribe_my_account_profile_phone', $ct_prof_phone, $ct_prof_user );

$ct_prof_edit_url = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'instellingen' ) : home_url( '/' );
$ct_prof_edit_url = apply_filters( 'coachtribe_my_account_profile_edit_url', $ct_prof_edit_url, $ct_prof_user );

$ct_avatar_alt = sprintf(
	/* translators: %s: naam of gebruikersnaam */
	__( 'Profielfoto van %s', 'coachtribe-my-account' ),
	$ct_prof_name
);

$ct_prof_image_url = function_exists( 'coachtribe_my_account_get_profile_image_url' )
	? coachtribe_my_account_get_profile_image_url( $ct_prof_id )
	: '';

$ct_avatar_html = get_avatar(
	$ct_prof_user->ID,
	120,
	'',
	$ct_avatar_alt,
	array(
		'class' => 'ct-account-profiel__avatar-img',
	)
);

$ct_profile_display_input = trim( (string) $ct_prof_user->display_name );
if ( '' === $ct_profile_display_input ) {
	$ct_profile_display_input = $ct_prof_name;
}

$ct_summary_name = $ct_profile_display_input;

$ct_profile_saved_flag = isset( $_GET['ct_profile_saved'] ) && '1' === sanitize_text_field( wp_unslash( (string) $_GET['ct_profile_saved'] ) );
$ct_profile_form_collapsed = $ct_profile_saved_flag;

$ct_profile_form_action = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'dashboard' ) : '';
?>
<section class="ct-account-profiel<?php echo $ct_profile_form_collapsed ? ' ct-account-profiel--form-collapsed' : ''; ?>" aria-labelledby="ct-account-profiel-title">
	<h2 id="ct-account-profiel-title" class="ct-account-profiel__title"><?php esc_html_e( 'Mijn persoonlijke gegevens', 'coachtribe-my-account' ); ?></h2>

	<div class="ct-account-profiel__card-row">
		<div class="ct-account-profiel__avatar ct-account-profiel__avatar-col">
			<div class="ct-account-profiel__avatar-figure">
				<div
					class="ct-account-profile-image"
					id="ct-account-profile-image-wrap"
					data-ct-profile-image-wrap
					data-ct-profile-img-alt="<?php echo esc_attr( $ct_avatar_alt ); ?>"
				>
					<?php if ( '' !== $ct_prof_image_url ) : ?>
						<img
							src="<?php echo esc_url( $ct_prof_image_url ); ?>"
							alt="<?php echo esc_attr( $ct_avatar_alt ); ?>"
							class="ct-account-profiel__avatar-img"
							width="120"
							height="120"
							decoding="async"
							data-ct-profile-img
						/>
					<?php else : ?>
						<div class="ct-account-profile-image__fallback">
							<?php echo $ct_avatar_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress core avatar markup ?>
						</div>
					<?php endif; ?>
				</div>

				<input
					type="file"
					id="ct-profile-image-file"
					class="ct-account-profile-image__file"
					name="profile_image"
					accept="image/jpeg,image/png,image/gif,image/webp"
					data-ct-profile-image-file
					aria-label="<?php esc_attr_e( 'Kies profielfoto', 'coachtribe-my-account' ); ?>"
					tabindex="-1"
					hidden
				/>

				<button
					type="button"
					class="ct-account-profile-image-upload ct-account-profile-image-upload--camera"
					data-ct-profile-image-upload
					aria-label="<?php esc_attr_e( 'Profielfoto wijzigen', 'coachtribe-my-account' ); ?>"
				>
					<svg class="ct-account-profile-image-upload__svg" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
						<path d="M4 9.5h2.5L8.2 7h7.6l1.7 2.5H20a2 2 0 012 2V18a2 2 0 01-2 2H4a2 2 0 01-2-2v-6.5a2 2 0 012-2z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
						<circle cx="12" cy="14.5" r="3.25" stroke="currentColor" stroke-width="1.75"/>
						<path d="M17.5 7V5.5a1 1 0 011-1h1" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
					</svg>
				</button>
			</div>

			<p class="ct-account-profile-image__status" id="ct-profile-image-status" role="status" aria-live="polite" hidden></p>
		</div>

		<div class="ct-account-profiel__col-middle">
			<div class="ct-account-profiel__summary" data-ct-profile-summary <?php echo $ct_profile_form_collapsed ? '' : 'hidden'; ?>>
				<p class="ct-account-profiel__summary-name"><?php echo esc_html( $ct_summary_name ); ?></p>
				<p class="ct-account-profiel__summary-email"><?php echo esc_html( $ct_prof_email ); ?></p>
				<?php if ( '' !== trim( (string) $ct_prof_phone ) ) : ?>
					<p class="ct-account-profiel__summary-phone"><?php echo esc_html( $ct_prof_phone ); ?></p>
				<?php endif; ?>
			</div>

			<div class="ct-account-profiel__main">
				<div
					class="ct-account-profiel__form-panel"
					id="ct-account-profile-edit-panel"
					data-ct-profile-form-panel
					<?php echo $ct_profile_form_collapsed ? 'hidden' : ''; ?>
				>
					<form
						class="ct-account-profile-edit-form"
						id="ct-account-profile-edit-form"
						method="post"
						action="<?php echo $ct_profile_form_action ? esc_url( $ct_profile_form_action ) : ''; ?>"
						autocomplete="on"
					>
						<?php wp_nonce_field( 'coachtribe_profile_edit', 'coachtribe_profile_edit_nonce' ); ?>
							<input type="hidden" name="ct_profile_display_name" value="<?php echo esc_attr( $ct_profile_display_input ); ?>" />

						<div class="ct-account-settings-field">
							<label class="ct-account-settings-field__label" for="ct_profile_username"><?php esc_html_e( 'Gebruikersnaam', 'coachtribe-my-account' ); ?></label>
							<input
								class="ct-account-input"
								type="text"
								name="ct_profile_display_name"
								id="ct_profile_username"
								value="<?php echo esc_attr( $ct_profile_display_input ); ?>"
								required
								maxlength="250"
								readonly
								aria-readonly="true"
								tabindex="-1"
							/>
						</div>

						<div class="ct-account-settings-field">
							<label class="ct-account-settings-field__label" for="ct_profile_email"><?php esc_html_e( 'E-mailadres', 'coachtribe-my-account' ); ?></label>
							<input
								class="ct-account-input"
								type="email"
								name="ct_profile_email"
								id="ct_profile_email"
								value="<?php echo esc_attr( $ct_prof_email ); ?>"
								required
								inputmode="email"
								autocomplete="email"
							/>
						</div>

						<div class="ct-account-settings-field">
							<label class="ct-account-settings-field__label" for="ct_profile_phone"><?php esc_html_e( 'Telefoonnummer', 'coachtribe-my-account' ); ?></label>
							<input
								class="ct-account-input"
								type="tel"
								name="ct_profile_phone"
								id="ct_profile_phone"
								value="<?php echo esc_attr( $ct_prof_phone ); ?>"
								autocomplete="tel"
							/>
						</div>

						<p class="ct-account-profile-edit-form__client-error" id="ct-profile-edit-client-error" role="alert" hidden></p>

						<div class="ct-account-settings-field ct-account-settings-field--submit">
							<button type="submit" name="coachtribe_profile_edit_submit" value="1" class="ct-account-submit-button">
								<?php esc_html_e( 'Wijzigingen opslaan', 'coachtribe-my-account' ); ?>
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>

		<button
			type="button"
			class="ct-account-profiel__form-toggle"
			data-ct-profile-form-toggle
			data-ct-profile-full-edit-url="<?php echo esc_url( $ct_prof_edit_url ); ?>"
			aria-controls="ct-account-profile-edit-panel"
			aria-expanded="<?php echo $ct_profile_form_collapsed ? 'false' : 'true'; ?>"
		>
			<svg class="ct-account-profiel__form-toggle-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
				<path d="M4 20.5L8.5 16l9-9a2 2 0 000-2.8l-1.3-1.3a2 2 0 00-2.8 0l-9 9L3.5 17" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
				<path d="M13.5 6.5l4 4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
			</svg>
			<span class="ct-account-profiel__form-toggle-label"><?php esc_html_e( 'Profiel bewerken', 'coachtribe-my-account' ); ?></span>
		</button>
	</div>
</section>
