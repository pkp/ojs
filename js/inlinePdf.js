/**
 * js/inlinePdf.js
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Functions for inline PDF render and viewing.
 */

/**
* Initialize the reading tool javascript (resizable and fullscreen mode)
*/
$(document).ready(function(){
	var inlineStyle = ''; // Store the inline style that resizable creates, and reassign it when done with fullscreen

	// For the background "lightbox" effect
	$("#container").prepend('<div id="fade" class="black_overlay"></div>');

	$('#sidebarToggle').click(function() {
		$('#sidebar').toggle().toggleClass("sidebar-hidden");
		$('#main').toggleClass("main-full");
	});

	$('#fullscreenShow').click(function() {
		inlineStyle = $("#inlinePdf").attr("style");
		$("#inlinePdf").removeAttr("style");  // Resizable doesn't work in fullscreen
		$("#inlinePdf").resizable("destroy");  // Resizable doesn't work in fullscreen
		$("#inlinePdf").addClass('fullscreen');
		$("#fade").fadeIn(2000);
		$("#fullscreenHide").show();
		return false;
	});

	$('#fullscreenHide').click(function() {
		$("#inlinePdf").attr("style", inlineStyle);
		$("#inlinePdf").removeClass('fullscreen');
		$("#fade").hide();
		$("#fullscreenHide").hide();
		$("#inlinePdf").resizable({ containment: 'parent', handles: 'se' }); // Reinitialize resizable
		return false;
	});
});
