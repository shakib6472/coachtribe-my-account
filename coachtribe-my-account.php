<?php
/**
 * Plugin Name:       CoachTribe My Account
 * Plugin URI:        https://coachtribe.nl/
 * Description:       Custom WooCommerce My Account page for CoachTribe — custom endpoints, templates, SPA-tabs, CoachTribe styling.
 * Version:           1.0.18
 * Author:            CoachTribe
 * Author URI:        https://coachtribe.nl/
 * Text Domain:        coachtribe-my-account
 * Domain Path:        /languages
 * Requires at least:  6.0
 * Requires PHP:       7.4
 * WC requires at least: 7.0
 * WC tested up to:     9.0
 * License:            GPL-2.0-or-later
 * License URI:        https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package CoachTribe_My_Account
 */

defined( 'ABSPATH' ) || exit;

define( 'COACHTRIBE_MY_ACCOUNT_VERSION', '1.0.54' );
define( 'COACHTRIBE_MY_ACCOUNT_PATH', plugin_dir_path( __FILE__ ) );
define( 'COACHTRIBE_MY_ACCOUNT_URL', plugin_dir_url( __FILE__ ) );

/*
|--------------------------------------------------------------------------
| WooCommerce integratie (overzicht)
|--------------------------------------------------------------------------
| - Rewrite endpoints (init): facturen, wachtwoord, instellingen — gekoppeld aan WC query vars.
| - Menu: woocommerce_account_menu_items (Nederlandse labels + custom items).
| - Layout override: woocommerce_locate_template → templates/account-main.php.
| - Custom tab-inhoud: woocommerce_account_{endpoint}_endpoint (callbacks registreren op woocommerce_init).
| - Overzicht: include overzicht.php; daarin do_action( 'woocommerce_account_dashboard' ) voor extensies.
| - Overige WC-endpoints: do_action( 'woocommerce_account_content' ) (bestellingen, adressen, …).
| - Uitschakelen override: add_filter( 'coachtribe_my_account_use_plugin_templates', '__return_false' ).
|--------------------------------------------------------------------------
*/

/**
 * Bootstrap when WooCommerce is available.
 */
function coachtribe_my_account_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	coachtribe_my_account_register_hooks();
}
add_action( 'plugins_loaded', 'coachtribe_my_account_init', 11 );

/**
 * Admin notice when WooCommerce is missing.
 */
function coachtribe_my_account_wc_missing_notice() {
	if ( class_exists( 'WooCommerce' ) ) {
		return;
	}
	echo '<div class="notice notice-error"><p>';
	echo esc_html__( 'CoachTribe My Account vereist WooCommerce om te werken.', 'coachtribe-my-account' );
	echo '</p></div>';
}
add_action( 'admin_notices', 'coachtribe_my_account_wc_missing_notice' );

/**
 * Register My Account rewrite endpoints (must run on init).
 */
function coachtribe_my_account_register_endpoints() {
	add_rewrite_endpoint( 'facturen', EP_ROOT | EP_PAGES );
	add_rewrite_endpoint( 'wachtwoord', EP_ROOT | EP_PAGES );
	add_rewrite_endpoint( 'instellingen', EP_ROOT | EP_PAGES );
}
add_action( 'init', 'coachtribe_my_account_register_endpoints', 5 );

/**
 * Register custom endpoints with WooCommerce query vars.
 *
 * @param array $query_vars Existing query vars.
 * @return array
 */
function coachtribe_my_account_query_vars( $query_vars ) {
	$query_vars['facturen']     = 'facturen';
	$query_vars['wachtwoord']   = 'wachtwoord';
	$query_vars['instellingen'] = 'instellingen';

	return $query_vars;
}

/**
 * Flush rewrite rules on activation.
 */
function coachtribe_my_account_activate() {
	coachtribe_my_account_register_endpoints();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'coachtribe_my_account_activate' );

/**
 * Flush rewrite rules on deactivation (cleanup custom endpoints from rules).
 */
function coachtribe_my_account_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'coachtribe_my_account_deactivate' );

/**
 * Pro product ID from wp_options when filter default is 0.
 *
 * @param int             $id   Product/variation ID from earlier filters.
 * @param WC_Subscription $sub  Subscription.
 * @param string          $plan Plan display name.
 * @return int
 */
function coachtribe_my_account_upgrade_to_pro_product_id_from_option( $id, $sub, $plan ) {
	$saved = (int) get_option( 'coachtribe_pro_product_id', 0 );
	return $saved > 0 ? $saved : (int) $id;
}
add_filter( 'coachtribe_my_account_upgrade_to_pro_product_id', 'coachtribe_my_account_upgrade_to_pro_product_id_from_option', 10, 3 );

/**
 * Invoice download URL via WooCommerce PDF Invoices & Packing Slips when available.
 *
 * @param string   $url   Fallback URL.
 * @param WC_Order $order Order.
 * @return string
 */
function coachtribe_my_account_invoice_download_url( $url, $order ) {
	if ( ! $order instanceof WC_Order ) {
		return $url;
	}
	if ( function_exists( 'wcpdf_get_document' ) ) {
		$invoice = wcpdf_get_document( 'invoice', $order, false );
		if ( $invoice && is_callable( array( $invoice, 'get_pdf_url' ) ) ) {
			$pdf_url = $invoice->get_pdf_url();
			if ( is_string( $pdf_url ) && '' !== $pdf_url ) {
				return $pdf_url;
			}
		}
	}
	return $url;
}
add_filter( 'coachtribe_my_account_invoice_download_url', 'coachtribe_my_account_invoice_download_url', 10, 2 );

/**
 * Settings → CoachTribe Account (Pro product ID).
 */
function coachtribe_my_account_admin_menu() {
	add_options_page(
		__( 'CoachTribe Account', 'coachtribe-my-account' ),
		__( 'CoachTribe Account', 'coachtribe-my-account' ),
		'manage_options',
		'coachtribe-my-account',
		'coachtribe_my_account_render_settings_page'
	);
}
add_action( 'admin_menu', 'coachtribe_my_account_admin_menu' );

/**
 * Register CoachTribe Account settings.
 */
function coachtribe_my_account_register_settings() {
	register_setting(
		'coachtribe_my_account_settings',
		'coachtribe_pro_product_id',
		array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 0,
		)
	);

	add_settings_section(
		'coachtribe_my_account_main',
		__( 'Abonnementen', 'coachtribe-my-account' ),
		'__return_false',
		'coachtribe-my-account'
	);

	add_settings_field(
		'coachtribe_pro_product_id',
		__( 'Pro product ID', 'coachtribe-my-account' ),
		'coachtribe_my_account_render_pro_product_id_field',
		'coachtribe-my-account',
		'coachtribe_my_account_main'
	);
}
add_action( 'admin_init', 'coachtribe_my_account_register_settings' );

/**
 * @return void
 */
function coachtribe_my_account_render_pro_product_id_field() {
	$value = (int) get_option( 'coachtribe_pro_product_id', 0 );
	printf(
		'<input type="number" name="coachtribe_pro_product_id" id="coachtribe_pro_product_id" value="%1$d" min="0" step="1" class="regular-text" />',
		$value
	);
	echo '<p class="description">';
	esc_html_e( 'WooCommerce product- of variatie-ID voor “Upgrade naar Pro” (subscription switch checkout).', 'coachtribe-my-account' );
	echo '</p>';
}

/**
 * @return void
 */
function coachtribe_my_account_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'coachtribe_my_account_settings' );
			do_settings_sections( 'coachtribe-my-account' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * Register filters and actions (content, menus, assets, templates).
 */
function coachtribe_my_account_register_hooks() {
	add_filter( 'woocommerce_get_query_vars', 'coachtribe_my_account_query_vars' );
	add_filter( 'woocommerce_account_menu_items', 'coachtribe_my_account_menu_items', 20, 1 );
	add_filter(
		'woocommerce_locate_template',
		'coachtribe_my_account_locate_template',
		(int) apply_filters( 'coachtribe_my_account_locate_template_priority', 99 ),
		3
	);

	add_action( 'woocommerce_init', 'coachtribe_my_account_register_wc_endpoint_integration', 20 );

	add_filter( 'body_class', 'coachtribe_my_account_body_class', 20 );
	add_filter( 'get_custom_logo', 'coachtribe_my_account_filter_get_custom_logo', 20, 2 );
	add_filter( 'elementor/widget/render_content', 'coachtribe_my_account_elementor_site_logo_content', 20, 2 );
	add_action( 'wp_enqueue_scripts', 'coachtribe_my_account_enqueue_assets', 20 );
	add_action( 'template_redirect', 'coachtribe_my_account_handle_password_change', 5 );
	add_action( 'template_redirect', 'coachtribe_my_account_handle_settings_save', 6 );
	add_action( 'template_redirect', 'coachtribe_my_account_handle_profile_edit_save', 7 );
	add_action( 'template_redirect', 'coachtribe_my_account_upgrade_notices_on_account_load', 4 );
	add_action( 'woocommerce_thankyou', 'coachtribe_my_account_upgrade_notice_flag_thankyou', 25, 1 );
	add_action( 'wp_ajax_coachtribe_my_account_tab', 'coachtribe_my_account_ajax_tab' );
	add_action( 'wp_ajax_coachtribe_my_account_profile_image', 'coachtribe_my_account_ajax_profile_image' );
	add_action( 'wp_ajax_coachtribe_my_account_cancel_subscription', 'coachtribe_my_account_ajax_cancel_subscription' );
	add_shortcode( 'coachtribe_my_account', 'coachtribe_my_account_shortcode' );
}

/**
 * Registreer WooCommerce `woocommerce_account_{endpoint}_endpoint` callbacks (één bron voor template-output).
 * Zo kunnen extensies met priorities vóór/na de standaardinhoud haken, vergelijkbaar met core WC.
 */
function coachtribe_my_account_register_wc_endpoint_integration() {
	static $registered = false;
	if ( $registered ) {
		return;
	}
	$registered = true;

	$priority = (int) apply_filters( 'coachtribe_my_account_endpoint_template_priority', 10 );

	add_action( 'woocommerce_account_facturen_endpoint', 'coachtribe_my_account_output_facturen_endpoint', $priority );
	add_action( 'woocommerce_account_instellingen_endpoint', 'coachtribe_my_account_output_instellingen_endpoint', $priority );
	add_action( 'woocommerce_account_wachtwoord_endpoint', 'coachtribe_my_account_output_wachtwoord_endpoint', $priority );

	// WCS registers `view-subscription`; we replace its template, not the rewrite endpoint.
	add_action( 'woocommerce_account_view-subscription_endpoint', 'coachtribe_my_account_output_view_subscription_endpoint', $priority );
}

/**
 * Remove default WooCommerce Subscriptions view-subscription template (avoid duplicate output).
 */
function coachtribe_my_account_detach_wcs_view_subscription_template() {
	if ( ! function_exists( 'wcs_get_subscription' ) ) {
		return;
	}

	$wcs_callbacks = array(
		'wcs_account_content_view_subscription',
	);

	foreach ( $wcs_callbacks as $callback ) {
		if ( has_action( 'woocommerce_account_view-subscription_endpoint', $callback ) ) {
			remove_action( 'woocommerce_account_view-subscription_endpoint', $callback, 10 );
		}
	}

	if ( class_exists( 'WC_Subscriptions_Query' ) ) {
		$handler = array( 'WC_Subscriptions_Query', 'view_subscription' );
		if ( has_action( 'woocommerce_account_view-subscription_endpoint', $handler ) ) {
			remove_action( 'woocommerce_account_view-subscription_endpoint', $handler, 10 );
		}
	}
}
add_action( 'woocommerce_init', 'coachtribe_my_account_detach_wcs_view_subscription_template', 99 );

/**
 * Subscription ID from the view-subscription account endpoint query var.
 *
 * @return int
 */
function coachtribe_my_account_get_view_subscription_id() {
	if ( ! empty( $GLOBALS['coachtribe_my_account_ajax_subscription_id'] ) ) {
		return absint( $GLOBALS['coachtribe_my_account_ajax_subscription_id'] );
	}

	global $wp;

	if ( isset( $wp->query_vars['view-subscription'] ) ) {
		return absint( $wp->query_vars['view-subscription'] );
	}

	return absint( get_query_var( 'view-subscription' ) );
}

/**
 * Load subscription for the view-subscription screen; only if owned by current user.
 *
 * @return WC_Subscription|null
 */
function coachtribe_my_account_get_view_subscription_for_current_user() {
	if ( ! is_user_logged_in() || ! function_exists( 'wcs_get_subscription' ) ) {
		return null;
	}

	$subscription_id = coachtribe_my_account_get_view_subscription_id();
	if ( $subscription_id < 1 ) {
		return null;
	}

	$subscription = wcs_get_subscription( $subscription_id );
	if ( ! $subscription instanceof WC_Subscription ) {
		return null;
	}

	if ( (int) $subscription->get_user_id() !== get_current_user_id() ) {
		return null;
	}

	return $subscription;
}

/**
 * Status badge class + label for a subscription.
 *
 * @param WC_Subscription $subscription Subscription.
 * @return array{class: string, label: string}
 */
function coachtribe_my_account_subscription_status_display( $subscription ) {
	if ( ! $subscription instanceof WC_Subscription ) {
		return array(
			'class' => 'ct-account-sub__badge ct-account-sub__badge--cancelled',
			'label' => __( 'Onbekend', 'coachtribe-my-account' ),
		);
	}

	if ( $subscription->has_status( 'active' ) ) {
		return array(
			'class' => 'ct-account-sub__badge ct-account-sub__badge--active',
			'label' => __( 'Actief', 'coachtribe-my-account' ),
		);
	}

	if (
		$subscription->has_status( 'on-hold' )
		|| $subscription->has_status( 'pending' )
		|| $subscription->has_status( 'pending-cancel' )
	) {
		return array(
			'class' => 'ct-account-sub__badge ct-account-sub__badge--paused',
			'label' => __( 'Gepauzeerd', 'coachtribe-my-account' ),
		);
	}

	return array(
		'class' => 'ct-account-sub__badge ct-account-sub__badge--cancelled',
		'label' => __( 'Geannuleerd', 'coachtribe-my-account' ),
	);
}

/**
 * Published subscription products (simple + variations) for plan switching.
 *
 * @return WC_Product[]
 */
function coachtribe_my_account_get_switchable_subscription_plans() {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}

	$products = wc_get_products(
		array(
			'type'    => array( 'subscription', 'variable-subscription' ),
			'status'  => 'publish',
			'limit'   => -1,
			'orderby' => 'menu_order',
			'order'   => 'ASC',
		)
	);

	$plans = array();

	foreach ( $products as $product ) {
		if ( ! $product instanceof WC_Product ) {
			continue;
		}

		if ( $product->is_type( 'variable-subscription' ) ) {
			foreach ( $product->get_children() as $child_id ) {
				$variation = wc_get_product( $child_id );
				if ( $variation instanceof WC_Product && $variation->is_purchasable() ) {
					$plans[] = $variation;
				}
			}
			continue;
		}

		if ( $product->is_purchasable() ) {
			$plans[] = $product;
		}
	}

	return (array) apply_filters( 'coachtribe_my_account_switchable_plans', $plans );
}

/**
 * Whether a subscription's line item matches a product or variation ID.
 *
 * @param WC_Subscription $subscription  Subscription.
 * @param int             $product_id Product or variation ID.
 * @return bool
 */
function coachtribe_my_account_subscription_has_product( $subscription, $product_id ) {
	if ( ! $subscription instanceof WC_Subscription ) {
		return false;
	}

	$product_id = absint( $product_id );
	if ( $product_id < 1 ) {
		return false;
	}

	foreach ( $subscription->get_items() as $item ) {
		if ( ! $item->is_type( 'line_item' ) ) {
			continue;
		}
		if ( (int) $item->get_variation_id() === $product_id ) {
			return true;
		}
		if ( (int) $item->get_product_id() === $product_id ) {
			return true;
		}
	}

	return false;
}

/**
 * Cancel URL for a subscription (WCS endpoint or actions).
 *
 * @param WC_Subscription $subscription Subscription.
 * @return string
 */
function coachtribe_my_account_get_subscription_cancel_url( $subscription ) {
	if ( ! $subscription instanceof WC_Subscription ) {
		return '';
	}

	if ( is_callable( array( $subscription, 'get_cancel_endpoint' ) ) ) {
		$url = $subscription->get_cancel_endpoint();
		if ( is_string( $url ) && '' !== $url ) {
			return $url;
		}
	}

	if ( function_exists( 'wcs_get_all_user_actions_for_subscription' ) ) {
		$actions = wcs_get_all_user_actions_for_subscription( $subscription, get_current_user_id() );
		foreach ( array( 'cancel', 'cancelled' ) as $action_key ) {
			if ( isset( $actions[ $action_key ]['url'] ) && is_string( $actions[ $action_key ]['url'] ) ) {
				return $actions[ $action_key ]['url'];
			}
		}
	}

	return '';
}

/**
 * @param int $subscription_id Subscription ID (endpoint value; optional).
 * @return void
 */
function coachtribe_my_account_output_view_subscription_endpoint( $subscription_id = 0 ) {
	do_action( 'coachtribe_my_account_before_view_subscription', absint( $subscription_id ) );

	$file = COACHTRIBE_MY_ACCOUNT_PATH . 'templates/sections/view-subscription.php';
	if ( is_readable( $file ) ) {
		include $file;
	}

	do_action( 'coachtribe_my_account_after_view_subscription', absint( $subscription_id ) );
}

/**
 * Return URL after customer changes subscription payment method (WCS).
 *
 * @param string $url Default return URL.
 * @return string
 */
function coachtribe_my_account_return_after_payment_method_change( $url ) {
	if ( function_exists( 'wc_get_account_endpoint_url' ) ) {
		$dashboard = wc_get_account_endpoint_url( 'dashboard' );
		if ( is_string( $dashboard ) && '' !== $dashboard ) {
			return $dashboard;
		}
	}

	return home_url( '/account-2/' );
}
add_filter( 'woocommerce_subscriptions_return_after_payment_method_change', 'coachtribe_my_account_return_after_payment_method_change' );

/**
 * @return void
 */
function coachtribe_my_account_output_facturen_endpoint() {
	/**
	 * Vóór de Facturen-tab (CoachTribe).
	 */
	do_action( 'coachtribe_my_account_before_facturen' );

	$file = COACHTRIBE_MY_ACCOUNT_PATH . 'templates/sections/tabs/facturen.php';
	if ( is_readable( $file ) ) {
		include $file;
	}

	/**
	 * Na de Facturen-tab (CoachTribe).
	 */
	do_action( 'coachtribe_my_account_after_facturen' );
}

/**
 * @return void
 */
function coachtribe_my_account_output_instellingen_endpoint() {
	do_action( 'coachtribe_my_account_before_instellingen' );

	$file = COACHTRIBE_MY_ACCOUNT_PATH . 'templates/sections/tabs/instellingen.php';
	if ( is_readable( $file ) ) {
		include $file;
	}

	do_action( 'coachtribe_my_account_after_instellingen' );
}

/**
 * @return void
 */
function coachtribe_my_account_output_wachtwoord_endpoint() {
	do_action( 'coachtribe_my_account_before_wachtwoord' );

	$file = COACHTRIBE_MY_ACCOUNT_PATH . 'templates/sections/tabs/wachtwoord.php';
	if ( is_readable( $file ) ) {
		include $file;
	}

	do_action( 'coachtribe_my_account_after_wachtwoord' );
}

/**
 * User meta key voor aangepaste profielfoto-URL.
 *
 * @return string
 */
function coachtribe_my_account_profile_image_meta_key() {
	return (string) apply_filters( 'coachtribe_my_account_profile_image_meta_key', 'coachtribe_profile_image_url' );
}

/**
 * Opgeslagen profielfoto-URL voor een gebruiker (leeg = standaard avatar).
 *
 * @param int $user_id Gebruikers-ID.
 * @return string Escaped URL of leeg.
 */
function coachtribe_my_account_get_profile_image_url( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return '';
	}
	$url = get_user_meta( $user_id, coachtribe_my_account_profile_image_meta_key(), true );
	if ( ! is_string( $url ) || '' === trim( $url ) ) {
		return '';
	}
	return esc_url( $url );
}

/**
 * Profielfoto-URL voor header-avatar: custom upload, anders WP/Gravatar indien beschikbaar.
 *
 * @param int $user_id Gebruikers-ID.
 * @return string Escaped URL of leeg (toon initial).
 */
function coachtribe_my_account_get_header_avatar_url( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return '';
	}

	$url = coachtribe_my_account_get_profile_image_url( $user_id );
	if ( '' !== $url ) {
		return $url;
	}

	if ( function_exists( 'get_avatar_data' ) ) {
		$avatar_data = get_avatar_data(
			$user_id,
			array(
				'size' => 96,
			)
		);
		if ( ! empty( $avatar_data['url'] ) && ! empty( $avatar_data['found_avatar'] ) ) {
			return esc_url( $avatar_data['url'] );
		}
	}

	return '';
}

/**
 * AJAX: veilige profielfoto-upload met wp_handle_upload().
 */
function coachtribe_my_account_ajax_profile_image() {
	if ( ! check_ajax_referer( 'coachtribe_profile_image_upload', 'nonce', false ) ) {
		wp_send_json_error(
			array( 'message' => __( 'Ongeldige sessie. Vernieuw de pagina en probeer opnieuw.', 'coachtribe-my-account' ) ),
			403
		);
	}

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Je moet ingelogd zijn.', 'coachtribe-my-account' ) ), 403 );
	}

	$user_id = get_current_user_id();
	if ( $user_id < 1 ) {
		wp_send_json_error( array( 'message' => __( 'Geen geldige gebruiker.', 'coachtribe-my-account' ) ), 403 );
	}

	if ( empty( $_FILES['profile_image'] ) || ! is_array( $_FILES['profile_image'] ) ) {
		wp_send_json_error( array( 'message' => __( 'Geen bestand ontvangen.', 'coachtribe-my-account' ) ), 400 );
	}

	$file = $_FILES['profile_image'];

	if ( ! isset( $file['error'] ) || UPLOAD_ERR_OK !== (int) $file['error'] ) {
		wp_send_json_error( array( 'message' => __( 'Upload mislukt. Probeer een ander bestand.', 'coachtribe-my-account' ) ), 400 );
	}

	$max_bytes = (int) apply_filters( 'coachtribe_my_account_profile_image_max_bytes', 2 * ( defined( 'MB_IN_BYTES' ) ? MB_IN_BYTES : 1048576 ) );
	if ( $max_bytes < 102400 ) {
		$max_bytes = 102400;
	}
	if ( isset( $file['size'] ) && (int) $file['size'] > $max_bytes ) {
		wp_send_json_error(
			array( 'message' => __( 'Bestand is te groot. Maximaal 2 MB toegestaan.', 'coachtribe-my-account' ) ),
			400
		);
	}

	$allowed_mimes = apply_filters(
		'coachtribe_my_account_profile_image_allowed_mimes',
		array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'gif'          => 'image/gif',
			'webp'         => 'image/webp',
		)
	);

	$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $allowed_mimes );
	if ( empty( $check['type'] ) || 0 !== strpos( (string) $check['type'], 'image/' ) ) {
		wp_send_json_error(
			array( 'message' => __( 'Alleen afbeeldingsbestanden zijn toegestaan (JPEG, PNG, GIF, WebP).', 'coachtribe-my-account' ) ),
			400
		);
	}

	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	$overrides = array(
		'test_form' => false,
		'mimes'     => $allowed_mimes,
	);

	$movefile = wp_handle_upload( $file, $overrides );

	if ( isset( $movefile['error'] ) && is_string( $movefile['error'] ) ) {
		wp_send_json_error(
			array( 'message' => wp_strip_all_tags( $movefile['error'] ) ),
			400
		);
	}

	if ( empty( $movefile['url'] ) || empty( $movefile['type'] ) || 0 !== strpos( (string) $movefile['type'], 'image/' ) ) {
		wp_send_json_error( array( 'message' => __( 'Ongeldig uploadresultaat.', 'coachtribe-my-account' ) ), 500 );
	}

	$url = esc_url_raw( $movefile['url'] );

	/**
	 * Vóór het opslaan van de profielfoto-URL in user meta.
	 *
	 * @param string $url     Nieuwe bestands-URL.
	 * @param int    $user_id Gebruikers-ID.
	 * @param array  $movefile Resultaat van wp_handle_upload().
	 */
	do_action( 'coachtribe_my_account_before_profile_image_save', $url, $user_id, $movefile );

	update_user_meta( $user_id, coachtribe_my_account_profile_image_meta_key(), $url );

	/**
	 * Na het opslaan van de profielfoto-URL.
	 *
	 * @param string $url     Opgeslagen URL.
	 * @param int    $user_id Gebruikers-ID.
	 */
	do_action( 'coachtribe_my_account_after_profile_image_save', $url, $user_id );

	wp_send_json_success(
		array(
			'url' => $url,
		)
	);
}

/**
 * Detect My Account endpoint from query vars or REQUEST_URI path (shortcode / custom account URLs).
 *
 * @return string facturen|instellingen|wachtwoord|'' 
 */
function coachtribe_my_account_detect_endpoint_from_url() {
	static $cached = null;

	if ( null !== $cached ) {
		return $cached;
	}

	$known = apply_filters(
		'coachtribe_my_account_detect_url_endpoints',
		array( 'facturen', 'instellingen', 'wachtwoord' )
	);

	foreach ( $known as $endpoint ) {
		$endpoint = sanitize_key( $endpoint );
		if ( '' === $endpoint ) {
			continue;
		}
		$qv = get_query_var( $endpoint, false );
		if ( false !== $qv ) {
			$cached = $endpoint;
			return $cached;
		}
	}

	$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	if ( ! is_string( $uri ) || '' === $uri ) {
		$cached = '';
		return $cached;
	}

	$path = wp_parse_url( $uri, PHP_URL_PATH );
	if ( ! is_string( $path ) || '' === $path ) {
		$cached = '';
		return $cached;
	}

	$segments = array_filter( explode( '/', trim( $path, '/' ) ) );
	$found    = '';

	foreach ( $segments as $segment ) {
		$segment = sanitize_key( $segment );
		if ( in_array( $segment, $known, true ) ) {
			$found = $segment;
		}
	}

	$cached = $found;
	return $cached;
}

/**
 * Normaliseer huidig WC-endpoint naar tab-slug voor SPA-tabbladen.
 *
 * @param string $endpoint Huidig endpoint (kan leeg zijn).
 * @return string dashboard|facturen|instellingen|wachtwoord|wc-default
 */
function coachtribe_my_account_normalize_tab_slug( $endpoint ) {
	$endpoint = (string) $endpoint;
	if ( '' === $endpoint ) {
		$endpoint = coachtribe_my_account_detect_endpoint_from_url();
	}
	if ( '' === $endpoint || 'dashboard' === $endpoint ) {
		return 'dashboard';
	}
	$ajax_tabs = array( 'facturen', 'instellingen', 'wachtwoord' );
	if ( in_array( $endpoint, $ajax_tabs, true ) ) {
		return $endpoint;
	}
	return 'wc-default';
}

/**
 * Rendert de inner HTML voor één account-tab (zelfde logica als account-main.php).
 *
 * @param string $endpoint WC-endpoint (dashboard, facturen, …).
 * @return string
 */
function coachtribe_my_account_render_tab_html( $endpoint ) {
	if ( ! defined( 'COACHTRIBE_MY_ACCOUNT_PATH' ) ) {
		return '';
	}

	$ct_sections_dir = COACHTRIBE_MY_ACCOUNT_PATH . 'templates/sections/';

	ob_start();

	switch ( $endpoint ) {
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
			 * @hooked WC — `wc_output_account_content` en gerelateerde handlers.
			 */
			do_action( 'woocommerce_account_content' );
			break;
	}

	return (string) ob_get_clean();
}

/**
 * AJAX: HTML voor een account-tab (ingelogde gebruikers alleen).
 */
function coachtribe_my_account_ajax_tab() {
	if ( ! check_ajax_referer( 'coachtribe_my_account_tabs', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => __( 'Ongeldige sessie.', 'coachtribe-my-account' ) ), 403 );
	}

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Niet ingelogd.', 'coachtribe-my-account' ) ), 403 );
	}

	if ( ! function_exists( 'WC' ) ) {
		wp_send_json_error( array( 'message' => __( 'WooCommerce niet beschikbaar.', 'coachtribe-my-account' ) ), 500 );
	}

	$tab = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : '';
	$allowed = array( 'dashboard', 'facturen', 'instellingen', 'wachtwoord', 'view-subscription' );

	if ( ! in_array( $tab, $allowed, true ) ) {
		wp_send_json_error( array( 'message' => __( 'Onbekend tabblad.', 'coachtribe-my-account' ) ), 400 );
	}

	$ajax_subscription_id = 0;
	if ( 'view-subscription' === $tab ) {
		$ajax_subscription_id = isset( $_POST['subscription_id'] ) ? absint( wp_unslash( $_POST['subscription_id'] ) ) : 0;
		if ( $ajax_subscription_id < 1 ) {
			wp_send_json_error( array( 'message' => __( 'Ongeldig abonnement.', 'coachtribe-my-account' ) ), 400 );
		}
		$GLOBALS['coachtribe_my_account_ajax_subscription_id'] = $ajax_subscription_id;
	}

	$html = coachtribe_my_account_render_tab_html( 'dashboard' === $tab ? 'dashboard' : $tab );

	if ( 'view-subscription' === $tab ) {
		unset( $GLOBALS['coachtribe_my_account_ajax_subscription_id'] );
	}

	wp_send_json_success(
		array(
			'html' => $html,
			'tab'  => $tab,
		)
	);
}

/**
 * AJAX: annuleer het actieve abonnement van de ingelogde gebruiker (WooCommerce Subscriptions).
 */
function coachtribe_my_account_ajax_cancel_subscription() {
	if ( ! check_ajax_referer( 'coachtribe_cancel_subscription', 'nonce', false ) ) {
		wp_send_json_error(
			array( 'message' => __( 'Invalid session. Please refresh the page and try again.', 'coachtribe-my-account' ) ),
			403
		);
	}

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'coachtribe-my-account' ) ), 403 );
	}

	$uid = get_current_user_id();
	$subscription_id = isset( $_POST['subscription_id'] ) ? absint( wp_unslash( $_POST['subscription_id'] ) ) : 0;

	if ( $subscription_id < 1 ) {
		wp_send_json_error( array( 'message' => __( 'Invalid subscription.', 'coachtribe-my-account' ) ), 400 );
	}

	if ( ! function_exists( 'wcs_get_subscription' ) || ! class_exists( 'WC_Subscription' ) ) {
		wp_send_json_error(
			array( 'message' => __( 'WooCommerce Subscriptions is not available.', 'coachtribe-my-account' ) ),
			400
		);
	}

	$subscription = wcs_get_subscription( $subscription_id );

	if ( ! $subscription instanceof WC_Subscription ) {
		wp_send_json_error( array( 'message' => __( 'Subscription not found.', 'coachtribe-my-account' ) ), 404 );
	}

	if ( (int) $subscription->get_user_id() !== $uid ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission to cancel this subscription.', 'coachtribe-my-account' ) ), 403 );
	}

	if ( ! $subscription->has_status( 'active' ) ) {
		wp_send_json_error( array( 'message' => __( 'Only active subscriptions can be cancelled here.', 'coachtribe-my-account' ) ), 400 );
	}

	/**
	 * Doelstatus na klantopzegging via AJAX: meestal `pending-cancel` (einde periode) of `cancelled`.
	 *
	 * @param string            $status       Standaard `pending-cancel`.
	 * @param WC_Subscription  $subscription Abonnement.
	 */
	$target_status = (string) apply_filters( 'coachtribe_my_account_subscription_cancel_ajax_status', 'pending-cancel', $subscription );
	$allowed       = array( 'cancelled', 'pending-cancel' );
	if ( ! in_array( $target_status, $allowed, true ) ) {
		$target_status = 'pending-cancel';
	}

	if ( ! $subscription->can_be_updated_to( $target_status ) ) {
		if ( $subscription->can_be_updated_to( 'cancelled' ) ) {
			$target_status = 'cancelled';
		} else {
			wp_send_json_error(
				array( 'message' => __( 'This subscription cannot be cancelled online. Please contact support.', 'coachtribe-my-account' ) ),
				400
			);
		}
	}

	$note = __( 'Cancelled by customer (account dashboard).', 'coachtribe-my-account' );

	try {
		$subscription->update_status( $target_status, $note );
	} catch ( Exception $e ) {
		wp_send_json_error(
			array( 'message' => wp_strip_all_tags( $e->getMessage() ) ),
			500
		);
	}

	/**
	 * Na succesvolle AJAX-opzegging.
	 *
	 * @param WC_Subscription $subscription Abonnement (nieuwe status).
	 */
	do_action( 'coachtribe_my_account_after_subscription_cancel_ajax', $subscription );

	$email_sent = true;
	if ( apply_filters( 'coachtribe_my_account_should_send_subscription_cancelled_email', true, $subscription ) ) {
		$email_sent = coachtribe_my_account_send_subscription_cancelled_user_email( $subscription );
	}

	$response = array(
		'message' => __( 'Your subscription has been successfully cancelled.', 'coachtribe-my-account' ),
		'status'  => $subscription->get_status(),
	);
	if ( ! $email_sent ) {
		$response['email_warning'] = sprintf(
			/* translators: %s: support email address */
			__( 'Your cancellation was saved, but we could not send the confirmation email. Please contact support at %s if you need a copy.', 'coachtribe-my-account' ),
			coachtribe_my_account_support_email()
		);
	}

	wp_send_json_success( $response );
}

/**
 * Bepaalt of het huidige abonnement als Pro / hoogste tier wordt beschouwd.
 *
 * @param WC_Subscription|null $subscription Abonnement of null.
 * @param string               $plan_name    Weergavenaam van het lijnitem.
 * @return bool
 */
function coachtribe_my_account_subscription_is_pro_plan( $subscription, $plan_name ) {
	$plan_normalized = strtolower( html_entity_decode( wp_strip_all_tags( (string) $plan_name ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	if ( preg_match( '/\b(pro|professional|premium\s*plus)\b/u', $plan_normalized ) ) {
		return true;
	}

	if ( ! $subscription instanceof WC_Subscription ) {
		return (bool) apply_filters( 'coachtribe_my_account_subscription_is_pro_plan', false, $subscription, $plan_name );
	}

	$pro_product_ids = array_map( 'absint', (array) apply_filters( 'coachtribe_my_account_pro_subscription_product_ids', array() ) );
	foreach ( $subscription->get_items() as $item ) {
		if ( ! $item->is_type( 'line_item' ) ) {
			continue;
		}
		$pid = (int) $item->get_product_id();
		$vid = (int) $item->get_variation_id();
		if ( $vid && in_array( $vid, $pro_product_ids, true ) ) {
			return true;
		}
		if ( $pid && in_array( $pid, $pro_product_ids, true ) ) {
			return true;
		}
	}

	return (bool) apply_filters( 'coachtribe_my_account_subscription_is_pro_plan', false, $subscription, $plan_name );
}

/**
 * Checkout-URL om te switchen naar een ander abonnementsproduct (WooCommerce Subscriptions).
 *
 * @param WC_Subscription $subscription  Huidig abonnement.
 * @param int             $to_product_id Product- of variatie-ID van het Pro-abonnement.
 * @return string Lege string als bouwen niet lukt.
 */
function coachtribe_my_account_build_switch_to_product_checkout_url( $subscription, $to_product_id ) {
	if ( ! $subscription instanceof WC_Subscription || ! function_exists( 'wc_get_checkout_url' ) ) {
		return '';
	}

	$to_product_id = absint( $to_product_id );
	if ( $to_product_id < 1 ) {
		return '';
	}

	$item_key = '';
	foreach ( $subscription->get_items() as $key => $item ) {
		if ( $item->is_type( 'line_item' ) ) {
			$item_key = (string) $key;
			break;
		}
	}

	if ( '' === $item_key ) {
		return '';
	}

	$base = add_query_arg(
		array(
			'switch-subscription' => $subscription->get_id(),
			'item'                => $item_key,
		),
		wc_get_checkout_url()
	);

	return add_query_arg( 'add-to-cart', $to_product_id, $base );
}

/**
 * Zet sessievlag na succesvolle betaling bij een subscription switch-order (thank you).
 *
 * @param int $order_id Bestelling-ID.
 */
function coachtribe_my_account_upgrade_notice_flag_thankyou( $order_id ) {
	$order_id = absint( $order_id );
	if ( $order_id < 1 || ! function_exists( 'wc_get_order' ) ) {
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}

	if ( ! function_exists( 'wcs_order_contains_subscription' ) ) {
		return;
	}

	$is_switch = wcs_order_contains_subscription( $order, 'switch' );

	if ( ! $is_switch ) {
		return;
	}

	$email_ok = coachtribe_my_account_send_subscription_upgraded_user_email( $order );

	if ( function_exists( 'WC' ) && WC()->session && is_user_logged_in() ) {
		WC()->session->set( 'coachtribe_sub_upgrade_success', '1' );
		if ( ! $email_ok ) {
			WC()->session->set( 'coachtribe_sub_upgrade_email_failed', '1' );
		}
	}
}

/**
 * Toont upgrade-succes- of afbreuk-melding op Mijn account (sessie of querystring).
 */
function coachtribe_my_account_upgrade_notices_on_account_load() {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || ! is_user_logged_in() ) {
		return;
	}
	if ( ! function_exists( 'wc_add_notice' ) || ! function_exists( 'wc_get_account_endpoint_url' ) ) {
		return;
	}

	if ( ! empty( $_GET['ct_subscription_upgrade'] ) && 'cancelled' === sanitize_key( wp_unslash( $_GET['ct_subscription_upgrade'] ) ) ) {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'ct_subscription_upgrade_cancelled' ) ) {
			return;
		}
		wc_add_notice( __( 'Your subscription upgrade was not completed.', 'coachtribe-my-account' ), 'error' );
		wp_safe_redirect( wc_get_account_endpoint_url( 'dashboard' ) );
		exit;
	}

	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}

	$flag = WC()->session->get( 'coachtribe_sub_upgrade_success' );
	if ( '1' === $flag ) {
		WC()->session->__unset( 'coachtribe_sub_upgrade_success' );
		wc_add_notice( __( 'Your subscription has been upgraded successfully.', 'coachtribe-my-account' ), 'success' );
		if ( '1' === WC()->session->get( 'coachtribe_sub_upgrade_email_failed' ) ) {
			WC()->session->__unset( 'coachtribe_sub_upgrade_email_failed' );
			wc_add_notice(
				__( 'There was an issue sending the confirmation email. Your upgrade is still active. If you need a copy, please contact support.', 'coachtribe-my-account' ),
				'error'
			);
		}
		wp_safe_redirect( wc_get_account_endpoint_url( 'dashboard' ) );
		exit;
	}
}

/**
 * Standaard support-e-mailadres voor klantmails.
 *
 * @return string
 */
function coachtribe_my_account_support_email() {
	return (string) apply_filters( 'coachtribe_my_account_support_email', 'info@coachtribe.nl' );
}

/**
 * Eerste abonnementsregel — weergavenaam (plan).
 *
 * @param WC_Subscription $subscription Abonnement.
 * @return string
 */
function coachtribe_my_account_get_subscription_plan_display_name( $subscription ) {
	if ( ! $subscription instanceof WC_Subscription ) {
		return '';
	}
	foreach ( $subscription->get_items() as $item ) {
		if ( $item->is_type( 'line_item' ) ) {
			return wp_strip_all_tags( $item->get_name() );
		}
	}
	return '';
}

/**
 * Vervangt [Placeholders] in een e-mailtekst.
 *
 * @param string $text         Sjabloon.
 * @param array  $placeholders Sleutel (zonder haken) => waarde.
 * @return string
 */
function coachtribe_my_account_replace_subscription_email_placeholders( $text, $placeholders ) {
	$out = (string) $text;
	foreach ( $placeholders as $key => $value ) {
		$token = '[' . $key . ']';
		$out     = str_replace( $token, $value, $out );
	}
	return $out;
}

/**
 * Eenvoudige HTML-wrap voor wp_mail.
 *
 * @param string $inner_html Al ge-escape inhoud (paragrafen).
 * @return string
 */
function coachtribe_my_account_subscription_email_html_wrapper( $inner_html ) {
	return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head><body style="margin:0;padding:24px;background-color:#f4f4f4;">'
		. '<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e0e0e0;border-radius:8px;">'
		. '<tr><td style="padding:28px 28px 24px;font-family:Segoe UI,Roboto,Arial,sans-serif;font-size:16px;line-height:1.55;color:#222222;">'
		. $inner_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- caller supplies escaped fragments
		. '</td></tr></table></body></html>';
}

/**
 * Verstuurt bevestigingsmail na opzegging (AJAX).
 *
 * @param WC_Subscription $subscription Abonnement na statusupdate.
 * @return bool True als wp_mail meldt succes.
 */
function coachtribe_my_account_send_subscription_cancelled_user_email( $subscription ) {
	if ( ! $subscription instanceof WC_Subscription || ! function_exists( 'wp_mail' ) ) {
		return false;
	}

	if ( ! apply_filters( 'coachtribe_my_account_should_send_subscription_cancelled_email', true, $subscription ) ) {
		return true;
	}

	$user_id = (int) $subscription->get_user_id();
	$user    = $user_id ? get_userdata( $user_id ) : false;
	$to      = $user && is_email( $user->user_email ) ? $user->user_email : '';
	if ( '' === $to ) {
		$to = sanitize_email( $subscription->get_billing_email() );
	}
	if ( '' === $to || ! is_email( $to ) ) {
		return false;
	}

	$plan_name      = coachtribe_my_account_get_subscription_plan_display_name( $subscription );
	$support_email  = coachtribe_my_account_support_email();
	$site_name      = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$user_name      = $user ? $user->display_name : $subscription->get_billing_first_name();

	$placeholders = array(
		'Plan Name'    => $plan_name,
		'User Name'    => $user_name,
		'Support Email' => $support_email,
		'Site Name'    => $site_name,
	);

	$subject_template = (string) apply_filters(
		'coachtribe_my_account_subscription_cancelled_email_subject',
		__( 'Your Subscription has been Cancelled', 'coachtribe-my-account' ),
		$subscription
	);
	$subject = coachtribe_my_account_replace_subscription_email_placeholders( $subject_template, $placeholders );

	$body_template = (string) apply_filters(
		'coachtribe_my_account_subscription_cancelled_email_body',
		__( "Your subscription to [Plan Name] has been successfully cancelled.\n\nIf you have any questions or concerns, please contact support at [Support Email].", 'coachtribe-my-account' ),
		$subscription
	);
	$body_plain = coachtribe_my_account_replace_subscription_email_placeholders( $body_template, $placeholders );
	$paras      = array_filter( array_map( 'trim', explode( "\n\n", $body_plain ) ) );
	$inner      = '';
	foreach ( $paras as $p ) {
		$inner .= '<p style="margin:0 0 14px;">' . esc_html( $p ) . '</p>';
	}
	$body = coachtribe_my_account_subscription_email_html_wrapper( $inner );

	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'Reply-To: ' . sanitize_email( $support_email ),
	);

	/**
	 * Vóór verzenden opzeg-bevestiging.
	 *
	 * @param WC_Subscription $subscription Abonnement.
	 * @param string          $to           Ontvanger.
	 */
	do_action( 'coachtribe_my_account_before_send_subscription_cancelled_email', $subscription, $to );

	return (bool) wp_mail( $to, $subject, $body, $headers );
}

/**
 * Verstuurt bevestigingsmail na subscription switch (upgrade).
 *
 * @param WC_Order $order Switch-bestelling.
 * @return bool True als wp_mail meldt succes.
 */
function coachtribe_my_account_send_subscription_upgraded_user_email( $order ) {
	if ( ! $order instanceof WC_Order || ! function_exists( 'wp_mail' ) ) {
		return false;
	}

	if ( ! apply_filters( 'coachtribe_my_account_should_send_subscription_upgraded_email', true, $order ) ) {
		return true;
	}

	$to = sanitize_email( $order->get_billing_email() );
	if ( '' === $to || ! is_email( $to ) ) {
		return false;
	}

	$new_plan = '';
	foreach ( $order->get_items() as $item ) {
		if ( $item->is_type( 'line_item' ) ) {
			$new_plan = wp_strip_all_tags( $item->get_name() );
			break;
		}
	}
	if ( '' === $new_plan ) {
		$new_plan = __( 'your new plan', 'coachtribe-my-account' );
	}

	$support_email = coachtribe_my_account_support_email();
	$site_name     = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$user_name     = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
	if ( '' === $user_name ) {
		$user_name = $order->get_billing_email();
	}

	$placeholders = array(
		'New Plan Name' => $new_plan,
		'User Name'     => $user_name,
		'Support Email' => $support_email,
		'Site Name'     => $site_name,
	);

	$subject_template = (string) apply_filters(
		'coachtribe_my_account_subscription_upgraded_email_subject',
		__( 'Your Subscription has been Upgraded', 'coachtribe-my-account' ),
		$order
	);
	$subject = coachtribe_my_account_replace_subscription_email_placeholders( $subject_template, $placeholders );

	$body_template = (string) apply_filters(
		'coachtribe_my_account_subscription_upgraded_email_body',
		__( "Your subscription has been upgraded to [New Plan Name].\n\nThank you for choosing to upgrade your plan. If you have any questions, feel free to contact us at [Support Email].", 'coachtribe-my-account' ),
		$order
	);
	$body_plain = coachtribe_my_account_replace_subscription_email_placeholders( $body_template, $placeholders );
	$paras      = array_filter( array_map( 'trim', explode( "\n\n", $body_plain ) ) );
	$inner      = '';
	foreach ( $paras as $p ) {
		$inner .= '<p style="margin:0 0 14px;">' . esc_html( $p ) . '</p>';
	}
	$body = coachtribe_my_account_subscription_email_html_wrapper( $inner );

	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'Reply-To: ' . sanitize_email( $support_email ),
	);

	/**
	 * Vóór verzenden upgrade-bevestiging.
	 *
	 * @param WC_Order $order Switch-bestelling.
	 * @param string   $to    Ontvanger.
	 */
	do_action( 'coachtribe_my_account_before_send_subscription_upgraded_email', $order, $to );

	return (bool) wp_mail( $to, $subject, $body, $headers );
}

/**
 * URL terug naar Mijn account met query + nonce voor upgrade-afbreukmelding.
 *
 * @return string
 */
function coachtribe_my_account_subscription_upgrade_cancel_return_url() {
	if ( ! function_exists( 'wc_get_account_endpoint_url' ) ) {
		return '';
	}
	return wp_nonce_url(
		add_query_arg( 'ct_subscription_upgrade', 'cancelled', wc_get_account_endpoint_url( 'dashboard' ) ),
		'ct_subscription_upgrade_cancelled',
		'_wpnonce'
	);
}

/**
 * Dutch labels for My Account menu (default + custom endpoints).
 *
 * @param array $items Endpoint slug => label.
 * @return array
 */
function coachtribe_my_account_menu_items( $items ) {
	$logout_label = null;
	if ( isset( $items['customer-logout'] ) ) {
		$logout_label = $items['customer-logout'];
		unset( $items['customer-logout'] );
	}

	foreach ( $items as $slug => $label ) {
		switch ( $slug ) {
			case 'dashboard':
				$items[ $slug ] = esc_html__( 'Dashboard', 'coachtribe-my-account' );
				break;
			case 'orders':
				$items[ $slug ] = esc_html__( 'Bestellingen', 'coachtribe-my-account' );
				break;
			case 'downloads':
				$items[ $slug ] = esc_html__( 'Downloads', 'coachtribe-my-account' );
				break;
			case 'edit-address':
				$items[ $slug ] = esc_html__( 'Adressen', 'coachtribe-my-account' );
				break;
			case 'payment-methods':
				$items[ $slug ] = esc_html__( 'Betaalmethoden', 'coachtribe-my-account' );
				break;
			case 'edit-account':
				$items[ $slug ] = esc_html__( 'Accountgegevens', 'coachtribe-my-account' );
				break;
			case 'facturen':
				$items[ $slug ] = esc_html__( 'Facturen', 'coachtribe-my-account' );
				break;
			case 'wachtwoord':
				$items[ $slug ] = esc_html__( 'Wachtwoord', 'coachtribe-my-account' );
				break;
			case 'instellingen':
				$items[ $slug ] = esc_html__( 'Instellingen', 'coachtribe-my-account' );
				break;
			default:
				$items[ $slug ] = $label;
				break;
		}
	}

	$items['facturen']     = esc_html__( 'Facturen', 'coachtribe-my-account' );
	$items['wachtwoord']   = esc_html__( 'Wachtwoord', 'coachtribe-my-account' );
	$items['instellingen'] = esc_html__( 'Instellingen', 'coachtribe-my-account' );

	if ( null !== $logout_label ) {
		$items['customer-logout'] = esc_html__( 'Uitloggen', 'coachtribe-my-account' );
	}

	return $items;
}

/**
 * Point WooCommerce to plugin templates where we override behaviour.
 *
 * @param string $template      Located template path.
 * @param string $template_name Template name.
 * @param string $template_path Template path within WC.
 * @return string
 */
function coachtribe_my_account_locate_template( $template, $template_name, $template_path ) {
	if ( ! apply_filters( 'coachtribe_my_account_use_plugin_templates', true, $template_name, $template ) ) {
		return $template;
	}

	$plugin_path = COACHTRIBE_MY_ACCOUNT_PATH . 'templates/';

	if ( 'myaccount/my-account.php' === $template_name ) {
		$custom = $plugin_path . 'account-main.php';
		if ( is_readable( $custom ) ) {
			return $custom;
		}
	}

	// Minimaal dashboard: standaard WC-dashboardtekst verbergen; hook `woocommerce_account_dashboard` blijft beschikbaar.
	if ( 'myaccount/dashboard.php' === $template_name ) {
		$custom = $plugin_path . 'woocommerce/myaccount/dashboard.php';
		if ( is_readable( $custom ) ) {
			return $custom;
		}
	}

	return $template;
}

/**
 * Verwerk wachtwoordwijziging op het wachtwoord-endpoint (POST + nonce).
 */
function coachtribe_my_account_handle_password_change() {
	if ( ! is_user_logged_in() ) {
		return;
	}
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
		return;
	}
	if ( ! isset( $_POST['coachtribe_password_change_submit'] ) ) {
		return;
	}
	if ( ! function_exists( 'WC' ) || ! WC()->query ) {
		return;
	}
	if ( 'wachtwoord' !== WC()->query->get_current_endpoint() ) {
		return;
	}
	if ( ! function_exists( 'wc_add_notice' ) || ! function_exists( 'wc_get_account_endpoint_url' ) ) {
		return;
	}

	$redirect = wc_get_account_endpoint_url( 'wachtwoord' );

	if ( ! isset( $_POST['coachtribe_password_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['coachtribe_password_nonce'] ) ), 'coachtribe_password_change' ) ) {
		wc_add_notice( __( 'Ongeldige sessie. Vernieuw de pagina en probeer opnieuw.', 'coachtribe-my-account' ), 'error' );
		wp_safe_redirect( $redirect );
		exit;
	}

	$user    = wp_get_current_user();
	$current = isset( $_POST['ct_current_password'] ) ? (string) wp_unslash( $_POST['ct_current_password'] ) : '';
	$new     = isset( $_POST['ct_new_password'] ) ? (string) wp_unslash( $_POST['ct_new_password'] ) : '';
	$confirm = isset( $_POST['ct_confirm_password'] ) ? (string) wp_unslash( $_POST['ct_confirm_password'] ) : '';

	if ( '' === $current || '' === $new || '' === $confirm ) {
		wc_add_notice( __( 'Vul alle velden in.', 'coachtribe-my-account' ), 'error' );
		wp_safe_redirect( $redirect );
		exit;
	}

	if ( $new !== $confirm ) {
		wc_add_notice( __( 'Het nieuwe wachtwoord en de bevestiging komen niet overeen.', 'coachtribe-my-account' ), 'error' );
		wp_safe_redirect( $redirect );
		exit;
	}

	if ( ! wp_check_password( $current, $user->user_pass, $user->ID ) ) {
		wc_add_notice( __( 'Het huidige wachtwoord is onjuist.', 'coachtribe-my-account' ), 'error' );
		wp_safe_redirect( $redirect );
		exit;
	}

	$min_len = (int) apply_filters( 'coachtribe_my_account_password_min_length', 8 );
	if ( strlen( $new ) < $min_len ) {
		wc_add_notice(
			sprintf(
				/* translators: %d: minimum aantal tekens */
				__( 'Het nieuwe wachtwoord moet minimaal %d tekens bevatten.', 'coachtribe-my-account' ),
				$min_len
			),
			'error'
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	if ( function_exists( 'wc_validate_password_strength' ) ) {
		$min_strength = (int) apply_filters( 'woocommerce_min_password_strength', 3 );
		$hint         = $user->user_email . ' ' . $user->user_login;
		$score        = wc_validate_password_strength( $new, $hint );
		if ( $score < $min_strength ) {
			wc_add_notice( __( 'Het nieuwe wachtwoord is te zwak. Kies een sterker wachtwoord.', 'coachtribe-my-account' ), 'error' );
			wp_safe_redirect( $redirect );
			exit;
		}
	}

	$result = wp_update_user(
		array(
			'ID'        => (int) $user->ID,
			'user_pass' => $new,
		)
	);

	if ( is_wp_error( $result ) ) {
		wc_add_notice( wp_strip_all_tags( $result->get_error_message() ), 'error' );
	} else {
		wc_add_notice( __( 'Je wachtwoord is succesvol gewijzigd.', 'coachtribe-my-account' ), 'success' );
	}

	wp_safe_redirect( $redirect );
	exit;
}

/**
 * Valideer telefoonnummer (optioneel leeg).
 *
 * @param string $phone Telefoon.
 * @return bool
 */
function coachtribe_my_account_validate_settings_phone( $phone ) {
	$phone = trim( (string) $phone );
	if ( '' === $phone ) {
		return true;
	}
	if ( ! preg_match( '/^[\d\s\+\-\(\)]+$/u', $phone ) ) {
		return false;
	}
	$digits = preg_replace( '/\D/', '', $phone );
	$min    = (int) apply_filters( 'coachtribe_my_account_settings_phone_min_digits', 8 );
	$max    = (int) apply_filters( 'coachtribe_my_account_settings_phone_max_digits', 15 );
	$len    = strlen( $digits );
	return $len >= $min && $len <= $max;
}

/**
 * Verwerk accountinstellingen op het instellingen-endpoint (POST + nonce).
 */
function coachtribe_my_account_handle_settings_save() {
	if ( ! is_user_logged_in() ) {
		return;
	}
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
		return;
	}
	if ( ! isset( $_POST['coachtribe_settings_submit'] ) ) {
		return;
	}
	if ( ! function_exists( 'WC' ) || ! WC()->query ) {
		return;
	}
	if ( 'instellingen' !== WC()->query->get_current_endpoint() ) {
		return;
	}
	if ( ! function_exists( 'wc_add_notice' ) || ! function_exists( 'wc_get_account_endpoint_url' ) ) {
		return;
	}

	$redirect = wc_get_account_endpoint_url( 'instellingen' );

	if ( ! isset( $_POST['coachtribe_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['coachtribe_settings_nonce'] ) ), 'coachtribe_settings_save' ) ) {
		wc_add_notice( __( 'Ongeldige sessie. Vernieuw de pagina en probeer opnieuw.', 'coachtribe-my-account' ), 'error' );
		wp_safe_redirect( $redirect );
		exit;
	}

	$user = wp_get_current_user();
	$uid  = (int) $user->ID;

	$email_raw = isset( $_POST['ct_settings_email'] ) ? wp_unslash( $_POST['ct_settings_email'] ) : '';
	$email     = sanitize_email( $email_raw );
	$phone     = isset( $_POST['ct_settings_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['ct_settings_phone'] ) ) : '';

	$phone = apply_filters( 'coachtribe_my_account_settings_phone_before_save', $phone, $user );

	if ( '' === $email || ! is_email( $email ) ) {
		wc_add_notice( __( 'Voer een geldig e-mailadres in.', 'coachtribe-my-account' ), 'error' );
		wp_safe_redirect( $redirect );
		exit;
	}

	if ( ! coachtribe_my_account_validate_settings_phone( $phone ) ) {
		wc_add_notice( __( 'Voer een geldig telefoonnummer in (alleen cijfers en +, spaties, haakjes of streepjes; minimaal 8 cijfers).', 'coachtribe-my-account' ), 'error' );
		wp_safe_redirect( $redirect );
		exit;
	}

	$existing = email_exists( $email );
	if ( $existing && (int) $existing !== $uid ) {
		wc_add_notice( __( 'Dit e-mailadres is al in gebruik door een ander account.', 'coachtribe-my-account' ), 'error' );
		wp_safe_redirect( $redirect );
		exit;
	}

	$old_email = $user->user_email;

	$result = wp_update_user(
		array(
			'ID'         => $uid,
			'user_email' => $email,
		)
	);

	if ( is_wp_error( $result ) ) {
		wc_add_notice( wp_strip_all_tags( $result->get_error_message() ), 'error' );
		wp_safe_redirect( $redirect );
		exit;
	}

	update_user_meta( $uid, 'billing_email', $email );
	update_user_meta( $uid, 'billing_phone', $phone );

	if ( class_exists( 'WC_Customer' ) ) {
		try {
			$customer = new WC_Customer( $uid );
			$customer->set_email( $email );
			$customer->set_billing_email( $email );
			$customer->set_billing_phone( $phone );
			$customer->save();
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// WooCommerce-klant kon niet worden gesynchroniseerd; basisgegevens zijn al opgeslagen.
		}
	}

	if ( $old_email !== $email && apply_filters( 'coachtribe_my_account_send_email_change_notification', true, $uid, $old_email, $email ) ) {
		/**
		 * Mogelijkheid voor site-eigen e-mailverificatie (bijv. bevestigingslink).
		 *
		 * @param int    $uid      Gebruikers-ID.
		 * @param string $old_email Oud adres.
		 * @param string $email    Nieuw adres.
		 */
		do_action( 'coachtribe_my_account_email_changed', $uid, $old_email, $email );
	}

	/**
	 * Na succesvol opslaan van accountinstellingen.
	 *
	 * @param int    $uid   Gebruikers-ID.
	 * @param string $email E-mailadres.
	 * @param string $phone Telefoon.
	 */
	do_action( 'coachtribe_my_account_after_settings_save', $uid, $email, $phone );

	wc_add_notice( __( 'Je wijzigingen zijn opgeslagen.', 'coachtribe-my-account' ), 'success' );

	wp_safe_redirect( $redirect );
	exit;
}

/**
 * Profiel bewerken vanaf het Overzicht (dashboard): weergavenaam, e-mail, telefoon.
 */
function coachtribe_my_account_handle_profile_edit_save() {
	if ( ! is_user_logged_in() ) {
		return;
	}
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
		return;
	}
	if ( ! isset( $_POST['coachtribe_profile_edit_submit'] ) ) {
		return;
	}
	if ( ! function_exists( 'WC' ) || ! WC()->query ) {
		return;
	}

	$ct_ep = (string) WC()->query->get_current_endpoint();
	if ( '' !== $ct_ep && 'dashboard' !== $ct_ep ) {
		return;
	}

	if ( ! function_exists( 'wc_add_notice' ) || ! function_exists( 'wc_get_account_endpoint_url' ) ) {
		return;
	}

	$redirect = wc_get_account_endpoint_url( 'dashboard' );

	if ( ! isset( $_POST['coachtribe_profile_edit_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['coachtribe_profile_edit_nonce'] ) ), 'coachtribe_profile_edit' ) ) {
		wc_add_notice( __( 'Invalid session. Please refresh the page and try again.', 'coachtribe-my-account' ), 'error' );
		wp_safe_redirect( $redirect );
		exit;
	}

	$user = wp_get_current_user();
	$uid  = (int) $user->ID;

	$display_raw = isset( $_POST['ct_profile_display_name'] ) ? wp_unslash( $_POST['ct_profile_display_name'] ) : '';
	$display_name = sanitize_text_field( $display_raw );
	$display_name = apply_filters( 'coachtribe_my_account_profile_edit_display_name', $display_name, $user );

	$email_raw = isset( $_POST['ct_profile_email'] ) ? wp_unslash( $_POST['ct_profile_email'] ) : '';
	$email     = sanitize_email( $email_raw );

	$phone = isset( $_POST['ct_profile_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['ct_profile_phone'] ) ) : '';
	$phone = apply_filters( 'coachtribe_my_account_settings_phone_before_save', $phone, $user );

	if ( '' === trim( $display_name ) ) {
		wc_add_notice( __( 'Please enter a display name.', 'coachtribe-my-account' ), 'error' );
		wp_safe_redirect( $redirect );
		exit;
	}

	if ( '' === $email || ! is_email( $email ) ) {
		wc_add_notice( __( 'Please enter a valid email address.', 'coachtribe-my-account' ), 'error' );
		wp_safe_redirect( $redirect );
		exit;
	}

	if ( ! coachtribe_my_account_validate_settings_phone( $phone ) ) {
		wc_add_notice(
			__( 'Please enter a valid phone number (digits and +, spaces, parentheses or hyphens; at least 8 digits).', 'coachtribe-my-account' ),
			'error'
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	$existing = email_exists( $email );
	if ( $existing && (int) $existing !== $uid ) {
		wc_add_notice( __( 'This email address is already in use by another account.', 'coachtribe-my-account' ), 'error' );
		wp_safe_redirect( $redirect );
		exit;
	}

	$old_email = $user->user_email;

	$result = wp_update_user(
		array(
			'ID'           => $uid,
			'display_name' => $display_name,
			'user_email'   => $email,
		)
	);

	if ( is_wp_error( $result ) ) {
		wc_add_notice( wp_strip_all_tags( $result->get_error_message() ), 'error' );
		wp_safe_redirect( $redirect );
		exit;
	}

	update_user_meta( $uid, 'billing_email', $email );
	update_user_meta( $uid, 'billing_phone', $phone );

	if ( class_exists( 'WC_Customer' ) ) {
		try {
			$customer = new WC_Customer( $uid );
			if ( is_callable( array( $customer, 'set_display_name' ) ) ) {
				$customer->set_display_name( $display_name );
			}
			$customer->set_email( $email );
			$customer->set_billing_email( $email );
			$customer->set_billing_phone( $phone );
			$customer->save();
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// WooCommerce-klant kon niet worden gesynchroniseerd; basisgegevens zijn al opgeslagen.
		}
	}

	if ( $old_email !== $email && apply_filters( 'coachtribe_my_account_send_email_change_notification', true, $uid, $old_email, $email ) ) {
		do_action( 'coachtribe_my_account_email_changed', $uid, $old_email, $email );
	}

	/**
	 * Na succesvol opslaan via het profiel-overzicht.
	 *
	 * @param int    $uid           Gebruikers-ID.
	 * @param string $display_name  Weergavenaam.
	 * @param string $email         E-mail.
	 * @param string $phone         Telefoon.
	 */
	do_action( 'coachtribe_my_account_after_profile_edit_save', $uid, $display_name, $email, $phone );

	wc_add_notice( __( 'Your profile has been updated successfully.', 'coachtribe-my-account' ), 'success' );

	wp_safe_redirect( add_query_arg( 'ct_profile_saved', '1', $redirect ) );
	exit;
}

/**
 * Of de CoachTribe account-shell actief is (WC account of shortcode-pagina).
 *
 * @return bool
 */
function coachtribe_my_account_is_shell_context() {
	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		return true;
	}
	return coachtribe_my_account_is_shortcode_on_singular();
}

/**
 * Standaard CoachTribe merklogo (PNG in plugin assets).
 *
 * @return string
 */
function coachtribe_my_account_get_brand_logo_url() {
	$default = COACHTRIBE_MY_ACCOUNT_URL . 'assets/images/coachtribe-logo.png';
	return (string) apply_filters( 'coachtribe_my_account_brand_logo_url', $default );
}

/**
 * Vervang thema-logo in site-header op account-pagina's door CoachTribe-logo.
 *
 * @param string $html    Logo HTML.
 * @param int    $blog_id Blog ID.
 * @return string
 */
function coachtribe_my_account_filter_get_custom_logo( $html, $blog_id ) {
	unset( $blog_id );
	if ( ! coachtribe_my_account_is_shell_context() ) {
		return $html;
	}

	$url  = coachtribe_my_account_get_brand_logo_url();
	$home = home_url( '/' );
	$alt  = __( 'CoachTribe', 'coachtribe-my-account' );

	return sprintf(
		'<a href="%1$s" class="custom-logo-link coachtribe-account-brand-logo" rel="home" itemprop="url">%2$s</a>',
		esc_url( $home ),
		sprintf(
			'<img src="%1$s" class="custom-logo coachtribe-account-brand-logo__img" alt="%2$s" decoding="async" />',
			esc_url( $url ),
			esc_attr( $alt )
		)
	);
}

/**
 * Elementor theme-site-logo widget: CoachTribe-logo op account-shell.
 *
 * @param string      $content Widget output.
 * @param \Elementor\Widget_Base $widget  Widget instance.
 * @return string
 */
function coachtribe_my_account_elementor_site_logo_content( $content, $widget ) {
	if ( ! coachtribe_my_account_is_shell_context() ) {
		return $content;
	}
	if ( ! is_object( $widget ) || ! method_exists( $widget, 'get_name' ) ) {
		return $content;
	}
	if ( 'theme-site-logo' !== $widget->get_name() ) {
		return $content;
	}

	$url  = coachtribe_my_account_get_brand_logo_url();
	$home = home_url( '/' );
	$alt  = __( 'CoachTribe', 'coachtribe-my-account' );

	return sprintf(
		'<div class="elementor-widget-container"><a href="%1$s" class="coachtribe-account-brand-logo" rel="home">%2$s</a></div>',
		esc_url( $home ),
		sprintf(
			'<img src="%1$s" class="coachtribe-account-brand-logo__img" alt="%2$s" decoding="async" />',
			esc_url( $url ),
			esc_attr( $alt )
		)
	);
}

/**
 * Body class voor full-width account-CSS (theme wrappers via :has(.ct-account)).
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function coachtribe_my_account_body_class( $classes ) {
	$on_wc_account = function_exists( 'is_account_page' ) && is_account_page();
	$on_shortcode  = coachtribe_my_account_is_shortcode_on_singular();
	if ( $on_wc_account || $on_shortcode ) {
		$classes[] = 'coachtribe-my-account-shell';
	}
	return $classes;
}

/**
 * Of de huidige singular content de shortcode bevat (voor assets).
 *
 * @return bool
 */
function coachtribe_my_account_is_shortcode_on_singular() {
	if ( ! is_singular() ) {
		return false;
	}
	$post = get_post();
	return $post && has_shortcode( (string) $post->post_content, 'coachtribe_my_account' );
}

/**
 * Parse optioneel `tab`-attribuut uit shortcode in post content (voor initialTab).
 *
 * @param string $content Post content.
 * @return string dashboard|facturen|instellingen|wachtwoord
 */
function coachtribe_my_account_parse_shortcode_tab( $content ) {
	$content = (string) $content;
	if ( ! preg_match( '/\[coachtribe_my_account\b([^\]]*)\]/', $content, $m ) ) {
		return 'dashboard';
	}
	$inner = $m[1];
	if ( preg_match( '/\btab\s*=\s*["\']([^"\']+)["\']/', $inner, $t ) ) {
		$tab = sanitize_key( $t[1] );
		$allowed = array( 'dashboard', 'facturen', 'instellingen', 'wachtwoord' );
		if ( in_array( $tab, $allowed, true ) ) {
			return $tab;
		}
	}
	return 'dashboard';
}

/**
 * Shortcode: toon het Mijn-account-dashboard op elke pagina.
 *
 * Usage: [coachtribe_my_account] of [coachtribe_my_account tab="facturen"]
 *
 * @param array|string $atts Shortcode attributes.
 * @return string HTML.
 */
function coachtribe_my_account_shortcode( $atts ) {
	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'WC' ) || ! WC()->query ) {
		return '';
	}

	$atts = shortcode_atts(
		array(
			'tab' => 'dashboard',
		),
		$atts,
		'coachtribe_my_account'
	);

	$tab = sanitize_key( $atts['tab'] );
	$allowed = array( 'dashboard', 'facturen', 'instellingen', 'wachtwoord' );
	if ( ! in_array( $tab, $allowed, true ) ) {
		$tab = 'dashboard';
	}

	if ( ! is_user_logged_in() ) {
		$login_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
		ob_start();
		?>
		<p class="coachtribe-my-account-shortcode-login" style="margin:1rem 0;font-size:1rem;">
			<a href="<?php echo esc_url( $login_url ); ?>" style="color:rgb(255,77,109);font-weight:600;text-decoration:underline;"><?php esc_html_e( 'Log in om je account te bekijken.', 'coachtribe-my-account' ); ?></a>
		</p>
		<?php
		return (string) ob_get_clean();
	}

	$forced = ( 'dashboard' === $tab ) ? '' : $tab;
	$initial_tab = coachtribe_my_account_normalize_tab_slug( $forced );
	coachtribe_my_account_enqueue_front_assets( $initial_tab );

	$GLOBALS['coachtribe_my_account_forced_endpoint'] = $forced;

	ob_start();
	$tpl = COACHTRIBE_MY_ACCOUNT_PATH . 'templates/account-main.php';
	if ( file_exists( $tpl ) ) {
		include $tpl;
	}
	unset( $GLOBALS['coachtribe_my_account_forced_endpoint'] );

	return (string) ob_get_clean();
}

/**
 * Registreer CSS/JS + localize (herbruikbaar vanaf shortcode of wp_enqueue_scripts).
 *
 * @param string $initial_tab dashboard|facturen|instellingen|wachtwoord|wc-default
 */
function coachtribe_my_account_enqueue_front_assets( $initial_tab ) {
	$ct_ac_css_deps = array( 'dashicons' );
	if ( wp_style_is( 'elementor-frontend', 'registered' ) ) {
		$ct_ac_css_deps[] = 'elementor-frontend';
	}

	$ct_ac_css_path = COACHTRIBE_MY_ACCOUNT_PATH . 'assets/css/account.css';
	$ct_ac_js_path  = COACHTRIBE_MY_ACCOUNT_PATH . 'assets/js/account.js';
	$ct_ac_css_ver  = COACHTRIBE_MY_ACCOUNT_VERSION;
	$ct_ac_js_ver   = COACHTRIBE_MY_ACCOUNT_VERSION;
	if ( file_exists( $ct_ac_css_path ) ) {
		$ct_ac_css_ver .= '.' . (string) filemtime( $ct_ac_css_path );
	}
	if ( file_exists( $ct_ac_js_path ) ) {
		$ct_ac_js_ver .= '.' . (string) filemtime( $ct_ac_js_path );
	}

	wp_enqueue_style(
		'coachtribe-my-account',
		COACHTRIBE_MY_ACCOUNT_URL . 'assets/css/account.css',
		$ct_ac_css_deps,
		$ct_ac_css_ver
	);

	wp_enqueue_script(
		'coachtribe-my-account',
		COACHTRIBE_MY_ACCOUNT_URL . 'assets/js/account.js',
		array(),
		$ct_ac_js_ver,
		true
	);

	if ( function_exists( 'wp_script_add_data' ) ) {
		wp_script_add_data( 'coachtribe-my-account', 'strategy', 'defer' );
	}

	$tab_nonce               = wp_create_nonce( 'coachtribe_my_account_tabs' );
	$cancel_subscription_nonce = wp_create_nonce( 'coachtribe_cancel_subscription' );
	$profile_image_nonce   = wp_create_nonce( 'coachtribe_profile_image_upload' );
	$profile_max_bytes   = (int) apply_filters( 'coachtribe_my_account_profile_image_max_bytes', 2 * ( defined( 'MB_IN_BYTES' ) ? MB_IN_BYTES : 1048576 ) );

	$endpoint_urls = array();
	if ( function_exists( 'wc_get_account_endpoint_url' ) ) {
		$endpoint_urls = array(
			'dashboard'    => wc_get_account_endpoint_url( 'dashboard' ),
			'facturen'     => wc_get_account_endpoint_url( 'facturen' ),
			'instellingen' => wc_get_account_endpoint_url( 'instellingen' ),
			'wachtwoord'   => wc_get_account_endpoint_url( 'wachtwoord' ),
		);
	}

	wp_localize_script( 
		'coachtribe-my-account',
		'coachtribeMyAccount',
		array(
			'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
			'tabNonce'         => $tab_nonce,
			'tabAction'        => 'coachtribe_my_account_tab',
			'endpointUrls'     => $endpoint_urls,
			'initialTab'       => $initial_tab,
			'ajaxTabs'         => array( 'dashboard', 'facturen', 'instellingen', 'wachtwoord', 'view-subscription' ),
			'passwordMismatch' => __( 'Het nieuwe wachtwoord en de bevestiging komen niet overeen.', 'coachtribe-my-account' ),
			'invalidEmail'              => __( 'Voer een geldig e-mailadres in.', 'coachtribe-my-account' ),
			'invalidPhone'              => __( 'Voer een geldig telefoonnummer in (alleen cijfers en +, spaties, haakjes of streepjes; minimaal 8 cijfers).', 'coachtribe-my-account' ),
			'profileDisplayNameRequired' => __( 'Please enter a display name.', 'coachtribe-my-account' ),
			'tabLoadError'     => __( 'Kon dit tabblad niet laden. Vernieuw de pagina en probeer opnieuw.', 'coachtribe-my-account' ),
			'profileImageNonce'   => $profile_image_nonce,
			'profileImageAction'  => 'coachtribe_my_account_profile_image',
			'profileImageMaxBytes' => max( 102400, $profile_max_bytes ),
			'profileImageTooBig'  => __( 'Bestand is te groot. Kies een afbeelding van maximaal 2 MB.', 'coachtribe-my-account' ),
			'profileImageBadType' => __( 'Alleen JPEG-, PNG-, GIF- of WebP-afbeeldingen zijn toegestaan.', 'coachtribe-my-account' ),
			'profileImageError'   => __( 'Upload mislukt. Probeer het opnieuw.', 'coachtribe-my-account' ),
			'profileImageOk'      => __( 'Profielfoto bijgewerkt.', 'coachtribe-my-account' ),
			'profileImageUploading' => __( 'Uploaden…', 'coachtribe-my-account' ),
			'cancelSubscriptionNonce'  => $cancel_subscription_nonce,
			'cancelSubscriptionAction'   => 'coachtribe_my_account_cancel_subscription',
			'subscriptionCancelModalTitle' => __( 'Cancel subscription', 'coachtribe-my-account' ),
			'subscriptionCancelModalMessage' => __( 'Are you sure you want to cancel your subscription? This action cannot be undone.', 'coachtribe-my-account' ),
			'subscriptionCancelModalConfirm' => __( 'Confirm', 'coachtribe-my-account' ),
			'subscriptionCancelModalDismiss' => __( 'Cancel', 'coachtribe-my-account' ),
			'subscriptionCancelSuccess' => __( 'Your subscription has been successfully cancelled.', 'coachtribe-my-account' ),
			'subscriptionCancelError' => __( 'Cancellation failed. Please try again or contact support.', 'coachtribe-my-account' ),
		)
	);
}

/**
 * Enqueue front-end assets on WooCommerce My Account page of op pagina's met de shortcode.
 */
function coachtribe_my_account_enqueue_assets() {
	$load_for_shortcode = coachtribe_my_account_is_shortcode_on_singular();
	if ( ! $load_for_shortcode && ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) ) {
		return;
	}

	$current_ep = '';
	if ( function_exists( 'is_account_page' ) && is_account_page() && function_exists( 'WC' ) && WC()->query ) {
		$current_ep = (string) WC()->query->get_current_endpoint();
	}
	if ( '' === $current_ep ) {
		$current_ep = coachtribe_my_account_detect_endpoint_from_url();
	}
	if ( '' === $current_ep && $load_for_shortcode ) {
		$post   = get_post();
		$parsed = $post ? coachtribe_my_account_parse_shortcode_tab( (string) $post->post_content ) : 'dashboard';
		$current_ep = ( 'dashboard' === $parsed ) ? '' : $parsed;
	}
	$initial_tab = coachtribe_my_account_normalize_tab_slug( $current_ep );

	coachtribe_my_account_enqueue_front_assets( $initial_tab );
}
