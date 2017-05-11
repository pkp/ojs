/**
 * @file js/classes/linkAction/PostAndRedirectRequest.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PostAndRedirectRequest
 * @ingroup js_classes_linkAction
 *
 * @brief An action request that will post data and then redirect, using two
 * different urls. For both requests, it will post the passed data. If none is
 * passed, then it will post nothing. You can provide a js event response for
 * the first post request and it will be handled.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.linkAction.LinkActionRequest
	 *
	 * @param {jQueryObject} $linkActionElement The element the link
	 *  action was attached to.
	 * @param {Object} options Configuration of the link action
	 *  request.
	 */
	$.pkp.classes.linkAction.PostAndRedirectRequest =
			function($linkActionElement, options) {

		this.parent($linkActionElement, options);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.classes.linkAction.PostAndRedirectRequest,
			$.pkp.classes.linkAction.LinkActionRequest);

	
	//
	// Private properties
	//
	/**
	 * Post request response data.
	 * @private
	 * @type {?Object}
	 */
	$.pkp.classes.linkAction.PostAndRedirectRequest.prototype.
			postJsonData_ = null;


	//
	// Public methods
	//
	/**
	 * @inheritDoc
	 */
	$.pkp.classes.linkAction.PostAndRedirectRequest.prototype.activate =
			function(element, event) {
		var returner = this.parent('activate', element, event),
				options = this.getOptions(),
				// Create a response handler for the first request (post).
				responseHandler = $.pkp.classes.Helper.curry(
						this.handleResponse_, this),
				finishCallback;

		// Post.
		$.post(/** @type {{postUrl: string}} */ (options).postUrl,
				responseHandler, 'json');

		return /** @type {boolean} */ (returner);
	};


	//
	// Private helper methods.
	//
	/**
	 * Callback to be called after a timeout.
	 * @private
	 */
	$.pkp.classes.linkAction.PostAndRedirectRequest.prototype.finishCallback_ =
			function() {
		var $linkActionElement = this.getLinkActionElement(),
				// Get the link action handler to handle the json response.
				linkActionHandler = $.pkp.classes.Handler.getHandler($linkActionElement);

		this.finish();
		linkActionHandler.handleJson(this.postJsonData_);
	};


	/**
	 * The post data response handler.
	 * @param {Object} jsonData A parsed JSON response object.
	 * @private
	 */
	$.pkp.classes.linkAction.PostAndRedirectRequest.prototype.handleResponse_ =
			function(jsonData) {
		var options = this.getOptions(), timer = null, finishCallback = null;

		// Save return data to be handled at the finish callback. If
		// the redirect action loads another page, then the interface
		// will be updated anyway and any events that could be triggered
		// by the post response will be useless, so that's ok that the
		// finish callback is not called in that case, and that the post
		// json answer is never handled.
		// If a new page is not loaded, then we have to wait for the redirect
		// action to start (probably a file download) and only then handle the post
		// answer, avoiding triggering events that could replace the current link
		// action element before the redirect request starts.
		// In a download action, it avoids the activation of the download link
		// action before the download triggered by the first click starts.
		this.postJsonData_ = jsonData;

		// Redirect, making sure there is no ajax request in progress,
		// to avoid stoping them.
		timer = setInterval(function() {
			if ($.active == 0) {
				clearInterval(timer);
				window.location = /** @type {{url: string}} */ (options).url;
			}
		},100);

		// When it's a download action, try to avoid double execution.
		// Not ideal, see issue #247.
		finishCallback = $.pkp.classes.Helper.curry(
				this.finishCallback_, this);
		setTimeout(finishCallback, 2000);
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
