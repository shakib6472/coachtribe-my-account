<?php
/**
 * Mijn account — hoofdlay-out met zijbalk en inhoudsgebied.
 *
 * @package CoachTribe_My_Account
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'WC' ) || ! WC()->query ) {
	return;
}

if ( isset( $GLOBALS['coachtribe_my_account_forced_endpoint'] ) && is_string( $GLOBALS['coachtribe_my_account_forced_endpoint'] ) && '' !== $GLOBALS['coachtribe_my_account_forced_endpoint'] ) {
	$ct_endpoint = $GLOBALS['coachtribe_my_account_forced_endpoint'];
} else {
	$ct_endpoint = (string) WC()->query->get_current_endpoint();
	if ( '' === $ct_endpoint && function_exists( 'coachtribe_my_account_detect_endpoint_from_url' ) ) {
		$ct_endpoint = coachtribe_my_account_detect_endpoint_from_url();
	}
}
$ct_user     = wp_get_current_user();
$ct_name     = $ct_user->display_name ? $ct_user->display_name : $ct_user->user_login;
$ct_initial  = function_exists( 'mb_substr' )
	? strtoupper( mb_substr( $ct_name, 0, 1 ) )
	: strtoupper( substr( $ct_name, 0, 1 ) );

$ct_header_avatar_url = function_exists( 'coachtribe_my_account_get_header_avatar_url' )
	? coachtribe_my_account_get_header_avatar_url( (int) $ct_user->ID )
	: '';
$ct_header_avatar_alt = sprintf(
	/* translators: %s: naam of gebruikersnaam */
	__( 'Profielfoto van %s', 'coachtribe-my-account' ),
	$ct_name
);

$ct_is_overzicht       = ( '' === $ct_endpoint || 'dashboard' === $ct_endpoint );
$ct_is_facturen        = ( 'facturen' === $ct_endpoint );
$ct_is_instellingen    = ( 'instellingen' === $ct_endpoint );
$ct_is_factuurgegevens = ( 'factuurgegevens' === $ct_endpoint );
$ct_is_opzeggen        = ( 'opzeggen' === $ct_endpoint );
$ct_is_betaalmethode   = ( 'payment-methods' === $ct_endpoint );
$ct_is_wachtwoord      = ( 'wachtwoord' === $ct_endpoint );

$ct_overzicht_url       = wc_get_account_endpoint_url( 'dashboard' );
$ct_facturen_url        = wc_get_account_endpoint_url( 'facturen' );
$ct_instellingen_url    = wc_get_account_endpoint_url( 'instellingen' );
$ct_factuurgegevens_url = wc_get_account_endpoint_url( 'factuurgegevens' );
$ct_opzeggen_url        = wc_get_account_endpoint_url( 'opzeggen' );
$ct_wachtwoord_url      = wc_get_account_endpoint_url( 'wachtwoord' );
$ct_logout_url          = wc_logout_url( wc_get_page_permalink( 'myaccount' ) );

// Membership type drives which navigation items appear (woocommerce | plug_and_pay | gratis).
$ct_member_type    = function_exists( 'coachtribe_my_account_get_member_type' ) ? coachtribe_my_account_get_member_type() : 'woocommerce';
$ct_is_wc_member   = ( 'woocommerce' === $ct_member_type );
$ct_is_plugandpay  = ( 'plug_and_pay' === $ct_member_type );
$ct_is_free        = ( 'gratis' === $ct_member_type );
$ct_plugandpay_url = function_exists( 'coachtribe_my_account_plugandpay_url' ) ? coachtribe_my_account_plugandpay_url() : '';

// Free members have no invoices; Plug&Pay members read invoices on the external portal.
$ct_show_facturen        = ! $ct_is_free;
$ct_facturen_nav_url     = ( $ct_is_plugandpay && '' !== $ct_plugandpay_url ) ? $ct_plugandpay_url : $ct_facturen_url;
$ct_facturen_is_external = ( $ct_is_plugandpay && '' !== $ct_plugandpay_url );
// The editable billing-details page is only for WooCommerce members.
$ct_show_factuurgegevens = $ct_is_wc_member;
// Free members have no quick cards, so they get a dedicated cancellation tab instead.
$ct_show_opzeggen_tab = $ct_is_free;

$ct_sections_dir = COACHTRIBE_MY_ACCOUNT_PATH . 'templates/sections/';
require_once COACHTRIBE_MY_ACCOUNT_PATH . 'templates/partials/sidebar-nav-icons.php';

$ct_tab_slug = function_exists( 'coachtribe_my_account_normalize_tab_slug' )
	? coachtribe_my_account_normalize_tab_slug( $ct_endpoint )
	: 'dashboard';

$ct_tab_panel_id = 'wc-default' === $ct_tab_slug ? 'ct-tab-panel-wc-default' : 'ct-tab-panel-' . $ct_tab_slug;
?>
<div class="ct-account ct-account-mobile<?php echo $ct_is_overzicht ? ' ct-account--tab-overzicht' : ''; ?><?php echo $ct_is_instellingen ? ' ct-account--tab-instellingen' : ''; ?>" data-ct-account-root>
	<aside class="ct-account-sidebar ct-account-sidebar--saas" aria-label="<?php esc_attr_e( 'Accountnavigatie', 'coachtribe-my-account' ); ?>">
		<div class="ct-account-sidebar__brand-row ct-account-sidebar__brand-row--no-logo">
			<button
				type="button"
				class="ct-account-mobile-menu-toggle"
				data-ct-account-menu-toggle
				aria-expanded="false"
				aria-controls="ct-account-nav-drawer"
			>
				<span class="ct-account-mobile-menu-toggle__label"><?php esc_html_e( 'Menu', 'coachtribe-my-account' ); ?></span>
				<span class="ct-account-mobile-menu-toggle__bars" aria-hidden="true">
					<span class="ct-account-mobile-menu-toggle__bar"></span>
					<span class="ct-account-mobile-menu-toggle__bar"></span>
					<span class="ct-account-mobile-menu-toggle__bar"></span>
				</span>
			</button>
		</div>

		<div class="ct-account-sidebar__mobile-tabs">
			<label class="ct-account-sidebar__mobile-tabs-label" for="ct-account-tab-select"><?php esc_html_e( 'Ga naar', 'coachtribe-my-account' ); ?></label>
			<select
				id="ct-account-tab-select"
				class="ct-account-tab-select ct-account-mobile-tab-select"
				name="ct_account_tab_select"
				data-ct-account-tab-select
				aria-label="<?php esc_attr_e( 'Accountmenu', 'coachtribe-my-account' ); ?>"
			>
				<?php if ( 'wc-default' === $ct_tab_slug ) : ?>
					<option value="" disabled selected><?php esc_html_e( 'Huidige pagina (menu)', 'coachtribe-my-account' ); ?></option>
				<?php endif; ?>
				<option value="dashboard" data-url="<?php echo esc_url( $ct_overzicht_url ); ?>" <?php selected( $ct_tab_slug, 'dashboard' ); ?>><?php esc_html_e( 'Mijn account', 'coachtribe-my-account' ); ?></option>
				<?php if ( $ct_is_wc_member ) : ?><option value="facturen" data-url="<?php echo esc_url( $ct_facturen_url ); ?>" <?php selected( $ct_tab_slug, 'facturen' ); ?>><?php esc_html_e( 'Facturen', 'coachtribe-my-account' ); ?></option>
				<option value="factuurgegevens" data-url="<?php echo esc_url( $ct_factuurgegevens_url ); ?>" <?php selected( $ct_tab_slug, 'factuurgegevens' ); ?>><?php esc_html_e( 'Factuurgegevens', 'coachtribe-my-account' ); ?></option><?php endif; ?>
				<?php if ( $ct_show_opzeggen_tab ) : ?>
					<option value="opzeggen" data-url="<?php echo esc_url( $ct_opzeggen_url ); ?>" <?php selected( $ct_tab_slug, 'opzeggen' ); ?>><?php esc_html_e( 'Abonnement opzeggen', 'coachtribe-my-account' ); ?></option>
				<?php endif; ?>
				<option value="wachtwoord" data-url="<?php echo esc_url( $ct_wachtwoord_url ); ?>" <?php selected( $ct_tab_slug, 'wachtwoord' ); ?>><?php esc_html_e( 'Wachtwoord veranderen', 'coachtribe-my-account' ); ?></option>
			</select>
		</div>

		<div id="ct-account-nav-drawer" class="ct-account-sidebar__nav-drawer" data-ct-account-nav-drawer>
			<nav class="ct-account-sidebar__nav" aria-label="<?php esc_attr_e( 'Hoofdmenu', 'coachtribe-my-account' ); ?>">
			<ul class="ct-account-sidebar__list">
				<li class="ct-account-sidebar__item">
					<a
						class="ct-account-sidebar__link ct-account-tab<?php echo $ct_is_overzicht ? ' is-active' : ''; ?>"
						href="<?php echo esc_url( $ct_overzicht_url ); ?>"
						data-ct-tab="dashboard"
					>
						<span class="ct-account-sidebar__icon" aria-hidden="true"><?php echo $ct_is_overzicht ? coachtribe_my_account_sidebar_nav_icon( 'home-solid' ) : coachtribe_my_account_sidebar_nav_icon( 'home-outline' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span class="ct-account-sidebar__label"><?php esc_html_e( 'Mijn account', 'coachtribe-my-account' ); ?></span>
					</a>
				</li>
				<?php if ( $ct_show_facturen ) : ?>
				<li class="ct-account-sidebar__item">
					<?php if ( $ct_facturen_is_external ) : ?>
					<a
						class="ct-account-sidebar__link"
						href="<?php echo esc_url( $ct_facturen_nav_url ); ?>"
						target="_blank"
						rel="noopener noreferrer"
					>
						<span class="ct-account-sidebar__icon" aria-hidden="true"><?php echo coachtribe_my_account_sidebar_nav_icon( 'invoice' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span class="ct-account-sidebar__label"><?php esc_html_e( 'Facturen', 'coachtribe-my-account' ); ?></span>
					</a>
					<?php else : ?>
					<a
						class="ct-account-sidebar__link ct-account-tab<?php echo $ct_is_facturen ? ' is-active' : ''; ?>"
						href="<?php echo esc_url( $ct_facturen_url ); ?>"
						data-ct-tab="facturen"
					>
						<span class="ct-account-sidebar__icon" aria-hidden="true"><?php echo coachtribe_my_account_sidebar_nav_icon( 'invoice' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span class="ct-account-sidebar__label"><?php esc_html_e( 'Facturen', 'coachtribe-my-account' ); ?></span>
					</a>
					<?php endif; ?>
				</li>
				<?php endif; ?>
				<?php if ( $ct_show_factuurgegevens ) : ?>
				<li class="ct-account-sidebar__item">
					<a
						class="ct-account-sidebar__link ct-account-tab<?php echo $ct_is_factuurgegevens ? ' is-active' : ''; ?>"
						href="<?php echo esc_url( $ct_factuurgegevens_url ); ?>"
						data-ct-tab="factuurgegevens"
					>
						<span class="ct-account-sidebar__icon" aria-hidden="true"><?php echo coachtribe_my_account_sidebar_nav_icon( 'invoice' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span class="ct-account-sidebar__label"><?php esc_html_e( 'Factuurgegevens', 'coachtribe-my-account' ); ?></span>
					</a>
				</li>
				<?php endif; ?>
				<li class="ct-account-sidebar__item">
					<a
						class="ct-account-sidebar__link ct-account-tab<?php echo $ct_is_wachtwoord ? ' is-active' : ''; ?>"
						href="<?php echo esc_url( $ct_wachtwoord_url ); ?>"
						data-ct-tab="wachtwoord"
					>
						<span class="ct-account-sidebar__icon" aria-hidden="true"><?php echo coachtribe_my_account_sidebar_nav_icon( 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span class="ct-account-sidebar__label"><?php esc_html_e( 'Wachtwoord veranderen', 'coachtribe-my-account' ); ?></span>
					</a>
				</li>
				<?php if ( $ct_show_opzeggen_tab ) : ?>
					<li class="ct-account-sidebar__item">
						<a
							class="ct-account-sidebar__link ct-account-tab<?php echo $ct_is_opzeggen ? ' is-active' : ''; ?>"
							href="<?php echo esc_url( $ct_opzeggen_url ); ?>"
							data-ct-tab="opzeggen"
						>
							<span class="ct-account-sidebar__icon" aria-hidden="true">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
									<path d="M4 4v5h5M20 20v-5h-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									<path d="M20 9a8 8 0 00-14.9-3M4 15a8 8 0 0014.9 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
							</span>
							<span class="ct-account-sidebar__label"><?php esc_html_e( 'Abonnement opzeggen', 'coachtribe-my-account' ); ?></span>
						</a>
					</li>
					<?php endif; ?>
					<li class="ct-account-sidebar__item ct-account-sidebar__item--logout">
					<a class="ct-account-sidebar__link ct-account-sidebar__link--logout" href="<?php echo esc_url( $ct_logout_url ); ?>">
						<span class="ct-account-sidebar__icon" aria-hidden="true"><?php echo coachtribe_my_account_sidebar_nav_icon( 'logout' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span class="ct-account-sidebar__label"><?php esc_html_e( 'Uitloggen', 'coachtribe-my-account' ); ?></span>
					</a>
				</li>
			</ul>
			</nav>
		</div>

		<div class="ct-account-sidebar__hulp-slot">
			<?php require $ct_sections_dir . 'hulp-nodig.php'; ?>
		</div>
	</aside>

	<div class="ct-account-main">
		<div class="ct-account-main__notices">
			<?php wc_print_notices(); ?>
		</div>

		<header class="ct-account-header<?php echo $ct_is_overzicht ? ' ct-account-header--overzicht' : ''; ?><?php echo $ct_is_instellingen ? ' ct-account-header--instellingen' : ''; ?>">
			<div class="ct-account-header__titles">
				<?php if ( $ct_is_overzicht ) : ?>
					<h1 class="ct-account-header__title"><?php esc_html_e( 'Mijn account', 'coachtribe-my-account' ); ?></h1>
					<p class="ct-account-header__subtitle"><?php esc_html_e( 'Hier beheer je je account en bekijk je je abonnementsgegevens.', 'coachtribe-my-account' ); ?></p>
				<?php elseif ( $ct_is_instellingen ) : ?>
					<h1 class="ct-account-header__title"><?php esc_html_e( 'Instellingen', 'coachtribe-my-account' ); ?></h1>
					<p class="ct-account-header__subtitle"><?php esc_html_e( 'Beheer je abonnement, facturen en accountinstellingen.', 'coachtribe-my-account' ); ?></p>
				<?php elseif ( $ct_is_facturen ) : ?>
					<h1 class="ct-account-header__title"><?php esc_html_e( 'Facturen', 'coachtribe-my-account' ); ?></h1>
					<p class="ct-account-header__subtitle"><?php esc_html_e( 'Bekijk en download je facturen.', 'coachtribe-my-account' ); ?></p>
				<?php elseif ( $ct_is_factuurgegevens ) : ?>
					<h1 class="ct-account-header__title"><?php esc_html_e( 'Factuurgegevens', 'coachtribe-my-account' ); ?></h1>
					<p class="ct-account-header__subtitle"><?php esc_html_e( 'Beheer je factuur- en betaalgegevens.', 'coachtribe-my-account' ); ?></p>
				<?php elseif ( $ct_is_wachtwoord ) : ?>
					<h1 class="ct-account-header__title"><?php esc_html_e( 'Wachtwoord veranderen', 'coachtribe-my-account' ); ?></h1>
					<p class="ct-account-header__subtitle"><?php esc_html_e( 'Wijzig je wachtwoord of stel een nieuw wachtwoord in.', 'coachtribe-my-account' ); ?></p>
				<?php elseif ( $ct_is_opzeggen ) : ?>
					<h1 class="ct-account-header__title"><?php esc_html_e( 'Abonnement opzeggen', 'coachtribe-my-account' ); ?></h1>
					<p class="ct-account-header__subtitle"><?php esc_html_e( 'Hier kun je je abonnement beëindigen. Je behoudt toegang tot het einde van je huidige betaalperiode.', 'coachtribe-my-account' ); ?></p>
				<?php elseif ( $ct_is_betaalmethode ) : ?>
					<h1 class="ct-account-header__title"><?php esc_html_e( 'Betaalmethode wijzigen', 'coachtribe-my-account' ); ?></h1>
					<p class="ct-account-header__subtitle"><?php esc_html_e( 'Werk je betaalgegevens bij.', 'coachtribe-my-account' ); ?></p>
				<?php else : ?>
					<h1 class="ct-account-header__title"><?php esc_html_e( 'Account', 'coachtribe-my-account' ); ?></h1>
					<p class="ct-account-header__subtitle"><?php esc_html_e( 'Beheer je accountgegevens.', 'coachtribe-my-account' ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( is_user_logged_in() ) : ?>
			<div class="ct-account-header__meta">
				<button type="button" class="ct-account-header__bell" aria-label="<?php esc_attr_e( 'Meldingen', 'coachtribe-my-account' ); ?>">
					<span class="ct-account-header__bell-icon dashicons dashicons-bell" aria-hidden="true"></span>
				</button>
				<p class="ct-account-header__greeting">
					<?php
					printf(
						/* translators: %s: voornaam of gebruikersnaam */
						esc_html__( 'Hallo %s', 'coachtribe-my-account' ),
						esc_html( $ct_name )
					);
					?>
				</p>
				<span
					class="ct-account-header__avatar"
					data-ct-header-avatar
					<?php if ( '' !== $ct_header_avatar_url ) : ?>
						role="img"
						aria-label="<?php echo esc_attr( $ct_header_avatar_alt ); ?>"
					<?php else : ?>
						aria-hidden="true"
					<?php endif; ?>
				>
					<?php if ( '' !== $ct_header_avatar_url ) : ?>
						<img
							class="ct-account-header__avatar-img"
							src="<?php echo esc_url( $ct_header_avatar_url ); ?>"
							alt="<?php echo esc_attr( $ct_header_avatar_alt ); ?>"
							width="40"
							height="40"
							decoding="async"
							data-ct-header-avatar-img
						/>
					<?php else : ?>
						<span class="ct-account-header__avatar-initial" aria-hidden="true"><?php echo esc_html( $ct_initial ); ?></span>
					<?php endif; ?>
				</span>
			</div>
			<?php endif; ?>
		</header>

		<div class="ct-account-body">
			<div class="ct-account-body__tab-area">
				<div class="ct-account-tab-panels" data-ct-account-tab-panels>
					<div
						class="ct-account-tab-content is-active"
						data-ct-tab="<?php echo esc_attr( $ct_tab_slug ); ?>"
						data-ct-loaded="1"
						id="<?php echo esc_attr( $ct_tab_panel_id ); ?>"
						role="tabpanel"
					>
						<?php
						switch ( $ct_endpoint ) {
							case '':
							case 'dashboard':
								include $ct_sections_dir . 'overzicht.php';
								break;
							case 'facturen':
								do_action( 'woocommerce_account_facturen_endpoint' );
								break;
							case 'instellingen':
								do_action( 'woocommerce_account_instellingen_endpoint' );
								break;
							case 'factuurgegevens':
								do_action( 'woocommerce_account_factuurgegevens_endpoint' );
								break;
							case 'opzeggen':
								do_action( 'woocommerce_account_opzeggen_endpoint' );
								break;
							case 'wachtwoord':
								do_action( 'woocommerce_account_wachtwoord_endpoint' );
								break;
							case 'view-subscription':
								do_action(
									'woocommerce_account_view-subscription_endpoint',
									coachtribe_my_account_get_view_subscription_id()
								);
								break;
							default:
								/**
								 * Standaard WooCommerce-endpoints (bestellingen, adressen, betaalmethoden, enz.).
								 *
								 * @hooked WC — `woocommerce_account_content` (o.a. `wc_output_account_content`).
								 */
								do_action( 'woocommerce_account_content' );
								break;
						}
						?>
					</div>
				</div>
				<?php require $ct_sections_dir . 'support.php'; ?>
			</div>
		</div>
	</div>
</div>
