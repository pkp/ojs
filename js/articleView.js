/**
 * articleView.js
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Initialization functions for reading tools javascript
 *
 * $Id:
 */

/**
 * initPdfResize
 * Initializes the resizer control for the embedded PDF view
 */
function initPdfResize() {
	$(document).ready(function(){
	    $("#articlePdf").resizable({ containment: 'parent' });
	});
}

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