/**
 * @file plugins/themes/default/js/main.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Handle JavaScript functionality unique to this theme.
 */
(function($) {

	// Initialize dropdown navigation menus on large screens
	// See bootstrap dropdowns: https://getbootstrap.com/docs/4.0/components/dropdowns/
	if (typeof $.fn.dropdown !== 'undefined') {
		var $nav = $('#navigationPrimary, #navigationUser'),
		$submenus = $('ul', $nav);
		function toggleDropdowns() {
			if (window.innerWidth > 992) {
				$submenus.each(function(i) {
					var id = 'pkpDropdown' + i;
					$(this)
						.addClass('dropdown-menu')
						.attr('aria-labelledby', id);
					$(this).siblings('a')
						.attr('data-toggle', 'dropdown')
						.attr('aria-haspopup', true)
						.attr('aria-expanded', false)
						.attr('id', id)
						.attr('href', '#');
				});
				$('[data-toggle="dropdown"]').dropdown();

			} else {
				$('[data-toggle="dropdown"]').dropdown('dispose');
				$submenus.each(function(i) {
					$(this)
						.removeClass('dropdown-menu')
						.removeAttr('aria-labelledby');
					$(this).siblings('a')
						.removeAttr('data-toggle')
						.removeAttr('aria-haspopup')
						.removeAttr('aria-expanded',)
						.removeAttr('id')
						.attr('href', '#');
				});
			}
		}
		window.onresize = toggleDropdowns;
		$().ready(function() {
			toggleDropdowns();
		});
	}

	// Toggle nav menu on small screens
	$('.pkp_site_nav_toggle').click(function(e) {
  		$('.pkp_site_nav_menu').toggleClass('pkp_site_nav_menu--isOpen');
  		$('.pkp_site_nav_toggle').toggleClass('pkp_site_nav_toggle--transform');
	});

	// Modify the Chart.js display options used by UsageStats plugin
	document.addEventListener('usageStatsChartOptions.pkp', function(e) {
		e.chartOptions.elements.line.backgroundColor = 'rgba(0, 122, 178, 0.6)';
		e.chartOptions.elements.bar.backgroundColor = 'rgba(0, 122, 178, 0.6)';
	});

	// Toggle display of consent checkboxes in site-wide registration
	var $contextOptinGroup = $('#contextOptinGroup');
	if ($contextOptinGroup.length) {
		var $roles = $contextOptinGroup.find('.roles :checkbox');
		$roles.change(function() {
			var $thisRoles = $(this).closest('.roles');
			if ($thisRoles.find(':checked').length) {
				$thisRoles.siblings('.context_privacy').addClass('context_privacy_visible');
			} else {
				$thisRoles.siblings('.context_privacy').removeClass('context_privacy_visible');
			}
		});
	}

	// Show or hide the reviewer interests field on the registration form
	// when a user has opted to register as a reviewer.
	function reviewerInterestsToggle() {
		var is_checked = false;
		$('#reviewerOptinGroup').find('input').each(function() {
			if ($(this).is(':checked')) {
				is_checked = true;
				return false;
			}
		});
		if (is_checked) {
			$('#reviewerInterests').addClass('is_visible');
		} else {
			$('#reviewerInterests').removeClass('is_visible');
		}
	}

	reviewerInterestsToggle();
	$('#reviewerOptinGroup input').on('click', reviewerInterestsToggle);

	var swiper = new Swiper('.swiper', {
		ally: {
			prevSlideMessage: pkpDefaultThemeI18N.prevSlide,
			nextSlideMessage: pkpDefaultThemeI18N.nextSlide,
		},
		autoHeight: true,
		navigation: {
			nextEl: '.swiper-button-next',
			prevEl: '.swiper-button-prev',
		},
		pagination: {
			el: '.swiper-pagination',
			type: 'bullets',
		}
	});

})(jQuery);

/**
 * Create language buttons to show multilingual metadata
 * [data-pkp-locales]: Publication's locales in order
 * [data-pkp-switcher-text]: Texts for the switchers to control
 * [data-pkp-switcher-target]: Switchers' containers
 */
(() => {
	function createButtonSwitcher(textsObj, originalLocaleOrder, metadataFieldName, selectedLocale) {
		// Get all locales for the switcher from the texts
		const textsElsLocales = textsObj.els.reduce((locales, textEls) => {
			textEls.forEach((el) => {
				locales[el.getAttribute('data-pkp-locale')] = el.getAttribute('data-pkp-locale-name');
			});
			return locales;
		}, {});

		// Create containers
		const spanContainer = document.createElement('span');
		[
			['class', `switcher-buttons-${metadataFieldName}`],
		].forEach((attr) => spanContainer.setAttribute(...attr));

		const spanButtons = document.createElement('span');
		const spanButtonsId = `switcher-buttons-${metadataFieldName}`;
		[
			['id', spanButtonsId],
		].forEach((attr) => spanButtons.setAttribute(...attr));

		// Create, sort to alphabetical order, and append buttons
		originalLocaleOrder
			.map((elLocale) => {
				if (!textsElsLocales[elLocale]) {
					return null;
				}
				if (!selectedLocale.value) {
					selectedLocale.value = elLocale;
				}

				const isSelectedLocale = elLocale === selectedLocale.value;
				const button = document.createElement('button');
				[
					['data-pkp-locale', elLocale],
					['data-pkp-switcher-button', metadataFieldName],
					['class', `pkpBadge pkpBadge--button collapse-button${isSelectedLocale ? ' selected-button show-button' : ''}`],
					['type', 'button'],
					['aria-controls', isSelectedLocale ? spanButtonsId : textsObj.ids.join(' ')],
					...isSelectedLocale
						? [
							['aria-expanded', false],
						]
						: [],
				].forEach((attr) => button.setAttribute(...attr));
				button.textContent = textsElsLocales[elLocale];

				return button;
			})
			.filter((btn) => btn)
			.sort((a, b) => a.getAttribute('data-pkp-locale').localeCompare(b.getAttribute('data-pkp-locale')))
			.forEach((btn) => spanButtons.appendChild(btn));

		// If only one button, set it disabled
		if (spanButtons.children.length === 1) {
			spanButtons.children[0].disabled = true;
		}

		spanContainer.appendChild(spanButtons);

		return spanContainer;
	}

	/**
	 * Show or hide switcher's target texts
	 * If selected locale doesn't match any, all texts are hidden
	 */
	function showText(selectedLocale, textsEls) {
		textsEls.forEach((textsEl) => {
			textsEl.forEach((textEl) => {
				const elLocale = textEl.getAttribute('data-pkp-locale');
				if (elLocale === selectedLocale.value) {
					textEl.classList.add('show-text');
				} else {
					textEl.classList.remove('show-text');
				}
			});
		});
	}

	/**
	 * Change/update buttons' aria-attributes
	 */
	function switchButtonAria(btnTarget, buttons) {
		let btnTargetOldAriaControls = btnTarget.getAttribute('aria-controls');
		let btnPrevSelectedLangAriaControls = null;
		buttons.forEach((btn) => {
			// Previously selected langauge button 
			if (btn.getAttribute('aria-expanded')) {
				btnPrevSelectedLangAriaControls = btn.getAttribute('aria-controls');
				btn.removeAttribute('aria-expanded');
				btn.setAttribute('aria-controls', btnTargetOldAriaControls);
			}
		});
		btnTarget.setAttribute('aria-expanded', true);
		btnTarget.setAttribute('aria-controls', btnPrevSelectedLangAriaControls);
	}

	function setButtonSwitcher(textsObj, switcherTargetEl, metadataFieldName, originalLocaleOrder) {
		// Currently selected language for buttons and texts
		const selectedLocale = {value: null};
		const buttonSwitcherEl = createButtonSwitcher(textsObj, originalLocaleOrder, metadataFieldName, selectedLocale);

		// Sync buttons and shown texts
		showText(selectedLocale, textsObj.els);

		const buttons = buttonSwitcherEl.querySelectorAll('button');

		// Add listeners if more than one button
		if (buttons.length > 1) {
			// Selected language shows/hides other switcher buttons, and otherwise switches language and shows text
			buttonSwitcherEl.addEventListener('click', (evt) => {
				const btnTarget = evt.target;
				if (btnTarget.type === 'button') {
					if (btnTarget.getAttribute('data-pkp-locale') === selectedLocale.value) {
						buttons.forEach((btn) => {
							if (btn.getAttribute('data-pkp-locale') !== selectedLocale.value) {
								btn.classList.toggle('show-button');
							}
						});
						btnTarget.setAttribute('aria-expanded', true);
					} else {
						selectedLocale.value = btnTarget.getAttribute('data-pkp-locale');
						switchButtonAria(btnTarget, buttons);
						buttons.forEach((btn) => {
							if (btn.getAttribute('data-pkp-locale') === selectedLocale.value) {
								btn.classList.add('selected-button');
							} else {
								btn.classList.remove('selected-button');
							}
						});
						showText(selectedLocale, textsObj.els);
					}
				}
			});
			// Hide switcher (except selected language) buttons when it loses focus
			buttonSwitcherEl.addEventListener('focusout', (evt) => {
				if (!evt.relatedTarget || evt.relatedTarget.getAttribute('data-pkp-switcher-button') !== metadataFieldName) {
					buttons.forEach((btn) => {
						if (btn.getAttribute('data-pkp-locale') !== selectedLocale.value) {
							btn.classList.remove('show-button');
						} else {
							btn.setAttribute('aria-expanded', false);
						}
					});
				}
			});
		}

		// Append and show switcher
		switcherTargetEl.append(buttonSwitcherEl);
		switcherTargetEl.classList.remove('collapse-switcher');
	}

	/**
	 * Get all multilingual texts and ids for the switchers
	 */
	function getSwitcherTexts() {
		const textsObj = {};
		document.querySelectorAll('[data-pkp-switcher-text]').forEach((textsEl) => {
			const key = textsEl.getAttribute('data-pkp-switcher-text');
			if (!textsObj[key]) {
				textsObj[key] = {ids: [], els: []};
			}
			textsObj[key].ids.push(textsEl.id);
			textsObj[key].els.push([...textsEl.querySelectorAll('[data-pkp-locale]')]);
		});
		return textsObj;
	}

	(() => {
		const originalLocaleOrder = document.querySelector('[data-pkp-locales]')?.getAttribute('data-pkp-locales').split(',');
		const switcherTargetEls = document.querySelectorAll('[data-pkp-switcher-target]');
		if (!originalLocaleOrder || !switcherTargetEls.length) {
			return;
		}
		const switcherTextsObj = getSwitcherTexts();
		// Get target elements for switchers and create them
		switcherTargetEls.forEach((switcherTargetEl) => {
			const metadataFieldName = switcherTargetEl.getAttribute('data-pkp-switcher-target');
			if (switcherTextsObj[metadataFieldName]) {
				setButtonSwitcher(switcherTextsObj[metadataFieldName], switcherTargetEl, metadataFieldName, originalLocaleOrder);
			}
		});
	})();
})();
