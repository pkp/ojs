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
 * [data-pkp-switcher-data]: Publication data for the switchers to control
 * [data-pkp-switcher]: Switchers' containers
 */
(() => {
	function createSwitcher(listbox, data, localeOrder, localeNames, accessibility) {
		// Get all locales for the switcher from the data
		const locales = Object.keys(Object.assign({}, ...Object.values(data)));
		// The initially selected locale
		let selectedLocale = null;
		// Create and sort to alphabetical order
		const buttons = localeOrder
			.map((locale) => {
				if (locales.indexOf(locale) === -1) {
					return null;
				}
				if (!selectedLocale) {
					selectedLocale = locale;
				}

				const isSelectedLocale = locale === selectedLocale;
				const button = document.createElement('button');

				button.type = 'button';
				button.classList.add('pkpBadge', 'pkpBadge--button');
				button.value = locale;
				// For safari losing button focus; Tabindex maintains it
				button.tabIndex = '0';
				if (!isSelectedLocale) {
					button.classList.add('pkp_screen_reader');
				}

				// Text content
				/// SR
				const srText = document.createElement('span');
				srText.classList.add('pkp_screen_reader');
				srText.textContent = accessibility.localeNames[locale];
				button.appendChild(srText);
				// Visual
				const text = document.createElement('span');
				text.ariaHidden = 'true';
				text.textContent = localeNames[locale];
				button.appendChild(text);

				return button;
			})
			.filter((btn) => btn)
			.sort((a, b) => a.value.localeCompare(b.value));

		// If only one button, set it disabled
		if (buttons.length === 1) {
			buttons[0].ariaDisabled = 'true';
		}

		buttons.forEach((btn) => {
			const option = document.createElement('li');
			option.role = 'option';
			option.ariaSelected = `${btn.value === selectedLocale}`;
			option.appendChild(btn);
			// Listbox: Ul element
			listbox.appendChild(option);
		});

		return buttons;
	}

	/**
	 * Sync data in elements to match the selected locale 
	 */
	function syncDataElContents(locale, propsData, accessibility) {
		for (prop in propsData.data) {
			propsData.dataEls[prop].lang = accessibility.langAttrs[locale];
			propsData.dataEls[prop].innerHTML = propsData.data[prop][locale] ?? '';
		}
	}

	/**
	 * Toggle 'visually hidden' of the buttons; Current selected is always visual
	 * pkp_screen_reader: button visibility hidden
	 */
	function setVisibility(buttons, currentSelected, visible) {
		buttons.forEach((btn) => {
			if (visible) {
				btn.classList.remove('pkp_screen_reader');
			} else if (btn !== currentSelected.btn) {
				btn.classList.add('pkp_screen_reader');
			}
		});
	}

	function setSwitcher(propsData, switcherContainer, localeOrder, localeNames, accessibility) {
		// Create buttons and append them to the switcher container
		const listbox = switcherContainer.querySelector('[role="listbox"]');
		const buttons = createSwitcher(listbox, propsData.data, localeOrder, localeNames, accessibility);
		const currentSelected = {btn: switcherContainer.querySelector('.pkpBadge--button:not(.pkp_screen_reader')};

		// Sync contents in data elements to match the selected locale (currentSelected.btn.value)
		syncDataElContents(currentSelected.btn.value, propsData, accessibility);

		// Do not add listeners if just one button, it is disabled
		if (buttons.length < 2) {
			return;
		}

		const isButtonsHidden = () => buttons.some(b => b.classList.contains('pkp_screen_reader'));

		// New button switches language and syncs data contents
		switcherContainer.addEventListener('click', (evt) => {
			// Choices are li > button > span
			const newSelectedBtn = evt.target.classList.contains('pkpBadge--button')
				? evt.target
				: (evt.target.querySelector('.pkpBadge--button') ?? evt.target.closest('.pkpBadge--button'));
			if (buttons.some(b => b === newSelectedBtn)) {
				// Set visibility
				setVisibility(buttons, currentSelected, true);
				if (newSelectedBtn !== currentSelected.btn) {
					// Sync contents
					syncDataElContents(newSelectedBtn.value, propsData, accessibility);
					// Listbox option: Aria
					currentSelected.btn.parentElement.ariaSelected = 'false';
					newSelectedBtn.parentElement.ariaSelected = 'true';
					// Update current button
					currentSelected.btn = newSelectedBtn;
				}
				// Reset focus to current selected
				currentSelected.btn.focus();
			}
		});

		// Visually hide buttons when focus out
		switcherContainer.addEventListener('focusout', (evt) => {
			if (evt.target !== evt.currentTarget && evt.relatedTarget?.closest('[data-pkp-switcher]') !== switcherContainer) {
				setVisibility(buttons, currentSelected, false);
			}
		});

		// For tabbed browsing: Show all visually hidden buttons when one of the buttons receives focus
		switcherContainer.addEventListener('focusin', (evt) => {
			if (isButtonsHidden() && buttons.find(b => b === evt.target)) {
				setVisibility(buttons, currentSelected, true);
				// Reset focus to current selected
				currentSelected.btn.focus();
			}
		});

		// Arrow keys left and right cycles button focus when all buttons are visible. Set focused button.
		switcherContainer.addEventListener('keydown', (evt) => {
			if (evt.key === 'ArrowRight' || evt.key === 'ArrowLeft') {
				const i = buttons.findIndex(b => b === evt.target);
				if (i !== -1 && !isButtonsHidden()) {
					const focusedBtn = (evt.key === 'ArrowRight')
						? (buttons[i + 1] ?? buttons[0])
						: (buttons[i - 1] ?? buttons[buttons.length - 1]);
					focusedBtn.focus();
				}
			}
		});
	}

	/**
	 * Set all multilingual data and elements for the switchers
	 */
	function setSwitchersData(dataEls, pubLocaleData) {
		const propsData = {};
		dataEls.forEach((dataEl) => {
			const propName = dataEl.getAttribute('data-pkp-switcher-data');
			const switcherName = pubLocaleData[propName].switcher;
			if (!propsData[switcherName]) {
				propsData[switcherName] = {data: [], dataEls: []};
			}
			propsData[switcherName].data[propName] = pubLocaleData[propName].data;
			propsData[switcherName].dataEls[propName] = dataEl;
		});
		return propsData;
	}

	(() => {
		const switcherContainers = document.querySelectorAll('[data-pkp-switcher]');

		if (!switcherContainers.length) return;

		const pubLocaleData = JSON.parse(pubLocaleDataJson);
		const switchersDataEls = document.querySelectorAll('[data-pkp-switcher-data]');
		const switchersData = setSwitchersData(switchersDataEls, pubLocaleData);
		// Create and set switchers, and sync data on the page
		switcherContainers.forEach((switcherContainer) => {
			const switcherName = switcherContainer.getAttribute('data-pkp-switcher');
			if (switchersData[switcherName]) {
				setSwitcher(switchersData[switcherName], switcherContainer, pubLocaleData.localeOrder, pubLocaleData.localeNames, pubLocaleData.accessibility);
			}
		});
	})();
})();