/**
 * closure-externs-check-only.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2010-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Import symbols into the closure compiler that are not defined
 * within the files compiled during the strict check phase of the build
 * script. (We only include classes for strict checking, not legacy
 * function.)
 *
 * @externs
 */

// FIXME: Replace the reference to the ajaxAction() function
// with an object/event oriented approach, see #6339.
/**
 * @param {string} actOnId the ID of an element to be changed.
 * @param {string} callingElement selector of the element that triggers the ajax call
 * @param {string} url the url to be called, defaults to the form action in case of
 *  action type 'post'.
 * @param {Object=} data (post action type only) the data to be posted, defaults to
 *  the form data.
 * @param {string=} eventName the name of the event that triggers the action, default 'click'.
 * @param {string=} form the selector of a form element.
 */
function ajaxAction(actOnId, callingElement, url, data, eventName, form) {};


/**
 * @constructor
 */
function PNotify(param1) {};

// JQuery externs. These appear to be included in newer versions of Closure --
// see https://code.google.com/p/closure-compiler/source/browse/externs/es5.js
/**
 * A fake type to model the JSON object.
 * @constructor
 */
var JSONType = function() {};


/**
 * @param {string} jsonStr The string to parse.
 * @param {(function(string, *) : *)=} opt_reviver
 * @return {*} The JSON object.
 * @throws {Error}
 * @nosideeffects
 */
JSONType.prototype.parse = function(jsonStr, opt_reviver) {};


/**
 * @param {*} jsonObj Input object.
 * @param {(Array.<string>|(function(string, *) : *)|null)=} opt_replacer
 * @param {(number|string)=} opt_space
 * @return {string} JSON string which represents jsonObj.
 * @throws {Error}
 * @nosideeffects
 */
JSONType.prototype.stringify = function(jsonObj, opt_replacer, opt_space) {};


/**
 * @type {!JSONType}
 * @suppress {duplicate}
 */
var JSON;
