/**
 * js/relatedItems.js
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Initialization functions for reading tools related items javascript
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
