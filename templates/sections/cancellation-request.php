<?php
/**
 * Shared cancellation UI (rendered by [coachtribe_cancellation]) — same design for
 * every member. The AJAX endpoint decides what happens on submit:
 *   - WooCommerce : cancel the subscription.
 *   - Free (PMPro): cancel the membership.
 *   - Plug&Pay    : store a "cancellation requested" flag + notify the team.
 *
 * Self-contained (inline style + script) so it also works on a standalone
 * Elementor/PMPro page where the account assets are not enqueued.
 *
 * @package CoachTribe_My_Account
 */

defined( 'ABSPATH' ) || exit;

$ct_cancel_uid     = get_current_user_id();
$ct_cancel_type    = function_exists( 'coachtribe_my_account_get_member_type' ) ? coachtribe_my_account_get_member_type( $ct_cancel_uid ) : 'woocommerce';
$ct_cancel_is_pnp  = ( 'plug_and_pay' === $ct_cancel_type );
$ct_cancel_already = ( $ct_cancel_is_pnp && '' !== (string) get_user_meta( $ct_cancel_uid, 'ct_cancellation_requested', true ) );
$ct_cancel_nonce   = wp_create_nonce( 'coachtribe_cancellation_request' );
$ct_cancel_ajax    = admin_url( 'admin-ajax.php' );
$ct_cancel_reasons = function_exists( 'coachtribe_my_account_cancellation_reasons' ) ? coachtribe_my_account_cancellation_reasons() : array();
$ct_cancel_back    = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'dashboard' ) : home_url( '/' );
$ct_cancel_sum     = function_exists( 'coachtribe_my_account_cancellation_summary' )
	? coachtribe_my_account_cancellation_summary( $ct_cancel_uid )
	: array(
		'plan'         => '—',
		'amount'       => '—',
		'access_until' => '—',
	);

// Warning text differs slightly per provider; the design stays the same.
if ( $ct_cancel_is_pnp ) {
	$ct_cancel_warn = __( 'Je abonnement loopt via Plug&Pay. Na je verzoek zeggen wij het voor je op. Je behoudt toegang tot het einde van je factureringsperiode.', 'coachtribe-my-account' );
} elseif ( '—' !== $ct_cancel_sum['access_until'] ) {
	$ct_cancel_warn = sprintf(
		/* translators: %s: access-until date */
		__( 'Na het opzeggen wordt je abonnement niet meer verlengd. Je behoudt toegang tot en met %s.', 'coachtribe-my-account' ),
		$ct_cancel_sum['access_until']
	);
} else {
	$ct_cancel_warn = __( 'Na het opzeggen wordt je abonnement niet meer verlengd. Je behoudt toegang tot het einde van je factureringsperiode.', 'coachtribe-my-account' );
}
?>
<style>
	.ct-cancel{--ct-c-accent:rgb(255,77,109);--ct-c-bg:#1a1a1a;--ct-c-ring:#8b1d2c;--ct-c-border:#2a2a2a;--ct-c-text:#fff;--ct-c-muted:#aaa;
		max-width:860px;font-family:"Segoe UI",-apple-system,BlinkMacSystemFont,Roboto,sans-serif;color:var(--ct-c-text)}
	.ct-cancel__sub{background:var(--ct-c-bg);border:1px solid var(--ct-c-border);border-radius:12px;padding:20px 22px;margin-bottom:20px}
	.ct-cancel__sub-title{margin:0 0 16px;font-size:17px;font-weight:700}
	.ct-cancel__sub-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:18px}
	.ct-cancel__sub-item{display:flex;align-items:center;gap:12px}
	.ct-cancel__sub-ic{flex:0 0 auto;width:44px;height:44px;border-radius:50%;background:var(--ct-c-ring);
		display:flex;align-items:center;justify-content:center;color:#fff}
	.ct-cancel__sub-label{display:block;color:var(--ct-c-muted);font-size:12.5px;margin-bottom:2px}
	.ct-cancel__sub-value{display:block;font-weight:700;font-size:14.5px}
	.ct-cancel__warn{display:flex;gap:14px;align-items:flex-start;background:rgba(232,163,61,.08);
		border:1px solid #e8a33d;border-radius:12px;padding:16px 18px;margin-bottom:22px}
	.ct-cancel__warn svg{flex:0 0 auto;margin-top:2px}
	.ct-cancel__warn strong{display:block;margin-bottom:4px;font-size:15px}
	.ct-cancel__warn span{color:var(--ct-c-muted);font-size:13.5px;line-height:1.5}
	.ct-cancel__card{background:var(--ct-c-bg);border:1px solid var(--ct-c-border);border-radius:12px;padding:22px 22px}
	.ct-cancel__label{display:block;font-weight:600;font-size:14px;margin:0 0 8px}
	.ct-cancel__opt{font-weight:400;color:var(--ct-c-muted);font-size:13px}
	.ct-cancel__select{width:100%;padding:11px 12px;background:#111;color:var(--ct-c-text);
		border:1px solid var(--ct-c-border);border-radius:8px;font-size:14px;margin-bottom:20px}
	.ct-cancel__check{display:flex;gap:10px;align-items:flex-start;font-size:14px;line-height:1.5;cursor:pointer}
	.ct-cancel__check input{margin-top:3px;flex:0 0 auto;width:18px;height:18px;accent-color:var(--ct-c-accent)}
	.ct-cancel__actions{display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;align-items:center;margin-top:22px}
	.ct-cancel__btn{display:inline-flex;align-items:center;gap:8px;padding:12px 20px;border-radius:8px;
		font-weight:700;font-size:14px;border:1px solid var(--ct-c-border);cursor:pointer;text-decoration:none}
	.ct-cancel__btn--back{background:transparent;color:var(--ct-c-text)}
	.ct-cancel__btn--danger{background:var(--ct-c-accent);color:#fff;border-color:var(--ct-c-accent)}
	.ct-cancel__btn--danger:disabled{opacity:.45;cursor:not-allowed}
	.ct-cancel__hint{color:var(--ct-c-muted);font-size:12.5px;margin:10px 0 0;text-align:right}
	.ct-cancel__notice{background:var(--ct-c-bg);border:1px solid var(--ct-c-border);border-left:4px solid var(--ct-c-accent);
		border-radius:10px;padding:16px 18px;font-size:14.5px;line-height:1.55}
</style>

<div class="ct-cancel" data-ct-cancel>

	<div class="ct-cancel__sub">
		<h3 class="ct-cancel__sub-title"><?php esc_html_e( 'Mijn abonnement', 'coachtribe-my-account' ); ?></h3>
		<div class="ct-cancel__sub-grid">
			<div class="ct-cancel__sub-item">
				<span class="ct-cancel__sub-ic" aria-hidden="true">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 8l4 3 5-6 5 6 4-3-2 11H5L3 8z" stroke="#fff" stroke-width="1.6" stroke-linejoin="round"/></svg>
				</span>
				<span>
					<span class="ct-cancel__sub-label"><?php esc_html_e( 'Soort abonnement', 'coachtribe-my-account' ); ?></span>
					<span class="ct-cancel__sub-value"><?php echo esc_html( $ct_cancel_sum['plan'] ); ?></span>
				</span>
			</div>
			<div class="ct-cancel__sub-item">
				<span class="ct-cancel__sub-ic" aria-hidden="true">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="6" width="18" height="12" rx="2" stroke="#fff" stroke-width="1.6"/><path d="M3 10h18" stroke="#fff" stroke-width="1.6"/></svg>
				</span>
				<span>
					<span class="ct-cancel__sub-label"><?php esc_html_e( 'Bedrag', 'coachtribe-my-account' ); ?></span>
					<span class="ct-cancel__sub-value"><?php echo esc_html( $ct_cancel_sum['amount'] ); ?></span>
				</span>
			</div>
			<div class="ct-cancel__sub-item">
				<span class="ct-cancel__sub-ic" aria-hidden="true">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="9" stroke="#fff" stroke-width="1.6"/><path d="M12 7v5l3 3" stroke="#fff" stroke-width="1.6" stroke-linecap="round"/></svg>
				</span>
				<span>
					<span class="ct-cancel__sub-label"><?php esc_html_e( 'Toegang tot en met', 'coachtribe-my-account' ); ?></span>
					<span class="ct-cancel__sub-value"><?php echo esc_html( $ct_cancel_sum['access_until'] ); ?></span>
				</span>
			</div>
		</div>
	</div>

	<div class="ct-cancel__warn">
		<svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
			<path d="M12 3l9 16H3L12 3z" stroke="#e8a33d" stroke-width="1.8" stroke-linejoin="round"/>
			<path d="M12 10v4M12 16.5v.5" stroke="#e8a33d" stroke-width="1.8" stroke-linecap="round"/>
		</svg>
		<div>
			<strong><?php esc_html_e( 'Je staat op het punt je abonnement op te zeggen', 'coachtribe-my-account' ); ?></strong>
			<span><?php echo esc_html( $ct_cancel_warn ); ?></span>
		</div>
	</div>

	<?php if ( $ct_cancel_already ) : ?>
		<div class="ct-cancel__notice" data-ct-cancel-done>
			<?php esc_html_e( 'Je opzegverzoek is al ontvangen. We verwerken het zo snel mogelijk. Je hoeft niets meer te doen.', 'coachtribe-my-account' ); ?>
		</div>
	<?php else : ?>
		<div class="ct-cancel__card">
			<label class="ct-cancel__label" for="ct-cancel-reason">
				<?php esc_html_e( 'Waarom zeg je je abonnement op?', 'coachtribe-my-account' ); ?>
				<span class="ct-cancel__opt"><?php esc_html_e( '(optioneel)', 'coachtribe-my-account' ); ?></span>
			</label>
			<select id="ct-cancel-reason" class="ct-cancel__select" data-ct-cancel-reason>
				<option value=""><?php esc_html_e( 'Kies een reden (optioneel)', 'coachtribe-my-account' ); ?></option>
				<?php foreach ( $ct_cancel_reasons as $ct_reason_key => $ct_reason_label ) : ?>
					<option value="<?php echo esc_attr( $ct_reason_key ); ?>"><?php echo esc_html( $ct_reason_label ); ?></option>
				<?php endforeach; ?>
			</select>

			<label class="ct-cancel__check">
				<input type="checkbox" data-ct-cancel-confirm />
				<span><?php esc_html_e( 'Ik begrijp dat mijn abonnement niet meer wordt verlengd. Je behoudt toegang tot het einde van je factureringsperiode.', 'coachtribe-my-account' ); ?></span>
			</label>

			<div class="ct-cancel__actions">
				<a class="ct-cancel__btn ct-cancel__btn--back" href="<?php echo esc_url( $ct_cancel_back ); ?>">
					<?php esc_html_e( 'Terug naar mijn account', 'coachtribe-my-account' ); ?>
				</a>
				<button type="button" class="ct-cancel__btn ct-cancel__btn--danger" data-ct-cancel-submit disabled>
					<?php esc_html_e( 'Ja, abonnement opzeggen', 'coachtribe-my-account' ); ?>
				</button>
			</div>
			<p class="ct-cancel__hint" data-ct-cancel-hint><?php esc_html_e( 'Vink het vakje hierboven aan om door te gaan.', 'coachtribe-my-account' ); ?></p>
		</div>
	<?php endif; ?>
</div>

<script>
(function(){
	var root = document.querySelector('[data-ct-cancel]');
	if (!root || root.querySelector('[data-ct-cancel-done]')) { return; }

	var ajaxUrl = <?php echo wp_json_encode( $ct_cancel_ajax ); ?>;
	var nonce   = <?php echo wp_json_encode( $ct_cancel_nonce ); ?>;

	var confirm = root.querySelector('[data-ct-cancel-confirm]');
	var submit  = root.querySelector('[data-ct-cancel-submit]');
	var reason  = root.querySelector('[data-ct-cancel-reason]');
	var hint    = root.querySelector('[data-ct-cancel-hint]');
	var card    = root.querySelector('.ct-cancel__card');

	if (confirm && submit) {
		confirm.addEventListener('change', function(){ submit.disabled = !confirm.checked; });
	}

	if (submit) {
		submit.addEventListener('click', function(){
			if (submit.disabled) { return; }
			submit.disabled = true;
			submit.textContent = <?php echo wp_json_encode( __( 'Bezig…', 'coachtribe-my-account' ) ); ?>;

			var body = new URLSearchParams();
			body.append('action', 'coachtribe_cancellation_request');
			body.append('nonce', nonce);
			body.append('reason', reason ? reason.value : '');

			fetch(ajaxUrl, { method:'POST', credentials:'same-origin', body:body })
				.then(function(r){ return r.json(); })
				.then(function(res){
					var msg = (res && res.data && res.data.message) ? res.data.message : '';
					if (res && res.success) {
						var box = document.createElement('div');
						box.className = 'ct-cancel__notice';
						box.textContent = msg || 'OK';
						if (card) { card.replaceWith(box); }
					} else {
						if (hint) { hint.textContent = msg || 'Er ging iets mis. Probeer het opnieuw.'; }
						submit.disabled = false;
						submit.textContent = <?php echo wp_json_encode( __( 'Ja, abonnement opzeggen', 'coachtribe-my-account' ) ); ?>;
					}
				})
				.catch(function(){
					if (hint) { hint.textContent = <?php echo wp_json_encode( __( 'Netwerkfout. Probeer het opnieuw.', 'coachtribe-my-account' ) ); ?>; }
					submit.disabled = false;
					submit.textContent = <?php echo wp_json_encode( __( 'Ja, abonnement opzeggen', 'coachtribe-my-account' ) ); ?>;
				});
		});
	}
})();
</script>
