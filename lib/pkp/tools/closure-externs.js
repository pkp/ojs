/**
 * closure-externs.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2010-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Import symbols into the closure compiler that are not defined
 * within the compiled files.
 *
 * See https://github.com/google/closure-compiler/tree/master/externs
 * for pre-extracted extern files, e.g. for jQuery.
 *
 * @externs
 */

jQueryObject.prototype.browser = { msie: false };

/**
 * @param {Object} arg1
 */
jQueryObject.prototype.autocomplete = function(arg1) {};

/**
 * @param {string=} param1
 * @param {string|number=} param2
 * @param {string=} param3
 */
jQueryObject.prototype.button = function(param1, param2, param3) {};


/**
 * @param {Object=} options
 */
jQueryObject.prototype.validate = function(options) {};
jQueryObject.prototype.valid = function() {};

/**
 * @param {Function} param1
 */
jQueryObject.prototype.sortElements = function(param1) {};

/**
 * @param {Object|string=} param1
 */
jQueryObject.prototype.sortable = function(param1) {};

/**
 * @param {Object=} options
 */
jQueryObject.prototype.jLabel = function(options) {};

/**
 * @param {Object=} options
 */
jQueryObject.prototype.selectBox = function(options) {};

jQueryObject.prototype.equalizeElementHeights = function() {};

/**
 * @param {Object=} options
 */
jQueryObject.prototype.slider = function(options) {};

/**
 * @param {string|Object=} param1
 * @param {string|number|Object=} param2
 * @param {string|number|Object=} param3
 */
jQueryObject.prototype.tabs = function(param1, param2, param3) {};

/**
 * @param {string|Object} param1
 * @param {string|Object=} param2
 */
jQueryObject.prototype.datepicker = function(param1, param2) {};

/**
 * @param {string|Object} param1
 * @param {string|Object|boolean|number=} param2
 * @param {string|boolean=} param3
 */
jQueryObject.prototype.accordion = function(param1, param2, param3) {};

/**
 * Handler plug-in.
 * @param {string} handlerName The handler to be instantiated
 *  and attached to the target HTML element(s).
 * @param {Object=} options Parameters to be passed on
 *  to the handler.
 * @return {jQueryObject} Selected HTML elements for chaining.
 */
jQueryObject.prototype.pkpHandler = function(handlerName, options) {};

/**
 * Re-implementation of jQuery's html() method
 * with a remote source.
 * @param {string} url the AJAX endpoint from which to
 *  retrieve the HTML to be inserted.
 * @param {Object=} callback function to be called on ajax success.
 * @return {jQueryObject} Selected HTML elements for chaining.
 */
jQueryObject.prototype.pkpAjaxHtml = function(url, callback) {};

/**
 * @param {string|Object=} param1
 * @param {string=} param2
 * @param {string|Object=} param3
 */
jQueryObject.prototype.dialog = function(param1, param2, param3) {};

/**
 * @param {Object=} options
 */
jQueryObject.prototype.spectrum = function(options) {};

/**
 * @constructor
 * @param {Object=} options
 * @param {jQueryObject=} form
 */
jQuery.validator = function(options, form) {};

jQuery.validator.prototype.checkForm = function() {};

jQuery.validator.prototype.defaultShowErrors = function() {};

jQuery.validator.prototype.settings = {};

/**
 * @param {string} param1
 * @param {string|boolean|Object=} param2
 */
jQueryObject.prototype.prop = function(param1, param2) {};

jQueryObject.prototype.panel = null;
jQueryObject.prototype.newTab = null;
jQueryObject.prototype.newTab.index = function() {};
jQueryObject.prototype.newTab.find = function() {};
jQueryObject.prototype.newPanel = null;
jQueryObject.prototype.ajaxSettings = null;

/**
 * @constructor
 * @private
 */
function tinyMCEObject() {};

tinyMCEObject.prototype.PluginManager = {};

/**
 * @param {string} param1
 * @param {string} param2
 * @return {tinyMCEObject}
 */
tinyMCEObject.prototype.PluginManager.load = function(param1, param2) {};

tinyMCEObject.prototype.EditorManager = {};

tinyMCEObject.prototype.EditorManager.triggerSave = function() {};

/**
 * @param {string} param1
 * @param {Object} param2
 * @return {tinyMCEObject}
 */
tinyMCEObject.prototype.EditorManager.createEditor = function(param1, param2) {};

/**
 * @param {string} param1
 * @return {tinyMCEObject}
 */
tinyMCEObject.prototype.EditorManager.get = function(param1) {};

/**
 * @param {Object} param1
 */
tinyMCEObject.prototype.init = function(param1) {};

/**
 * @param {string} param1
 * @return {tinyMCEObject}
 */
tinyMCEObject.prototype.get = function(param1) {};

tinyMCEObject.prototype.target = {dom: {}, editorContainer: {}};

/**
 * @param {string} param1
 */
tinyMCEObject.prototype.target.dom.get = function(param1) {};

tinyMCEObject.prototype.target.getContent = function() {};
tinyMCEObject.prototype.getContent = function() {};

/**
 * @param {string} param1
 */
tinyMCEObject.prototype.setContent = function(param1) {};

tinyMCEObject.prototype.render = function() {};

/**
 * @param {string} param1
 * @param {Object} param2
 */
tinyMCEObject.prototype.on = function(param1, param2) {};

tinyMCEObject.prototype.off = function() {};

tinyMCEObject.prototype.editor = { dom: {}, id: '' };

tinyMCEObject.prototype.dom = {};

tinyMCEObject.prototype.editor.dom.getRoot = function() {};

/**
 * @type {string} c
 */
tinyMCEObject.prototype.id = '';

tinyMCEObject.prototype.getWin = function() {};

tinyMCEObject.prototype.getBody = function() {};

tinyMCEObject.prototype.getContainer = function() {};

tinyMCEObject.prototype.onSetContent = function() {};

/**
 * @param {Object} param1
 */
tinyMCEObject.prototype.onSetContent.add = function(param1) {};

/**
 * @param {Object} param1
 */
tinyMCEObject.prototype.onSetContent.remove = function(param1) {};

/**
 * @type {tinyMCEObject}
 */
var tinyMCE;

/**
 * @param {string} f
 */
jQueryObject.prototype.plupload = function(f) {};
var plupload = {};

/**
 * @param {Object} options
 * @constructor
 */
plupload.Uploader = function (options) {};
plupload.Uploader.prototype.id = null;
plupload.Uploader.prototype.init = function() {};
plupload.Uploader.prototype.refresh = function() {};

/**
 * @param {string|number} p
 */
plupload.Uploader.prototype.percent = function(p) {};

/**
 * @param {string} f
 */
plupload.Uploader.prototype.removeFile = function(f) {};

/**
 * @param {!string} eventName
 * @param {Function} f
 */
plupload.Uploader.prototype.bind = function(eventName, f) {};

$.pkp.app = {
	baseUrl: ''
};

$.pkp.locale = {
	search_noKeywordError: '',
	form_dataHasChanged: '',
	common_close: ''
};

$.pkp.cons = {
	WORKFLOW_STAGE_ID_SUBMISSION: 0,
	WORKFLOW_STAGE_ID_INTERNAL_REVIEW: 0,
	WORKFLOW_STAGE_ID_EXTERNAL_REVIEW: 0,
	WORKFLOW_STAGE_ID_EDITING: 0,
	WORKFLOW_STAGE_ID_PRODUCTION: 0,
	REALLY_BIG_NUMBER: 0,
	ORDER_CATEGORY_GRID_CATEGORIES_ONLY: 0,
	ORDER_CATEGORY_GRID_CATEGORIES_AND_ROWS: 0,
	LISTBUILDER_SOURCE_TYPE_SELECT: 0,
	LISTBUILDER_OPTGROUP_LABEL: 0,
	ORDER_CATEGORY_GRID_CATEGORIES_ROWS_ONLY: 0,
	UPLOAD_MAX_FILESIZE: 0
}
