/**
 * @file plugins/themes/default/js/main.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Handle JavaScript functionality unique to this theme.
 */
(function($) {

	// Update nav menu ARIA states on focus, blur and mouse over/out events
	$('.navDropdownMenu ul').on('focus.default  mouseenter.default', '[aria-haspopup="true"]', function (e) {
		$(e.currentTarget).attr('aria-expanded', true);
	});

	$('.navDropdownMenu ul').on('blur.default  mouseleave.default', '[aria-haspopup="true"]', function (e) {
		$(e.currentTarget).attr('aria-expanded', false)
			.attr('pkp-touch-state', '');
	});

	// Nav menu items with submenus should expand on first click/tap
	// The `click` listener is necessary. Some phones (eg - iPhone 5 in Chrome's
	// emulator) try to "help" when a touch is slightly off-target by firing
	// an event on the adjacent element. However, it fires the `click` event,
	// not the `touchstart` event when doing so.
	$('.navDropdownMenu a').on('touchstart, click', function(e) {
		var target = $(e.target),
			li = target.parents('li').first();
		if (li.length && li.attr('aria-haspopup')) {
			var state = li.attr('pkp-touch-state') || '';
			if (state != 'open') {
				target.trigger('focus.default');
				li.attr('pkp-touch-state', 'open');
				e.preventDefault();
			}
		}
		return true;
	});

	// Register click handlers for the search panel
	var headerSearchPanelIsClosing = false,
	    headerSearchForm = $('#headerNavigationContainer .pkp_search'),
	    headerSearchPrompt = $('.headerSearchPrompt', headerSearchForm),
		headerSearchCancel = $('.headerSearchCancel', headerSearchForm),
		headerSearchInput = $('input[name="query"]', headerSearchForm);

	// Register events
	headerSearchPrompt.on('click', triggerSearchPanel);
	headerSearchCancel.on('click', closeSearchPanel);
	headerSearchInput.on('blur', function() {
		if(!headerSearchInput.val() && headerSearchForm.hasClass('is_open')) {
			closeSearchPanel();
		}
	});
	headerSearchForm.on('submit', function() {
		if(headerSearchForm.hasClass('is_searching')) {
			return;
		}
		headerSearchForm.addClass('is_searching');
	});
	headerSearchForm.on('keyup', function(e) {
		if(headerSearchForm.hasClass('is_open') && e.keyCode == 27) {
			closeSearchPanel();
		}
	});

	/**
	 * Open or submit search form
	 *
	 * @param Event e Optional event handler
	 */
	function triggerSearchPanel(e) {

		if (headerSearchPanelIsClosing) {
			return;
		}

		if (typeof e !== 'undefined') {
			e.preventDefault();
			e.stopPropagation();
		}

		if (headerSearchForm.hasClass('is_open')) {
			headerSearchForm.submit();
			return;
		}

		headerSearchForm.addClass('is_open');
		setTimeout(function() {
			headerSearchForm.find('input[type="text"]').focus();
		},200);
	}

	/**
	 * Close the search panel
	 *
	 * @param Event e Optional event handler
	 */
	function closeSearchPanel(e) {

		if (headerSearchPanelIsClosing) {
			return;
		}

		if (typeof e !== 'undefined') {
			e.preventDefault();
			e.stopPropagation();
		}

		headerSearchPanelIsClosing = true;
		headerSearchForm.removeClass('is_open');

		setTimeout(function() {
			headerSearchPanelIsClosing = false;
			headerSearchInput.val('');
		},300)
	}

	// Modify the Chart.js display options used by UsageStats plugin
	document.addEventListener('usageStatsChartOptions.pkp', function(e) {
		e.chartOptions.elements.line.backgroundColor = 'rgba(0, 122, 178, 0.6)';
		e.chartOptions.elements.rectangle.backgroundColor = 'rgba(0, 122, 178, 0.6)';
	});

	// Initialize tag-it components
	//
	// The tag-it component is used during registration for the user to enter
	// their review interests. See: /templates/frontend/pages/userRegister.tpl
	if (typeof $.fn.tagit !== 'undefined') {
		$('.tag-it').each(function() {
			var autocomplete_url = $(this).data('autocomplete-url');
			$(this).tagit({
				fieldName: $(this).data('field-name'),
				allowSpaces: true,
				autocomplete: {
					source: function(request, response) {
						$.ajax({
							url: autocomplete_url,
							data: {term: request.term},
							dataType: 'json',
							success: function(jsonData) {
								if (jsonData.status == true) {
									response(jsonData.content);
								}
							}
						});
					},
				},
			});
		});

		/**
		 * Determine if the user has opted to register as a reviewer
		 *
		 * @see: /templates/frontend/pages/userRegister.tpl
		 */
		function isReviewerSelected() {
			var group = $('#reviewerOptinGroup').find('input');
			var is_checked = false;
			group.each(function() {
				if ($(this).is(':checked')) {
					is_checked = true;
					return false;
				}
			});

			return is_checked;
		}

		/**
		 * Reveal the reviewer interests field on the registration form when a
		 * user has opted to register as a reviewer
		 *
		 * @see: /templates/frontend/pages/userRegister.tpl
		 */
		function reviewerInterestsToggle() {
			var is_checked = isReviewerSelected();
			if (is_checked) {
				$('#reviewerInterests').addClass('is_visible');
			} else {
				$('#reviewerInterests').removeClass('is_visible');
			}
		}

		// Update interests on page load and when the toggled is toggled
		reviewerInterestsToggle();
		$('#reviewerOptinGroup input').click(reviewerInterestsToggle);
	}

})(jQuery);
