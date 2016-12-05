/**
 * js/controllers/tab/issueEntry/SubmissionRevisionsHandler.js
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Features for the "Submission and Publication Metadata" overlay:
 *    a) display old revisions
 *    b) save changes as a new revision
 */

$(function($) {
    /** @type {Object} */
	
    $.pkp.controllers.tab.issueEntry = $.pkp.controllers.tab.issueEntry || {};
	
	
    /**
	 * @constructor
	 * @param {jQueryObject} $form the wrapped HTML form element.
	 * @param {Object} options form options.
	 */
	$.pkp.controllers.tab.issueEntry.SubmissionRevisionsHandler = function($form, options) {
		
		var submissionRevisionsHandler = this;
		var selectSubmissionSettingsRevision = this.getRevisionSelectField();
		var saveAsRevision = this.getSaveAsRevisionField();

		/** Display an old revision */
		selectSubmissionSettingsRevision.change(function() {
			var selectedRevision = $(this).val();
			
			submissionRevisionsHandler.setRevision(selectedRevision);
			submissionRevisionsHandler.refreshOverlay('revision', selectedRevision);
		});
		
		/** Save changes as new revision */
		saveAsRevision.click(function() {
			selectSubmissionSettingsRevision.val(submissionRevisionsHandler.getLatestRevision());
			
			var currentSelection = ($(this).prop('checked') == true) ? 1 : 0;
			
			if (currentSelection == 1) { 
				selectSubmissionSettingsRevision.hide();
				submissionRevisionsHandler.setRevision(0);
			} else {
				selectSubmissionSettingsRevision.show();
				submissionRevisionsHandler.setRevision(submissionRevisionsHandler.getLatestRevision());
			}
			
			submissionRevisionsHandler.refreshOverlay('saveAsRevision', currentSelection);
		});
		
		if (this.getSelectedRevision() < this.getLatestRevision()) {
			this.disableFields($form);
		}
	}
	
	
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.tab.issueEntry.SubmissionRevisionsHandler,
			$.pkp.controllers.tab.issueEntry.IssueEntryTabHandler);
	
	
	/**
	 * disableFields
	 * Disables the input fields of the current form when an old revision is selected (old revision should be read only)
	 * @param {jQueryObject} metadataForm
	 */
	$.pkp.controllers.tab.issueEntry.SubmissionRevisionsHandler.prototype.disableFields = function(metadataForm) {
		var formFields = metadataForm.find('input, select, textarea, button[type=submit]');
		formFields.attr('disabled', 'disabled');
	}
	
	
	/**
	 * refreshOverlay
	 * set the parameters for the tab links and reload the "Submission and Publication Metadata" overlay
	 * per default, the first tab (submission) is active after refreshing
	 * @param {string} key
	 * @param {mixed} value
	 */
	$.pkp.controllers.tab.issueEntry.SubmissionRevisionsHandler.prototype.refreshOverlay = function(key, value) {
		var tabs = $('#newIssueEntryTabs > ul li a');
		
		for(i = tabs.size()-1; i >= 0; i--) {
			var link = $(tabs[i]);
			this.attachParam(link, key, value);
			link.click();
		}
	}
	
	
	/**
	 * attachParam
	 * sets the revision or saveAsRevision parameter for a tab link
	 * @param {object} link
	 * @param {string} key
	 * @param {mixed} value
	 */
	$.pkp.controllers.tab.issueEntry.SubmissionRevisionsHandler.prototype.attachParam = function(link, key, value) {
		var linkTarget = link.attr('href');

		if (linkTarget.indexOf('&revision=') > 0) {
			linkTarget = linkTarget.split('&revision=')[0];
		}
		
		if (linkTarget.indexOf('&saveAsRevision=') > 0) {
			linkTarget = linkTarget.split('&saveAsRevision=')[0];
		}

		linkTarget += ('&' + encodeURIComponent(key) + '=' + encodeURIComponent(value));
		link.attr('href', linkTarget);
	}
}); 
