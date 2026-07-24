<?php
/**
 * Profielfoto — overzicht (compact) of instellingen (reference met camera).
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

$ct_avatar_alt = sprintf(
	/* translators: %s: naam of gebruikersnaam */
	__( 'Profielfoto van %s', 'coachtribe-my-account' ),
	$ct_prof_name
);

$ct_prof_image_url = function_exists( 'coachtribe_my_account_get_profile_image_url' )
	? coachtribe_my_account_get_profile_image_url( $ct_prof_id )
	: '';

$ct_profile_layout = isset( $ct_profile_avatar_layout ) ? (string) $ct_profile_avatar_layout : 'default';
$ct_is_settings    = ( 'settings' === $ct_profile_layout );

$ct_prof_initial = function_exists( 'mb_substr' )
	? strtoupper( mb_substr( $ct_prof_name, 0, 1 ) )
	: strtoupper( substr( $ct_prof_name, 0, 1 ) );

$ct_avatar_size = $ct_is_settings ? 144 : 96;
$ct_avatar_html = get_avatar(
	$ct_prof_user->ID,
	$ct_avatar_size,
	'',
	$ct_avatar_alt,
	array(
		'class' => 'ct-account-profiel__avatar-img',
	)
);

$ct_profile_header_class = 'ct-account-profile-header';
if ( $ct_is_settings ) {
	$ct_profile_header_class .= ' ct-account-profile-header--settings';
}

$ct_image_wrap_class = 'ct-account-profile-image ct-account-profile-image--header';
if ( $ct_is_settings ) {
	$ct_image_wrap_class = 'ct-account-profile-image ct-account-profile-image--settings';
}

$ct_file_input_id = $ct_is_settings ? 'ct-profile-image-file-settings' : 'ct-profile-image-file-header';
?>
<div class="<?php echo esc_attr( $ct_profile_header_class ); ?>" data-ct-profile-header>
	<div class="ct-account-profile-header__figure ct-account-profiel__avatar-col">
		<div
			class="<?php echo esc_attr( $ct_image_wrap_class ); ?>"
			data-ct-profile-image-wrap
			data-ct-profile-img-alt="<?php echo esc_attr( $ct_avatar_alt ); ?>"
		>
			<?php if ( '' !== $ct_prof_image_url ) : ?>
				<img
					src="<?php echo esc_url( $ct_prof_image_url ); ?>"
					alt="<?php echo esc_attr( $ct_avatar_alt ); ?>"
					class="ct-account-profiel__avatar-img"
					width="<?php echo esc_attr( (string) $ct_avatar_size ); ?>"
					height="<?php echo esc_attr( (string) $ct_avatar_size ); ?>"
					decoding="async"
					data-ct-profile-img
				/>
			<?php elseif ( $ct_is_settings ) : ?>
				<span class="ct-account-profile-image__initial" aria-hidden="true"><?php echo esc_html( $ct_prof_initial ); ?></span>
			<?php else : ?>
				<div class="ct-account-profile-image__fallback">
					<?php echo $ct_avatar_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress core avatar markup ?>
				</div>
			<?php endif; ?>
		</div>

		<input
			type="file"
			id="<?php echo esc_attr( $ct_file_input_id ); ?>"
			class="ct-account-profile-image__file"
			name="profile_image"
			accept="image/jpeg,image/png,image/gif,image/webp"
			data-ct-profile-image-file
			aria-label="<?php esc_attr_e( 'Kies profielfoto', 'coachtribe-my-account' ); ?>"
			tabindex="-1"
			hidden
		/>

		<?php if ( $ct_is_settings ) : ?>
			<button
				type="button"
				class="ct-account-profile-header__camera"
				data-ct-profile-image-upload
				aria-label="<?php esc_attr_e( 'Foto wijzigen', 'coachtribe-my-account' ); ?>"
			>
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
					<path d="M4 8h2l1.5-2h9L18 8h2a2 2 0 012 2v9a2 2 0 01-2 2H4a2 2 0 01-2-2V10a2 2 0 012-2z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
					<circle cx="12" cy="13" r="3.25" stroke="currentColor" stroke-width="1.75"/>
				</svg>
			</button>
		<?php endif; ?>
	</div>

	<?php if ( ! $ct_is_settings ) : ?>
	<button
		type="button"
		class="ct-foto-btn ct-account-profile-header__change"
		data-ct-profile-image-upload
		aria-label="<?php esc_attr_e( 'Foto wijzigen', 'coachtribe-my-account' ); ?>"
	>
		<svg class="ct-foto-btn__icon" width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
			<path d="M4 20.5L8.5 16l9-9a2 2 0 000-2.8l-1.3-1.3a2 2 0 00-2.8 0l-9 9L3.5 17" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
			<path d="M13.5 6.5l4 4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
		</svg>
		<span class="ct-account-profile-header__change-label"><?php esc_html_e( 'Foto wijzigen', 'coachtribe-my-account' ); ?></span>
	</button>
	<?php endif; ?>

	<p class="ct-account-profile-image__status ct-account-profile-header__status" data-ct-profile-image-status role="status" aria-live="polite" hidden></p>
</div>
