/**
 * general.js
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site-wide common JavaScript functions. 
 *
 * $Id$
 */

/**
 * Prompt user for confirmation prior to loading a URL.
 */
function confirmAction(url, msg) {
	if (confirm(msg)) {
		if (url) {
			document.location.href=url;
		}
		return true;
	}
	return false;
}

/**
 * Open window displaying help.
 */
function openHelp(url) {
	window.open(url, 'Help', 'width=700,height=600,screenX=100,screenY=100,toolbar=0,scrollbars=1');
}

/**
 * Open window displaying comments.
 */
function openComments(url) {
	window.open(url, 'Comments', 'width=700,height=600,screenX=100,screenY=100,toolbar=0,resizable=1,scrollbars=1');
}

/**
 * Open window for preview.
 */
function openWindow(url) {
	window.open(url, 'Window', 'width=600,height=550,screenX=100,screenY=100,toolbar=0,resizable=1,scrollbars=1');
}

/**
 * Open window for reading tools.
 */
function openRTWindow(url) {
	window.open(url, 'RT', 'width=700,height=500,screenX=100,screenY=100,toolbar=0,resizable=1,scrollbars=1');
}
function openRTWindowWithToolbar(url) {
	window.open(url, 'RT', 'width=700,height=500,screenX=100,screenY=100,toolbar=1,resizable=1,scrollbars=1');
}

/**
 * browser object availability detection
 * @param objectId string of object needed
 * @param style int (0 or 1) if style object is needed
 * @return javascript object specific to current browser
 */
function getBrowserObject(objectId, style) {
	var isNE4 = 0;
	var currObject;

	// browser object for ie5+ and ns6+
	if (document.getElementById) {
		currObject = document.getElementById(objectId);
	// browser object for ie4+
	} else if (document.all) {
		currObject = document.all[objectId];
	// browser object for ne4
	} else if (document.layers) {
		currObject = document.layers[objectId];
		isNE4 = 1;
	} else {
		// do nothing
	}
	
	// check if style is needed
	if (style && !isNE4) {
		currObject = currObject.style;
	}
	
	return currObject;
}

/**
 * Load a URL.
 */
function loadUrl(url) {
	document.location.href=url;	
}

function getStylesheets() {
	var linkNodes, styleNodes, x, sheets = [];
	if (!window.ScriptEngine && navigator.__ice_version ) {
		return document.styleSheets;
	}
	if (document.getElementsByTagName) {
		linkNodes = document.getElementsByTagName('link');
		styleNodes = document.getElementsByTagName('style');
	} else if (document.styleSheets && document.all) {
		linkNodes = document.all.tags('LINK');
		styleNodes = document.all.tags('STYLE');
	} else {
		return [];
	}
	for (x = 0; linkNodes[x]; x++) {
		var rel = linkNodes[x].rel ? linkNodes[x].rel : linkNodes[x].getAttribute ? linkNodes[x].getAttribute('rel') : '';
		if (typeof(rel) == 'string' && rel.toLowerCase().indexOf('style') != -1) {
			sheets[sheets.length] = linkNodes[x];
		}
	}
	for (x = 0; styleNodes[x]; x++) {
		sheets[sheets.length] = styleNodes[x];
	}
	return sheets;
}


/**
 * Set the font size to the named stylesheet.
 * Thanks to www.alistsapart.com for the basic design.
 */
function setFontSize(size) {
	var s = getStylesheets();
	for (var i=0; i < s.length; i++) {
		if (s[i].getAttribute("rel").indexOf("style") != -1 && s[i].getAttribute("title")) {
			s[i].disabled = true;
			if(s[i].getAttribute("title") == size) s[i].disabled = false;
		}
	}
}

/**
 * Get the current font size.
 * Thanks to www.alistapart.com for the basic design.
 */
function getFontSize() {
	var s = getStylesheets();
	for (var i=0; i < s.length; i++) {
		if(s[i].getAttribute("rel").indexOf("style") != -1 && s[i].getAttribute("title") && !s[i].disabled) return s[i].getAttribute("title");
	}
	return null;
}

function createCookie(name,value,days) {
	if (days) {
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else expires = "";
	document.cookie = name+"="+value+expires+"; path=/";
}

function readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}

function getPreferredFontSize() {
	var s = getStylesheets();
	for (var i=0; i < s.length; i++) {
		if(s[i].getAttribute("rel").indexOf("style") != -1 && s[i].getAttribute("rel").indexOf("alt") == -1 && s[i].getAttribute("title")) return s[i].getAttribute("title");
	}
	return null;
}

window.onload = function(e) {
	var cookie = readCookie("font-size");
	var size = cookie ? cookie : getPreferredFontSize();
	setFontSize(size);
}

window.onunload = function(e) {
	var size = getFontSize();
	createCookie("font-size", size, 365);
}

/**
 * Asynchronous request functions
 */
function makeAsyncRequest(){
	var req=(window.XMLHttpRequest)?new XMLHttpRequest():new ActiveXObject('Microsoft.XMLHTTP');
	return req;
}

function sendAsyncRequest(req, url, data, method) {
	var header = 'Content-Type:text/html; Charset=utf-8';
	req.open(method, url, true);
	req.setRequestHeader(header.split(':')[0],header.split(':')[1]);
	req.send(data);
}


