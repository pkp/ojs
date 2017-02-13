/**
 * @file js/plugins/citationFormats.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Frontend script to fetch citation formats on demand.
 *
 * This script is designed to be compatible with any themes that follow these
 * steps. First, you need a target element that will display the citation in
 * the requested format. This should have an id of `citationOutput`:
 *
 * <div id="citationOutput"></div>
 *
 * You can create a link to retrieve a citation and display it in this div by
 * assigning the link a `data-load-citation` attribute:
 *
 * <a href="{url ...}" data-load-citation="true">View citation in ABNT format</a>
 *
 * Downloadable citations should leave the `data-load-citation` attribute out
 * to allow normal browser download handling.
 *
 * This script requires jQuery. The format you specify should match
 * a format provided by a CitationFormat plugin.
 */
(function($) {

	// Require jQuery
	if (typeof $ === 'undefined') {
		return;
	}

	var citationOutput = $('#citationOutput'),
		citationFormatLinks = $('[data-load-citation]');

	if (!citationOutput.length || !citationFormatLinks.length) {
		return;
	}

	citationFormatLinks.click(function(e) {
		e.preventDefault();
		e.stopPropagation();

		var url = $(this).attr('href') + '/json';

		citationOutput.css('opacity', 0.5);

		$.ajax({url: url, dataType: 'json'})
			.done(function(r) {
				citationOutput.html(r.content)
					.hide()
					.css('opacity', 1)
					.fadeIn();
			})
			.fail(function(r) {
				citationOutput.css('opacity', 1);
			});
	});

})(jQuery);
