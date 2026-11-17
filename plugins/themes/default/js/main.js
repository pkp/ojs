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
					var id = 'pkpDropdown' + i,
					$submenu = $(this),
					$link = $submenu.siblings('a');

					$submenu
						.addClass('dropdown-menu')
						.attr('aria-labelledby', id);

					// The link has already been swapped for a toggle button
					if ($submenu.siblings('button[data-toggle="dropdown"]').length) {
						return;
					}

					$('<button type="button"></button>')
						.html($link.html())
						.attr('data-toggle', 'dropdown')
						.attr('aria-haspopup', true)
						.attr('aria-expanded', false)
						.attr('id', id)
						.data('pkpNavLink', $link)
						.insertBefore($link);
					$link.detach();
				});
				$('[data-toggle="dropdown"]').dropdown();

			} else {
				$('[data-toggle="dropdown"]').dropdown('dispose');
				$submenus.each(function(i) {
					var $submenu = $(this),
					$toggle = $submenu.siblings('button[data-toggle="dropdown"]');

					$submenu
						.removeClass('dropdown-menu')
						.removeAttr('aria-labelledby');

					if ($toggle.length) {
						$toggle.replaceWith($toggle.data('pkpNavLink'));
					}
				});
			}
		}

		// The stylesheet also reveals a submenu while the pointer is over the
		// toggle or the submenu itself, which bootstrap knows nothing about.
		// Keep the toggle's expanded state in sync with what is on screen.
		$nav.on('mouseenter mouseleave', 'li', function(e) {
			var $toggle = $(this).children('[data-toggle="dropdown"]'),
			$submenu = $(this).children('ul');

			if (!$toggle.length) {
				return;
			}

			if (e.type === 'mouseenter') {
				$toggle.attr('aria-expanded', true);
			} else if (!$submenu.hasClass('show')) {
				// Leave it open if bootstrap opened it on click
				$toggle.attr('aria-expanded', false);
			}
		});

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
		a11y: {
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
