/**
 * general.js
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
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
	window.open(url, 'Help', 'width=600,height=550,screenX=100,screenY=100,toolbar=0,scrollbars=1');
}

/**
 * Open window displaying comments.
 */
function openComments(url) {
	window.open(url, 'Comments', 'width=550,height=350,screenX=100,screenY=100,toolbar=0,resizable=1,scrollbars=1');
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

/**
 * Retrieve parent of requested tag
 * @param event element that invoked the event
 * @param tag the html tag to be found
 */
function getParent(event, tag) {
	if (event.tagName != tag) {
		if (document.getElementById) {
			return getParent(event.parentNode, tag);
		} else if (document.all) {
			return getParent(event.parentElement, tag);
		}
	} else {
		return event;
	}
}

/**
 * Mark the row if it was selected
 * @param event element that invoked the event
 * @param cssStyleName name of the new style name
 * @param orgStyleName name of the old style name
 */
function markRow(event, cssStyleName, orgStyleName) {
	var parentTR = getParent(event,'TR');
	if (parentTR.getAttribute('class') == cssStyleName) {
		parentTR.setAttribute('class',orgStyleName);
	} else {
		parentTR.setAttribute('class', cssStyleName);
	}
}

/**
 * Mark all rows
 * @param thisForm string form name
 * @param cName string class name of the checkbox
 * @param check boolean toggle between check all/none
 * @param cssStyleName string of style class
 * @param cssAltStyleName string of the alternative style class
 */
function checkAll(thisForm, cName, check, cssStyleName, cssAltStyleName) {
    var cForm = getBrowserObject(thisForm);
	var cssStyle = cssStyleName;
	for (i=0,n=cForm.elements.length;i<n;i++) {
        if (cForm.elements[i].className.indexOf(cName) !=-1) {
			cForm.elements[i].checked = check;
			markRow(cForm.elements[i],cssStyle,cssStyle);
			cssStyle = (cssStyle == cssStyleName) ?	cssAltStyleName : cssStyleName;
		}
	}
}

/**
 * Modify form action and submit the form
 * @param thisForm string form name
 * @param newAction string of the new action of form
 * @param zero check if this is the first value of the options
 * - zero param is used to disable any action taken with the first option
 */
function changeActionAndSubmit(thisForm, newAction, zero) {
	thisForm.action = newAction;
	if (zero != 0) {
		if (thisForm.onsubmit()) {
			thisForm.submit();
		}
	}
}
