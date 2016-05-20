/**
 * @file js/pages/ArticleDownloadHandler.js
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display and download revisions of an article file
 */

$(function($) {

	/** @type {Object} */
	$.pkp.pages = $.pkp.pages || {};
	
	
	/**
	 * @constructor
	 */
	$.pkp.pages.ArticleDownloadHandler = function() {
		
		var selectRevisions = $('select.revisions');
		var articleDownloadHandler = this;
	
		selectRevisions.each(function() {
			var lastRevision = articleDownloadHandler.getLatestRevision($(this));
			articleDownloadHandler.attachRevisionLink($(this), lastRevision);
		});
		
		selectRevisions.change(function() {
			var revision = $(this).val();
			articleDownloadHandler.attachRevisionLink($(this), revision, true);
		});
	};
	
	
	/**
	 * getLatestRevision
	 * @param {jQueryObject} selectField
	 * @return {int} latest revision id
	 */
	$.pkp.pages.ArticleDownloadHandler.prototype.getLatestRevision = function(selectField) {
		return selectField.children().first().val();
	}
	
	
	/**
	 * getRevisionLink
	 * @param {jQueryObject} selectField
	 * @return {int} latest revision id
	 */
	$.pkp.pages.ArticleDownloadHandler.prototype.getRevisionLink = function(selectField) {
		return selectField.parent().next('a');
	}
	
	
	/**
	 * attachRevisionLink
	 * @param {jQueryObject} selectField
	 * @param {int} revision
	 * @param {boolean} changeRevision
	 */
	$.pkp.pages.ArticleDownloadHandler.prototype.attachRevisionLink = function(selectField, revision, hasChanged = false) {
		var revisionLink = this.getRevisionLink(selectField);
		var revisionLinkTarget = revisionLink.attr('href');
		
		if (hasChanged == false) {
			revisionLink.attr('href', revisionLinkTarget + '/' + revision);
		} else {
			var arrRevisionLinkTarget = revisionLinkTarget.split('/');
			arrRevisionLinkTarget.pop();
			
			revisionLink.attr('href', arrRevisionLinkTarget.join('/') + '/' + revision);
		}
	}
});