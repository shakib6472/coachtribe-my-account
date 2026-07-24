/**
 * CoachTribe Mijn account — tabbladen, AJAX-inhoud en UI-gedrag (vanilla JS).
 */
(function () {
	'use strict';

	function closeDropdown(root) {
		if (!root) {
			return;
		}
		var toggle = root.querySelector('[data-ct-account-dropdown-toggle]');
		var menu = root.querySelector('[data-ct-account-dropdown-menu]');
		root.classList.remove('is-open');
		if (toggle) {
			toggle.setAttribute('aria-expanded', 'false');
		}
		if (menu) {
			menu.setAttribute('hidden', 'hidden');
		}
	}

	function closeAllDropdowns(exceptRoot) {
		document.querySelectorAll('[data-ct-account-dropdown]').forEach(function (root) {
			if (root !== exceptRoot) {
				closeDropdown(root);
			}
		});
	}

	function getCfg() {
		return typeof coachtribeMyAccount !== 'undefined' ? coachtribeMyAccount : null;
	}

	function isAjaxTab(tab) {
		var cfg = getCfg();
		if (!cfg || !cfg.ajaxTabs) {
			return false;
		}
		return cfg.ajaxTabs.indexOf(tab) !== -1;
	}

	function sortEndpointKeysByUrlLength(cfg) {
		var urls = cfg.endpointUrls || {};
		return Object.keys(urls).sort(function (a, b) {
			return (urls[b] || '').length - (urls[a] || '').length;
		});
	}

	function detectTabFromPathname() {
		var known = ['facturen', 'instellingen', 'wachtwoord'];
		var segments = (window.location.pathname || '').split('/').filter(Boolean);
		var found = '';
		for (var i = 0; i < segments.length; i++) {
			if (known.indexOf(segments[i]) !== -1) {
				found = segments[i];
			}
		}
		return found;
	}

	function detectTabFromLocation(cfg) {
		if (!cfg || !cfg.endpointUrls) {
			return detectTabFromPathname() || 'dashboard';
		}
		var norm = function (u) {
			return (u || '').replace(/\/+$/, '');
		};
		var href = norm(window.location.href.split('#')[0]);
		var keys = sortEndpointKeysByUrlLength(cfg);
		for (var i = 0; i < keys.length; i++) {
			var k = keys[i];
			if (k === 'dashboard') {
				continue;
			}
			var u = norm(cfg.endpointUrls[k]);
			if (u && href.indexOf(u) === 0) {
				return k;
			}
		}
		var d = norm(cfg.endpointUrls.dashboard);
		if (d && (href === d || href.indexOf(d + '/') === 0)) {
			if (href === d) {
				return 'dashboard';
			}
			var rest = href.slice((d + '/').length);
			var firstSeg = rest.split('/')[0] || '';
			if (firstSeg === '' || firstSeg === 'dashboard') {
				return 'dashboard';
			}
			return null;
		}
		return detectTabFromPathname() || 'dashboard';
	}

	function syncTablistUI(root, activeTab) {
		var onInstellingen = activeTab === 'instellingen';
		var onOverzicht = activeTab === 'dashboard';
		root.classList.toggle('ct-account--tab-instellingen', onInstellingen);
		root.classList.toggle('ct-account--tab-overzicht', onOverzicht);
		root.querySelectorAll('.ct-account-tab-select-option--instellingen-only').forEach(function (opt) {
			opt.hidden = !onInstellingen;
			opt.disabled = !onInstellingen;
		});
		root.querySelectorAll('a.ct-account-tab[data-ct-tab]').forEach(function (a) {
			var t = a.getAttribute('data-ct-tab');
			var on = t === activeTab;
			a.classList.toggle('is-active', on);
		});
		var sel = root.querySelector('[data-ct-account-tab-select]');
		if (sel && sel.tagName === 'SELECT') {
			var has = false;
			for (var oi = 0; oi < sel.options.length; oi++) {
				if (sel.options[oi].value === activeTab) {
					has = true;
					break;
				}
			}
			if (has) {
				sel.value = activeTab;
			}
		}
	}

	function setPanelsLoading(panelsRoot, on) {
		if (panelsRoot) {
			panelsRoot.classList.toggle('is-ct-tab-loading', !!on);
		}
	}

	function deactivatePanels(panelsRoot) {
		panelsRoot.querySelectorAll('.ct-account-tab-content').forEach(function (el) {
			el.classList.remove('is-active');
			el.setAttribute('aria-hidden', 'true');
		});
	}

	function activatePanel(el) {
		if (!el) {
			return;
		}
		el.classList.add('is-active');
		el.removeAttribute('aria-hidden');
	}

	var ctTabFetchAbort = null;

	function getTabCacheKey(tab, opts) {
		opts = opts || {};
		if (tab === 'view-subscription' && opts.subscriptionId) {
			return tab + '-' + String(opts.subscriptionId);
		}
		return tab;
	}

	function isExternalTab(tab) {
		return tab === 'upgrade-pro' || tab === 'payment-method' || tab === 'payment-methods';
	}

	function fetchTabHtml(tab, opts) {
		opts = opts || {};
		var cfg = getCfg();
		if (!cfg || !cfg.ajaxUrl || !cfg.tabNonce || !cfg.tabAction) {
			return Promise.reject(new Error('config'));
		}
		if (typeof AbortController !== 'undefined') {
			if (ctTabFetchAbort) {
				try {
					ctTabFetchAbort.abort();
				} catch (eAbort) {
					// ignore
				}
			}
			ctTabFetchAbort = new AbortController();
		}
		var fd = new FormData();
		fd.append('action', cfg.tabAction);
		fd.append('nonce', cfg.tabNonce);
		fd.append('tab', tab);
		if (tab === 'view-subscription' && opts.subscriptionId) {
			fd.append('subscription_id', String(opts.subscriptionId));
		}
		return fetch(cfg.ajaxUrl, {
			method: 'POST',
			body: fd,
			credentials: 'same-origin',
			signal: ctTabFetchAbort ? ctTabFetchAbort.signal : undefined,
		})
			.then(function (res) {
				if (!res.ok) {
					throw new Error('http');
				}
				return res.json();
			})
			.finally(function () {
				ctTabFetchAbort = null;
			});
	}

	function initAccountTabs() {
		var cfg = getCfg();
		var root = document.querySelector('[data-ct-account-root]');
		var panelsRoot = document.querySelector('[data-ct-account-tab-panels]');
		if (!cfg || !root || !panelsRoot) {
			return null;
		}

		var cache = {};

		var first = panelsRoot.querySelector('.ct-account-tab-content');
		if (first) {
			var ft = first.getAttribute('data-ct-tab');
			if (ft) {
				cache[ft] = first;
				syncTablistUI(root, ft);
			}
		}

		function getUrlForTab(tab) {
			return (cfg.endpointUrls && cfg.endpointUrls[tab]) || '';
		}

		function pushHistory(tab, opts) {
			opts = opts || {};
			var u = opts.historyUrl || getUrlForTab(tab);
			if (!u || typeof history === 'undefined' || !history.pushState) {
				return;
			}
			try {
				history.pushState(
					{
						ctAccountTab: tab,
						subscriptionId: opts.subscriptionId || null,
					},
					'',
					u
				);
			} catch (e) {
				// ignore
			}
		}

		function showTab(tab, opts) {
			opts = opts || {};
			var skipHistory = !!opts.skipHistory;
			var cacheKey = getTabCacheKey(tab, opts);

			if (!isAjaxTab(tab)) {
				if (!skipHistory) {
					var u = opts.historyUrl || getUrlForTab(tab);
					if (u) {
						window.location.href = u;
					}
				}
				return;
			}

			var existing = cache[cacheKey];
			if (existing) {
				deactivatePanels(panelsRoot);
				window.requestAnimationFrame(function () {
					activatePanel(existing);
					initProfileFormPanel(existing);
				});
				syncTablistUI(root, tab);
				if (!skipHistory) {
					pushHistory(tab, opts);
				}
				return;
			}

			setPanelsLoading(panelsRoot, true);
			fetchTabHtml(tab, opts)
				.then(function (json) {
					if (!json || !json.success || !json.data || typeof json.data.html !== 'string') {
						throw new Error('bad response');
					}
					var wrap = document.createElement('div');
					wrap.className = 'ct-account-tab-content';
					wrap.setAttribute('data-ct-tab', tab);
					wrap.setAttribute('data-ct-loaded', '1');
					wrap.id = 'ct-tab-panel-' + cacheKey.replace(/[^a-z0-9_-]/gi, '-');
					wrap.setAttribute('role', 'tabpanel');
					if (tab === 'view-subscription' && opts.subscriptionId) {
						wrap.setAttribute('data-ct-subscription-id', String(opts.subscriptionId));
					}
					wrap.innerHTML = json.data.html;

					deactivatePanels(panelsRoot);
					panelsRoot.appendChild(wrap);
					cache[cacheKey] = wrap;
					window.requestAnimationFrame(function () {
						activatePanel(wrap);
						initProfileFormPanel(wrap);
					});
					syncTablistUI(root, tab);
					if (!skipHistory) {
						pushHistory(tab, opts);
					}
				})
				.catch(function (err) {
					if (err && err.name === 'AbortError') {
						return;
					}
					window.alert(cfg.tabLoadError || 'Tab load failed.');
				})
				.finally(function () {
					setPanelsLoading(panelsRoot, false);
				});
		}

		root.addEventListener(
			'click',
			function (ev) {
				var a = ev.target.closest('a[data-ct-tab]');
				if (!a || !root.contains(a)) {
					return;
				}
				var tab = a.getAttribute('data-ct-tab');
				if (!tab) {
					return;
				}
				if (ev.defaultPrevented) {
					return;
				}
				if (ev.button !== 0 || ev.ctrlKey || ev.metaKey || ev.shiftKey || ev.altKey) {
					return;
				}

				ev.preventDefault();
				closeAllDropdowns();

				if (isExternalTab(tab)) {
					if (a.href) {
						window.location.href = a.href;
					}
					return;
				}

				if (!isAjaxTab(tab)) {
					if (a.href) {
						window.location.href = a.href;
					}
					return;
				}

				var showOpts = {
					skipHistory: false,
					historyUrl: a.href || '',
				};
				if (tab === 'view-subscription') {
					showOpts.subscriptionId = a.getAttribute('data-ct-subscription-id') || '';
				}
				showTab(tab, showOpts);
			},
			false
		);

		var sel = root.querySelector('[data-ct-account-tab-select]');
		if (sel) {
			sel.addEventListener('change', function () {
				var v = sel.value;
				if (!v) {
					return;
				}
				showTab(v, { skipHistory: false });
			});
		}

		window.addEventListener('popstate', function () {
			var t = detectTabFromLocation(cfg);
			if (t === null) {
				window.location.reload();
				return;
			}
			showTab(t, { skipHistory: true });
		});

		return { showTab: showTab, syncTablistUI: syncTablistUI, root: root, panelsRoot: panelsRoot, cache: cache };
	}

	function getProfileImageHeader(el) {
		if (!el) {
			return null;
		}
		return el.closest('[data-ct-profile-header]');
	}

	function setProfileImageStatus(message, isError, header) {
		var el = null;
		if (header) {
			el = header.querySelector('[data-ct-profile-image-status]');
		}
		if (!el) {
			el = document.querySelector('[data-ct-profile-image-status]');
		}
		if (!el) {
			return;
		}
		el.textContent = message || '';
		el.hidden = !message;
		el.classList.toggle('ct-account-profile-image__status--error', !!isError);
	}

	function profileImageCacheBustUrl(url) {
		if (!url) {
			return '';
		}
		var sep = url.indexOf('?') === -1 ? '?' : '&';
		return url + sep + 't=' + String(Date.now());
	}

	function updateHeaderAvatar(url) {
		if (!url) {
			return;
		}
		var avatar = document.querySelector('[data-ct-header-avatar]');
		if (!avatar) {
			return;
		}
		var bustUrl = profileImageCacheBustUrl(url);
		var alt =
			avatar.getAttribute('aria-label') ||
			(document.querySelector('[data-ct-profile-image-wrap]') &&
				document
					.querySelector('[data-ct-profile-image-wrap]')
					.getAttribute('data-ct-profile-img-alt')) ||
			'';
		var existingImg = avatar.querySelector('[data-ct-header-avatar-img]');
		if (existingImg) {
			existingImg.src = bustUrl;
			if (alt) {
				existingImg.alt = alt;
			}
			return;
		}
		var initial = avatar.querySelector('.ct-account-header__avatar-initial');
		if (initial) {
			initial.remove();
		}
		avatar.removeAttribute('aria-hidden');
		if (alt) {
			avatar.setAttribute('role', 'img');
			avatar.setAttribute('aria-label', alt);
		}
		var img = document.createElement('img');
		img.className = 'ct-account-header__avatar-img';
		img.src = bustUrl;
		img.alt = alt;
		img.width = 40;
		img.height = 40;
		img.decoding = 'async';
		img.setAttribute('data-ct-header-avatar-img', '');
		avatar.appendChild(img);
	}

	function updateProfileImagePreview(url) {
		if (!url) {
			return;
		}
		var bustUrl = profileImageCacheBustUrl(url);
		var wraps = document.querySelectorAll('[data-ct-profile-image-wrap]');

		wraps.forEach(function (wrap) {
			var alt = wrap.getAttribute('data-ct-profile-img-alt') || '';
			while (wrap.firstChild) {
				wrap.removeChild(wrap.firstChild);
			}
			var img = document.createElement('img');
			img.src = bustUrl;
			img.alt = alt;
			img.className = 'ct-account-profiel__avatar-img';
			img.width = 140;
			img.height = 140;
			img.decoding = 'async';
			img.setAttribute('data-ct-profile-img', '');
			wrap.appendChild(img);
		});

		updateHeaderAvatar(url);
	}

	function bindProfileImageUpload() {
		document.addEventListener('click', function (ev) {
			var btn = ev.target.closest('[data-ct-profile-image-upload]');
			if (!btn) {
				return;
			}
			ev.preventDefault();
			var header = getProfileImageHeader(btn);
			if (!header) {
				return;
			}
			var fileInput = header.querySelector('[data-ct-profile-image-file]');
			if (fileInput) {
				fileInput.click();
			}
		});

		document.addEventListener('change', function (ev) {
			var input = ev.target.closest('[data-ct-profile-image-file]');
			if (!input || !input.files || !input.files[0]) {
				return;
			}

			var header = getProfileImageHeader(input);
			var cfg = getCfg();
			if (!cfg || !cfg.ajaxUrl || !cfg.profileImageNonce || !cfg.profileImageAction) {
				input.value = '';
				setProfileImageStatus(
					cfg && cfg.profileImageError ? cfg.profileImageError : 'Upload niet beschikbaar. Vernieuw de pagina.',
					true,
					header
				);
				return;
			}

			var file = input.files[0];
			var maxB = parseInt(cfg.profileImageMaxBytes, 10) || 2097152;
			if (file.size > maxB) {
				setProfileImageStatus(cfg.profileImageTooBig || 'File too large.', true, header);
				input.value = '';
				return;
			}

			var okTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
			if (okTypes.indexOf(file.type) === -1) {
				setProfileImageStatus(cfg.profileImageBadType || 'Invalid type.', true, header);
				input.value = '';
				return;
			}

			setProfileImageStatus(cfg.profileImageUploading || 'Uploaden…', false, header);

			var fd = new FormData();
			fd.append('action', cfg.profileImageAction);
			fd.append('nonce', cfg.profileImageNonce);
			fd.append('profile_image', file);

			fetch(cfg.ajaxUrl, {
				method: 'POST',
				body: fd,
				credentials: 'same-origin',
			})
				.then(function (res) {
					return res.json();
				})
				.then(function (json) {
					if (!json || !json.success || !json.data || !json.data.url) {
						var msg = cfg.profileImageError || 'Error.';
						if (json && json.data) {
							if (typeof json.data === 'string') {
								msg = json.data;
							} else if (json.data.message) {
								msg = json.data.message;
							}
						}
						setProfileImageStatus(msg, true, header);
						input.value = '';
						return;
					}
					updateProfileImagePreview(json.data.url);
					setProfileImageStatus(cfg.profileImageOk || 'OK', false, header);
					window.setTimeout(function () {
						setProfileImageStatus('', false, header);
					}, 4000);
					input.value = '';
				})
				.catch(function () {
					setProfileImageStatus(cfg.profileImageError || 'Error.', true, header);
					input.value = '';
				});
		});
	}

	var CT_PROFILE_COLLAPSED_KEY = 'ctma_profile_overview_form_collapsed';

	function setProfileFormCollapsed(section, collapsed) {
		if (!section) {
			return;
		}
		section.classList.toggle('ct-account-profiel--form-collapsed', !!collapsed);
		var panel = section.querySelector('[data-ct-profile-form-panel]');
		var btn = section.querySelector('[data-ct-profile-form-toggle]');
		if (panel) {
			if (collapsed) {
				panel.setAttribute('hidden', 'hidden');
			} else {
				panel.removeAttribute('hidden');
			}
		}
		var summary = section.querySelector('[data-ct-profile-summary]');
		if (summary) {
			if (collapsed) {
				summary.removeAttribute('hidden');
			} else {
				summary.setAttribute('hidden', 'hidden');
			}
		}
		if (btn) {
			btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
		}
	}

	function gatherProfileSections(root) {
		if (!root || root === document) {
			return Array.prototype.slice.call(document.querySelectorAll('.ct-account-profiel'));
		}
		if (root.nodeType === 1 && root.classList && root.classList.contains('ct-account-profiel')) {
			return [root];
		}
		if (root.nodeType === 1 && typeof root.querySelectorAll === 'function') {
			return Array.prototype.slice.call(root.querySelectorAll('.ct-account-profiel'));
		}
		return [];
	}

	function initProfileFormPanel(root) {
		var docLevel = !root || root === document;
		var sections = gatherProfileSections(root || document);
		if (!sections.length) {
			return;
		}

		var urlCollapsed = false;
		if (docLevel && typeof URLSearchParams !== 'undefined' && typeof window.location !== 'undefined') {
			try {
				var params = new URLSearchParams(window.location.search);
				if (params.get('ct_profile_saved') === '1') {
					urlCollapsed = true;
					try {
						window.localStorage.setItem(CT_PROFILE_COLLAPSED_KEY, '1');
					} catch (eLs) {
						// ignore
					}
					params.delete('ct_profile_saved');
					var newQ = params.toString();
					var path = window.location.pathname + (newQ ? '?' + newQ : '') + (window.location.hash || '');
					if (typeof history !== 'undefined' && history.replaceState) {
						history.replaceState(null, '', path);
					}
				}
			} catch (eUrl) {
				// ignore
			}
		}

		var storeCollapsed = false;
		try {
			storeCollapsed = window.localStorage.getItem(CT_PROFILE_COLLAPSED_KEY) === '1';
		} catch (eRead) {
			storeCollapsed = false;
		}

		sections.forEach(function (section) {
			var collapse =
				urlCollapsed ||
				storeCollapsed ||
				section.classList.contains('ct-account-profiel--form-collapsed');
			setProfileFormCollapsed(section, !!collapse);
		});
	}

	var ctProfileToggleBound = false;
	function bindProfileFormToggle() {
		if (ctProfileToggleBound) {
			return;
		}
		ctProfileToggleBound = true;
		document.addEventListener(
			'click',
			function (ev) {
				var btn = ev.target.closest('[data-ct-profile-form-toggle]');
				if (!btn) {
					return;
				}
				var section = btn.closest('.ct-account-profiel');
				if (!section) {
					return;
				}
				ev.preventDefault();
				var isCollapsed = section.classList.contains('ct-account-profiel--form-collapsed');
				setProfileFormCollapsed(section, !isCollapsed);
				if (isCollapsed) {
					try {
						window.localStorage.removeItem(CT_PROFILE_COLLAPSED_KEY);
					} catch (eOpen) {
						// ignore
					}
					var dnOpen = document.getElementById('ct_profile_display_name');
					if (dnOpen && typeof dnOpen.focus === 'function') {
						window.requestAnimationFrame(function () {
							try {
								dnOpen.focus();
							} catch (eF) {
								// ignore
							}
						});
					}
				}
			},
			false
		);
	}

	function bindDelegatedForms() {
		document.addEventListener(
			'submit',
			function (ev) {
				var pwForm = ev.target.closest('.ct-account-password-change-form');
				if (pwForm) {
					var n = document.getElementById('ct_new_password');
					var c = document.getElementById('ct_confirm_password');
					var err = document.getElementById('ct-password-client-error');
					if (!n || !c) {
						return;
					}
					if (n.value !== c.value) {
						ev.preventDefault();
						var cfg = getCfg();
						if (err) {
							err.textContent =
								(cfg && cfg.passwordMismatch) ||
								'Het nieuwe wachtwoord en de bevestiging komen niet overeen.';
							err.hidden = false;
						}
					} else if (err) {
						err.hidden = true;
						err.textContent = '';
					}
					return;
				}

				var stForm = ev.target.closest('.ct-account-settings-form');
				if (stForm) {
					var cfg = getCfg();
					var em = document.getElementById('ct_settings_email');
					var ph = document.getElementById('ct_settings_phone');
					var err = document.getElementById('ct-settings-client-error');
					var msgs = [];

					if (em) {
						if (!em.value.trim()) {
							msgs.push((cfg && cfg.invalidEmail) || 'Voer een geldig e-mailadres in.');
						} else if (typeof em.checkValidity === 'function' && !em.checkValidity()) {
							msgs.push((cfg && cfg.invalidEmail) || 'Voer een geldig e-mailadres in.');
						}
					}

					if (ph && ph.value.trim()) {
						var raw = ph.value.trim();
						var digits = raw.replace(/\D/g, '');
						if (!/^[\d\s\+\-\(\)]+$/.test(raw) || digits.length < 8 || digits.length > 15) {
							msgs.push((cfg && cfg.invalidPhone) || 'Voer een geldig telefoonnummer in.');
						}
					}

					if (msgs.length) {
						ev.preventDefault();
						if (err) {
							err.textContent = msgs[0];
							err.hidden = false;
						}
					} else if (err) {
						err.hidden = true;
						err.textContent = '';
					}
					return;
				}

				var pfForm = ev.target.closest('.ct-account-profile-edit-form');
				if (pfForm) {
					var cfg = getCfg();
					var dn = document.getElementById('ct_profile_display_name');
					var em = document.getElementById('ct_profile_email');
					var ph = document.getElementById('ct_profile_phone');
					var err = document.getElementById('ct-profile-edit-client-error');
					var msgs = [];

					if (dn && !dn.value.trim()) {
						msgs.push(
							(cfg && cfg.profileDisplayNameRequired) || 'Please enter a display name.'
						);
					}

					if (em) {
						if (!em.value.trim()) {
							msgs.push((cfg && cfg.invalidEmail) || 'Voer een geldig e-mailadres in.');
						} else if (typeof em.checkValidity === 'function' && !em.checkValidity()) {
							msgs.push((cfg && cfg.invalidEmail) || 'Voer een geldig e-mailadres in.');
						}
					}

					if (ph && ph.value.trim()) {
						var raw = ph.value.trim();
						var digits = raw.replace(/\D/g, '');
						if (!/^[\d\s\+\-\(\)]+$/.test(raw) || digits.length < 8 || digits.length > 15) {
							msgs.push((cfg && cfg.invalidPhone) || 'Voer een geldig telefoonnummer in.');
						}
					}

					if (msgs.length) {
						ev.preventDefault();
						if (err) {
							err.textContent = msgs[0];
							err.hidden = false;
						}
					} else if (err) {
						err.hidden = true;
						err.textContent = '';
					}
				}
			},
			false
		);
	}

	function initSubscriptionCancelModal() {
		var cfg = getCfg();
		if (!cfg || !cfg.ajaxUrl) {
			return;
		}

		var modal = null;
		var lastFocus = null;
		var pendingId = null;

		function getModal() {
			if (modal) {
				return modal;
			}
			var el = document.createElement('div');
			el.id = 'ct-account-cancel-subscription-modal';
			el.className = 'ct-account-cancel-subscription-modal';
			el.setAttribute('role', 'dialog');
			el.setAttribute('aria-modal', 'true');
			el.setAttribute('aria-labelledby', 'ct-account-cancel-subscription-title');
			el.setAttribute('hidden', 'hidden');
			el.innerHTML =
				'<div class="ct-account-cancel-subscription-modal__backdrop" data-ct-subscription-modal-dismiss="1"></div>' +
				'<div class="ct-account-cancel-subscription-modal__dialog">' +
				'<h2 class="ct-account-cancel-subscription-modal__title" id="ct-account-cancel-subscription-title"></h2>' +
				'<p class="ct-account-cancel-subscription-modal__message" id="ct-account-cancel-subscription-message"></p>' +
				'<div class="ct-account-cancel-subscription-modal__actions">' +
				'<button type="button" class="ct-account-cancel-subscription-confirm" data-ct-subscription-modal-confirm></button>' +
				'<button type="button" class="ct-account-cancel-btn" data-ct-subscription-modal-dismiss="1"></button>' +
				'</div>' +
				'<p class="ct-account-cancel-subscription-modal__status" data-ct-subscription-modal-status role="status" aria-live="polite" hidden></p>' +
				'</div>';
			document.body.appendChild(el);
			modal = el;

			var titleEl = el.querySelector('#ct-account-cancel-subscription-title');
			var msgEl = el.querySelector('#ct-account-cancel-subscription-message');
			var confirmBtn = el.querySelector('[data-ct-subscription-modal-confirm]');
			var dismissBtns = el.querySelectorAll('[data-ct-subscription-modal-dismiss]');
			var statusEl = el.querySelector('[data-ct-subscription-modal-status]');

			if (titleEl) {
				titleEl.textContent =
					(cfg && cfg.subscriptionCancelModalTitle) || 'Cancel subscription';
			}
			if (msgEl) {
				msgEl.textContent =
					(cfg && cfg.subscriptionCancelModalMessage) ||
					'Are you sure you want to cancel your subscription? This action cannot be undone.';
			}
			if (confirmBtn) {
				confirmBtn.textContent =
					(cfg && cfg.subscriptionCancelModalConfirm) || 'Confirm';
			}
			dismissBtns.forEach(function (b) {
				b.textContent = (cfg && cfg.subscriptionCancelModalDismiss) || 'Cancel';
			});

			function closeModal() {
				if (!modal || modal.hasAttribute('hidden')) {
					return;
				}
				modal.setAttribute('hidden', 'hidden');
				document.body.classList.remove('ct-account-cancel-modal-open');
				pendingId = null;
				if (statusEl) {
					statusEl.hidden = true;
					statusEl.textContent = '';
					statusEl.classList.remove('is-error');
				}
				if (confirmBtn) {
					confirmBtn.disabled = false;
				}
				dismissBtns.forEach(function (b) {
					b.disabled = false;
				});
				if (lastFocus && typeof lastFocus.focus === 'function') {
					try {
						lastFocus.focus();
					} catch (e) {
						// ignore
					}
				}
				lastFocus = null;
			}

			dismissBtns.forEach(function (b) {
				b.addEventListener('click', function (ev) {
					ev.preventDefault();
					closeModal();
				});
			});

			if (confirmBtn) {
				confirmBtn.addEventListener('click', function () {
					if (!pendingId) {
						return;
					}
					var action = cfg.cancelSubscriptionAction;
					var nonce = cfg.cancelSubscriptionNonce;
					if (!action || !nonce) {
						if (statusEl) {
							statusEl.textContent =
								(cfg && cfg.subscriptionCancelError) || 'Cancellation failed.';
							statusEl.hidden = false;
							statusEl.classList.add('is-error');
						}
						return;
					}
					confirmBtn.disabled = true;
					dismissBtns.forEach(function (b) {
						b.disabled = true;
					});
					if (statusEl) {
						statusEl.hidden = true;
						statusEl.classList.remove('is-error');
					}

					var fd = new FormData();
					fd.append('action', action);
					fd.append('nonce', nonce);
					fd.append('subscription_id', String(pendingId));

					fetch(cfg.ajaxUrl, {
						method: 'POST',
						body: fd,
						credentials: 'same-origin',
					})
						.then(function (res) {
							return res.json();
						})
						.then(function (json) {
							if (json && json.success && json.data) {
								var okMsg =
									(typeof json.data.message === 'string' && json.data.message) ||
									(cfg && cfg.subscriptionCancelSuccess) ||
									'Your subscription has been successfully cancelled.';
								if (json.data.email_warning) {
									okMsg =
										okMsg +
										' ' +
										(typeof json.data.email_warning === 'string'
											? json.data.email_warning
											: '');
								}
								if (statusEl) {
									statusEl.textContent = okMsg;
									statusEl.hidden = false;
									statusEl.classList.remove('is-error');
								}
								window.setTimeout(function () {
									window.location.reload();
								}, 1800);
								return;
							}
							var errMsg =
								(json && json.data && json.data.message) ||
								(cfg && cfg.subscriptionCancelError) ||
								'Cancellation failed.';
							if (statusEl) {
								statusEl.textContent = errMsg;
								statusEl.hidden = false;
								statusEl.classList.add('is-error');
							}
							confirmBtn.disabled = false;
							dismissBtns.forEach(function (b) {
								b.disabled = false;
							});
						})
						.catch(function () {
							if (statusEl) {
								statusEl.textContent =
									(cfg && cfg.subscriptionCancelError) || 'Cancellation failed.';
								statusEl.hidden = false;
								statusEl.classList.add('is-error');
							}
							confirmBtn.disabled = false;
							dismissBtns.forEach(function (b) {
								b.disabled = false;
							});
						});
				});
			}

			document.addEventListener('keydown', function (ev) {
				if (ev.key !== 'Escape') {
					return;
				}
				if (!modal || modal.hasAttribute('hidden')) {
					return;
				}
				closeModal();
			});

			return el;
		}

		function openModal(subscriptionId) {
			var m = getModal();
			pendingId = subscriptionId;
			lastFocus = document.activeElement;
			document.querySelectorAll('[data-ct-account-dropdown].is-open').forEach(closeDropdown);
			m.removeAttribute('hidden');
			document.body.classList.add('ct-account-cancel-modal-open');
			var statusEl = m.querySelector('[data-ct-subscription-modal-status]');
			if (statusEl) {
				statusEl.hidden = true;
				statusEl.textContent = '';
				statusEl.classList.remove('is-error');
			}
			var confirmBtn = m.querySelector('[data-ct-subscription-modal-confirm]');
			var dismissBtns = m.querySelectorAll('[data-ct-subscription-modal-dismiss]');
			if (confirmBtn) {
				confirmBtn.disabled = false;
			}
			dismissBtns.forEach(function (b) {
				b.disabled = false;
			});
			if (confirmBtn) {
				window.requestAnimationFrame(function () {
					confirmBtn.focus();
				});
			}
		}

		document.addEventListener(
			'click',
			function (ev) {
				var trigger = ev.target.closest('[data-ct-subscription-cancel-trigger]');
				if (!trigger) {
					return;
				}
				ev.preventDefault();
				var sid = trigger.getAttribute('data-subscription-id');
				if (!sid) {
					return;
				}
				openModal(sid);
			},
			false
		);
	}

	function isMobileAccountNav() {
		return typeof window.matchMedia === 'function' && window.matchMedia('(max-width: 768px)').matches;
	}

	function initMobileAccountNav() {
		var root = document.querySelector('[data-ct-account-root]');
		if (!root) {
			return;
		}
		var sidebar = root.querySelector('.ct-account-sidebar');
		var toggle = root.querySelector('[data-ct-account-menu-toggle]');
		if (!sidebar || !toggle) {
			return;
		}

		function setMenuOpen(on) {
			if (!isMobileAccountNav()) {
				sidebar.classList.remove('is-menu-open');
				toggle.setAttribute('aria-expanded', 'false');
				document.body.classList.remove('ct-account-mobile-nav-open');
				return;
			}
			sidebar.classList.toggle('is-menu-open', !!on);
			toggle.setAttribute('aria-expanded', on ? 'true' : 'false');
			document.body.classList.toggle('ct-account-mobile-nav-open', !!on);
		}

		toggle.addEventListener('click', function (ev) {
			ev.preventDefault();
			if (!isMobileAccountNav()) {
				return;
			}
			setMenuOpen(!sidebar.classList.contains('is-menu-open'));
		});

		root.addEventListener('click', function (ev) {
			if (!isMobileAccountNav() || !sidebar.classList.contains('is-menu-open')) {
				return;
			}
			var navLink = ev.target.closest('.ct-account-sidebar__nav a');
			if (navLink && sidebar.contains(navLink)) {
				setMenuOpen(false);
			}
		});

		var tabSelect = root.querySelector('[data-ct-account-tab-select]');
		if (tabSelect) {
			tabSelect.addEventListener('change', function () {
				setMenuOpen(false);
			});
		}

		window.addEventListener('resize', function () {
			if (!isMobileAccountNav()) {
				setMenuOpen(false);
			}
		});

		document.addEventListener(
			'click',
			function (ev) {
				if (!isMobileAccountNav() || !sidebar.classList.contains('is-menu-open')) {
					return;
				}
				if (sidebar.contains(ev.target)) {
					return;
				}
				setMenuOpen(false);
			},
			true
		);

		document.addEventListener('keydown', function (ev) {
			if (ev.key !== 'Escape') {
				return;
			}
			setMenuOpen(false);
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		bindDelegatedForms();
		bindProfileImageUpload();
		bindProfileFormToggle();
		initProfileFormPanel(document);
		initSubscriptionCancelModal();
		initMobileAccountNav();

		document.addEventListener('click', function (ev) {
			var toggle = ev.target.closest('[data-ct-account-dropdown-toggle]');
			if (toggle) {
				ev.preventDefault();
				var droproot = toggle.closest('[data-ct-account-dropdown]');
				var menu = droproot ? droproot.querySelector('[data-ct-account-dropdown-menu]') : null;
				if (!droproot || !menu) {
					return;
				}
				var willOpen = !droproot.classList.contains('is-open');
				closeAllDropdowns(droproot);
				if (willOpen) {
					droproot.classList.add('is-open');
					toggle.setAttribute('aria-expanded', 'true');
					menu.removeAttribute('hidden');
				} else {
					closeDropdown(droproot);
				}
				return;
			}

			document.querySelectorAll('[data-ct-account-dropdown].is-open').forEach(function (root) {
				if (!root.contains(ev.target)) {
					closeDropdown(root);
				}
			});
		});

		document.addEventListener('keydown', function (ev) {
			if (ev.key !== 'Escape') {
				return;
			}
			document.querySelectorAll('[data-ct-account-dropdown].is-open').forEach(closeDropdown);
		});

		var tabsApi = initAccountTabs();
		if (tabsApi && typeof tabsApi.showTab === 'function') {
			var cfg = getCfg();
			var detectedTab = detectTabFromLocation(cfg);
			if (detectedTab === null) {
				return;
			}
			var panelsRoot = tabsApi.panelsRoot;
			var firstPanel = panelsRoot ? panelsRoot.querySelector('.ct-account-tab-content') : null;
			var renderedTab = firstPanel ? firstPanel.getAttribute('data-ct-tab') : '';
			if (detectedTab && detectedTab !== renderedTab) {
				tabsApi.showTab(detectedTab, { skipHistory: true });
			}
		}
	});
})();
