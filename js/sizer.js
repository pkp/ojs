/**
 * sizer.js
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Font sizer JavaScript functions. 
 *
 * $Id$
 */

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
