/**
 * @defgroup js_classes_features
 */
/**
 * @file js/classes/features/Feature.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Feature
 * @ingroup js_classes_features
 *
 * @brief Base grid feature class.
 * @see lib/pkp/classes/controllers/grid/feature/GridFeature.inc.php
 *
 * We use the features concept of the ext js framework:
 * http://docs.sencha.com/ext-js/4-0/#!/api/Ext.grid.feature.Feature
 */
(function($) {

	/** @type {Object} */
	$.pkp.classes.features = $.pkp.classes.features || {};



	/**
	 * @constructor
	 * @extends $.pkp.classes.ObjectProxy
	 * @param {$.pkp.controllers.grid.GridHandler} gridHandler The grid
	 *  handler object.
	 * @param {Array} options Associated options.
	 */
	$.pkp.classes.features.Feature =
			function(gridHandler, options) {
		this.gridHandler = gridHandler;
		this.options_ = options;
		this.addFeatureHtml(this.getGridHtmlElement(), options);
	};


	//
	// Protected properties.
	//
	/**
	 * The grid that this feature is attached to.
	 * @protected
	 * @type {$.pkp.controllers.grid.GridHandler}
	 */
	$.pkp.classes.features.Feature.prototype.gridHandler = null;


	//
	// Private properties.
	//
	/**
	 * This feature configuration options.
	 * @private
	 * @type {Object}
	 */
	$.pkp.classes.features.Feature.prototype.options_ = null;


	//
	// Setters and getters.
	//
	/**
	 * @param {Object} options The feature options.
	 */
	$.pkp.classes.features.Feature.prototype.setOptions =
			function(options) {
		this.options_ = options;
	};


	/**
	 * @return {Object} The feature options.
	 */
	$.pkp.classes.features.Feature.prototype.getOptions =
			function() {
		return this.options_;
	};


	//
	// Public template methods.
	//
	/**
	 * Initialize this feature. Needs to be extended to implement
	 * specific initialization. This method will always be called
	 * by the components that this feature is attached to, in the
	 * moment of the attachment.
	 */
	$.pkp.classes.features.Feature.prototype.init =
			function() {
		throw new Error('Abstract method!');
	};


	//
	// Template methods (hooks into grid widgets).
	//
	/**
	 * Hook into the add new element grid functionality.
	 * @param {jQueryObject} $newElement The new element to be added.
	 * @return {boolean} Always returns false.
	 */
	$.pkp.classes.features.Feature.prototype.addElement =
			function($newElement) {
		return false;
	};


	/**
	 * Hook into the replace element content grid functionality.
	 * @param {jQueryObject} $newContent The element new content to be shown.
	 * @return {boolean} Always returns false.
	 */
	$.pkp.classes.features.Feature.prototype.replaceElement =
			function($newContent) {
		return false;
	};


	/**
	 * Hook into the resequence rows grid functionality.
	 * @param {Object} sequenceMap The grid rows sequence.
	 * @return {boolean} Always returns false.
	 */
	$.pkp.classes.features.Feature.prototype.resequenceRows =
			function(sequenceMap) {
		return false;
	};


	/**
	 * Hook into the refresh grid functionality. Called just before
	 * the fetch (grid or row) call is done.
	 * @param {number|Object=} opt_elementId
	 * @return {boolean} Always returns false.
	 */
	$.pkp.classes.features.Feature.prototype.refreshGrid =
			function(opt_elementId) {
		return false;
	};


	/**
	 * Hook into the replace element response handler. Called after the
	 * response is handled.
	 * @param {Object} handledJsonData Object with the response content handled
	 * by the grid.
	 * @return {boolean} Always returns false.
	 */
	$.pkp.classes.features.Feature.prototype.replaceElementResponseHandler =
			function(handledJsonData) {
		return false;
	};


	//
	// Protected methods.
	//
	/**
	 * Use the grid handler object and call the
	 * callback wrapper method there.
	 * @see $.pkp.classes.Handler.callbackWrapper()
	 * @return {Function} Callback function.
	 */
	$.pkp.classes.features.Feature.prototype.callbackWrapper =
			function(callback, opt_context) {
		return this.gridHandler.callbackWrapper(callback, opt_context);
	};


	/**
	 * Extend to add extra html elements in the component
	 * that this feature is attached to.
	 * @param {jQueryObject} $gridElement Grid element to add elements to.
	 * @param {Object} options Feature options.
	 */
	$.pkp.classes.features.Feature.prototype.addFeatureHtml =
			function($gridElement, options) {
		// Default implementation does nothing.
	};


	/**
	 * Get the html element of the grid that this feature
	 * is attached to.
	 *
	 * @return {jQueryObject} Return the grid's HTML element.
	 */
	$.pkp.classes.features.Feature.prototype.getGridHtmlElement =
			function() {
		return this.gridHandler.getHtmlElement();
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
