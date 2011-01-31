/**
 * articleView.js
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Initialization functions for reading tools javascript
 *
 * $Id:
 */

/**
 * initRelatedItems
 * Initializes the related items block's roll-up feature
 */
function initRelatedItems() {
	$(document).ready(function(){
		$("#relatedItems").hide();
		$("#toggleRelatedItems").show();
	    $("#hideRelatedItems").click(function() {
			$("#relatedItems").hide('slow');
			$("#hideRelatedItems").hide();
			$("#showRelatedItems").show();
		});
		$("#showRelatedItems").click(function() {
			$("#relatedItems").show('slow');
			$("#showRelatedItems").hide();
			$("#hideRelatedItems").show();
		});
	});
}

/**
* Initialize the reading tool javascript (resizable and fullscreen mode)
*/
$(document).ready(function(){
	var inlineStyle = ''; // Store the inline style that resizable creates, and reassign it when done with fullscreen

	$('#sidebarToggle').click(function() {
		$('#sidebar').toggle().toggleClass("sidebar-hidden");
		$('#main').toggleClass("main-full");
	});

	$('#fullscreenShow').click(function() {
		inlineStyle = $("#articlePdf").attr("style");
		$("#articlePdf").removeAttr("style");  // Resizable doesn't work in fullscreen
		$("#articlePdf").resizable("destroy");  // Resizable doesn't work in fullscreen
		$("#articlePdf").addClass('fullscreen');
		$("#fade").fadeIn(2000);
		$("#fullscreenHide").show();
		return false;
	});

	$('#fullscreenHide').click(function() {
		$("#articlePdf").attr("style", inlineStyle);
		$("#articlePdf").removeClass('fullscreen');
		$("#fade").hide();
		$("#fullscreenHide").hide();
		$("#articlePdf").resizable({ containment: 'parent', handles: 'se' }); // Reinitialize resizable
		return false;
	});
});