/**
 * js/functions/citation.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * DEPRECATED implementation of jQuery modals and other JS backend functions for
 * the citation manager.
 * DO NOT USE.
 */

/**
 * Opens a modal confirm box.
 * @param {string} url URL to load into the modal
 * @param {string} actType Type to define if callback should do (nothing|append|replace|remove)
 * @param {string} actOnId The ID on which to perform the action on callback
 * @param {string} dialogText Text to display in the dialog
 * @param {Array} localizedButtons of translated 'Cancel/submit' strings
 * @param {string} callingElement Selector of the element (e.g. a button) that opens the modal
 * @param {string} title
 * @param {boolean} isForm whether to interpret the actOnId as a form container and
 *  submit it as a form.
 */
function modalConfirm(url, actType, actOnId, dialogText, localizedButtons, callingElement, title, isForm) {
	$(function() {
		// Tell the calling button to open this modal on click
		$(callingElement).live("click", function() {
			if (!title) {
				// Try to retrieve title from calling button's text.
				title = $(callingElement).text();
				if (title === '') {
					// Try to retrieve title from calling button's title attribute.
					title = $(callingElement).attr('title');
				}
			}
			var okButton = localizedButtons[0];
			var cancelButton = localizedButtons[1];

			// Construct action to perform when OK and Cancels buttons are clicked
			var dialogOptions = {};
			if(url == null) {
				// Show a simple alert dialog (does not communicate with server)
				dialogOptions[okButton] = function() {
					$(this).dialog("close");
				};
			} else {
				dialogOptions[okButton] = function() {
					if (isForm) {
						// Interpret the "act on id" as a form to
						// be posted.
						submitJsonForm(actOnId, actType, actOnId, url);
					} else {
						// Trigger start event.
						$(actOnId).triggerHandler('actionStart');

						// Post to server and construct callback
						$.post(url, '', function(returnString) {
							// Trigger stop event
							$(actOnId).triggerHandler('actionStop');

							if (returnString.status) {
								updateItem(actType, actOnId, returnString.content);
							} else {
								// Alert that the action failed
								alert(returnString.content);
							}
						}, 'json');
					}
					$('#modalConfirm').dialog("close");
				};
				dialogOptions[cancelButton] = function() {
					$(actOnId).triggerHandler('actionStop');
					$(this).dialog("close");
				};
			}

			// Construct dialog
			var $dialog = $('<div id=\"modalConfirm\">'+dialogText+'</div>').dialog({
				title: title,
				autoOpen: true,
				modal: true,
				draggable: false,
				buttons: dialogOptions,
				close: function() {
					$('#modalConfirm').dialog('destroy');
					$('#modalConfirm').remove();
				}
			});

			$dialog.dialog('open');
			return false;
		});
	});
}

/**
 * Submit a form that returns JSON data.
 * @param {string} formContainer Selector of the element containing the form to be submitted, e.g. a modal's ID (must include '#')
 * @param {string} actType Type to define if callback should do (nothing|append|replace|remove)
 * @param {string} actOnId The ID on which to perform the action on callback
 * @param {string=} url the URL to submit to
 */
function submitJsonForm(formContainer, actType, actOnId, url) {
	// jQuerify the form container and find the form in it.
	var $formContainer = $(formContainer);
	var $form = $formContainer.find('form');
	var validator = $form.validate();

	if (!url) {
		url = $form.attr('action');
	}

	// Post to server and construct callback
	if ($form.valid()) {
		// Retrieve form data.
		var data = $form.serialize();

		// Trigger start event.
		$(actOnId).triggerHandler('actionStart');

		$.post(
			url,
			data,
			function(jsonData) {
				// Trigger stop event.
				$(actOnId).triggerHandler('actionStop');
				if (jsonData.status == true) {
					var $updatedElement = updateItem(actType, actOnId, jsonData.content);
					if (typeof($formContainer.dialog) == 'function') {
						$formContainer.dialog('close');
					}
					$formContainer.triggerHandler('submitSuccessful', [$updatedElement]);
				} else {
					// If an error occurs then redisplay the form
					$formContainer.html(jsonData.content);
					$formContainer.triggerHandler('submitFailed');
				}
			},
			"json"
		);
		validator = null;
	}
}

/**
 * Implements a generic ajax action.
 *
 * NB: Please make sure you correctly unbind previous ajax action events
 * before you call this method.
 *
 * @param {string} callingElement selector of the element that triggers the ajax call
 * @param {string} url the url to be called, defaults to the form action in case of
 *  action type 'post'.
 * @param {Object} data (post action type only) the data to be posted, defaults to
 *  the form data.
 * @param {string} eventName the name of the event that triggers the action, default 'click'.
 * @param {string} form the selector of a form element.
 */
function ajaxAction(actOnId, callingElement, url, data, eventName, form) {
	var eventHandler = function() {
		// jQuerify the form.
		var $form;
		if (form) {
			$form = $(form);
		} else {
			$form = $(actOnId).find('form');
		}

		// Set default url and data if none has been given.
		if (!url) {
			url = $form.attr("action");
		}
		if (!data) {
			data = $form.serialize();
		}

		// Validate
		var validator = $form.validate();

		// Post to server and construct callback
		if ($form.valid()) {
			var $actOnId = $(actOnId);
			$actOnId.triggerHandler('actionStart');
			$.post(
				url,
				data,
				function(jsonData) {
					$actOnId.triggerHandler('actionStop');
					if (jsonData !== null) {
						if (jsonData.status === true) {
							$actOnId.replaceWith(jsonData.content);
						} else {
							// Alert that the action failed
							alert(jsonData.content);
						}
					}
				},
				'json'
			);
			validator = null;
		}
		return false;
	};

	if (!eventName) eventName = 'click';
	$(callingElement).each(function() {
		// NB: We cannot unbind previous events here as this
		// may delete other legitimate events. Please make sure
		// you correctly unbind previous ajax action events
		// before you call this method. We also don't use
		// live() here because it doesn't support all required
		// selectors.
		$(this).bind(eventName, eventHandler);
	});
}

/**
 * Binds to the "actionStart" event to delete
 * the current contents of the actOnId
 * element and show a throbber instead.
 * @param {string} actOnId the element to be filled with the throbber image.
 */
function actionThrobber(actOnId) {
	$(actOnId)
		.bind('actionStart', function() {
			// Start throbber.
			$(this).unbind('actionStart').html('<div id="actionThrobber" class="deprecated_throbber"></div>');
			$('#actionThrobber').show();
		})
		.bind('actionStop', function() {
			// Remove all handlers.
			$(this).unbind('actionStart').unbind('actionStop');
		});
}

/**
 * Update the DOM of a grid or list depending on the action type.
 *
 * NB: This relies on an element with class "empty" being
 * present as a child of a table element. Make sure you use an
 * appropriate DOM for this to work.
 *
 * @param {string} actType one of the action type constants.
 * @param {string} actOnId Selector for the DOM element to be changed.
 * @param {string} content The content that replaces the current DOM element (replace or append types only)
 * @return jQuery the new or deleted element.
 */
function updateItem(actType, actOnId, content) {
	var updatedItem;
	switch (actType) {
		case 'append':
		case 'replace':
			var $empty = $(actOnId).closest('table').children('.empty');
			if (actType === 'append') {
				updatedItem = $(actOnId).append(content).children().last();
				$empty.hide();
			} else {
				updatedItem = $(actOnId).replaceWith(content);
				if ($(actOnId).children().length === 0) {
					$empty.show();
				} else {
					$empty.hide();
				}
			}
			break;

		case 'remove':
			if ($(actOnId).siblings().length == 0) {
				updatedItem = deleteElementById(actOnId, true);
			} else {
				updatedItem = deleteElementById(actOnId);
			}
			break;

		case 'nothing':
			updatedItem = null
			break;

		case 'redirect':
			// redirect to the content
			$(window.location).attr('href', content);
			updatedItem = null
			break;
	}

	// Trigger custom event so that clients can take
	// additional action.
	$(actOnId).triggerHandler('updatedItem', [actType]);
	return updatedItem;
}

/**
 * Deletes the given grid or list element from the DOM.
 *
 * NB: This relies on an element with class "empty" being
 * present as a child of a table element. Make sure you use an
 * appropriate DOM for this to work.
 *
 * @param {string} element a selector for the element to delete.
 * @param {boolean=} showEmpty whether to show the "empty" element.
 * @return {jQueryObject} the deleted element
 */
function deleteElementById(element, showEmpty) {
	var $deletedElement = $(element);
	if (showEmpty) {
		var $emptyPlaceholder = $deletedElement.closest('table').children('.empty');
	}
	$deletedElement.fadeOut(500, function() {
		$(this).remove();
		if (showEmpty) {
			$emptyPlaceholder.fadeIn(500);
		}
	});
	return $deletedElement;
}

/**
 * Implement the "extras on demand" design pattern for a list
 * of option blocks. Clicking on the header of the extras section
 * will toggle the section open and closed.
 * @param {string} actOnId a selector that contains the options header
 *  and options blocks.
 */
function extrasOnDemand(actOnId) {
	/**
	 * Shows the extra options.
	 */
	function activateExtraOptions() {
		// Hide the inactive version of the option header text.
		$(actOnId + ' .options-head .option-block-inactive').hide();
		// Show the active version of the option header text and the option blocks.
		$(actOnId + ' .options-head .option-block-active, ' + actOnId + ' .option-block').show();
		// Adapt styling of the option header.
		$(actOnId + ' .options-head').removeClass('inactive').addClass('active');
		// Change the header icon into a triangle pointing downwards.
		$(actOnId + ' .ui-icon').removeClass('ui-icon-triangle-1-e').addClass('ui-icon-triangle-1-s');
		// Scroll the parent so that all extra options are visible.
		scrollToMakeVisible(actOnId);
	}

	/**
	 * Hides the extra options.
	 */
	function deactivateExtraOptions() {
		// Hide the active version of the option header text and the option blocs.
		$(actOnId + ' .options-head .option-block-active, ' + actOnId + ' .option-block').hide();
		// Show the inactive version of the option header text.
		$(actOnId + ' .options-head .option-block-inactive').show();
		// Adapt styling of the option header.
		$(actOnId + ' .options-head').removeClass('active').addClass('inactive');
		// Change the header icon into a triangle pointing to the right.
		$(actOnId + ' .ui-icon').removeClass('ui-icon-triangle-1-s').addClass('ui-icon-triangle-1-e');
	}

	// De-activate the extra options on startup.
	deactivateExtraOptions();

	// Toggle the options when clicking on the header.
	$(actOnId + ' .options-head').click(function() {
		if ($(this).hasClass('active')) {
			deactivateExtraOptions();
		} else {
			activateExtraOptions();
		}
	});
}

/**
 * Scroll a scrollable element to make the
 * given content element visible. The content element
 * must be a descendant of a scrollable
 * element (needs to have class "scrollable").
 *
 * NB: This method depends on the position() method
 * to refer to the same parent element for both the
 * content element and the scrollable element.
 *
 * @param {string} actOnId a selector to identify
 *  the element to be made visible.
 */
function scrollToMakeVisible(actOnId) {
	// jQuerify the element to be made visible.
	var $contentBlock = $(actOnId);

	// Identify the scrollable element.
	var $scrollable = $contentBlock.closest('.scrollable');

	var contentBlockTop = $contentBlock.position().top;
	var scrollingBlockTop = $scrollable.position().top;
	var currentScrollingTop = $scrollable.scrollTop();

	// Do we have to scroll down or scroll up?
	if (contentBlockTop > scrollingBlockTop) {
		// Consider scrolling down...

		// Calculate the number of hidden pixels of the child
		// element within the scrollable element.
		var hiddenPixels = Math.ceil(contentBlockTop + $contentBlock.height() - $scrollable.height());

		// Scroll down if parts or all of the content block are hidden.
		if (hiddenPixels > 0) {
			$scrollable.scrollTop(currentScrollingTop + hiddenPixels);
		}
	} else {
		// Scroll up...

		// Calculate the new scrolling top.
		var newScrollingTop = Math.max(Math.floor(currentScrollingTop + contentBlockTop - scrollingBlockTop), 0);

		// Set the new scrolling top.
		$scrollable.scrollTop(newScrollingTop);
	}
}

(function($) {

	/**
	 * Custom jQuery plug-in that marks the matched elements
	 * Code adapted from phpBB, thanks to the phpBB group.
	 * @return {jQueryObject}
	 */
	$.fn.selectRange = function() {
		return this.each(function() {
			// Not IE
			if (window.getSelection) {
				var s = window.getSelection();
				// Safari
				if (s.setBaseAndExtent) {
					s.setBaseAndExtent(this, 0, this, this.innerText.length - 1);
				}
				// Firefox and Opera
				else {
					if (window.opera
							&& this.innerHTML.substring(this.innerHTML.length - 4) == '<BR>') {
						this.innerHTML = this.innerHTML + '&nbsp;';
					}

					var r = document.createRange();
					r.selectNodeContents(this);
					s.removeAllRanges();
					s.addRange(r);
				}
			}
			// Some older browsers
			else if (document.getSelection) {
				var s = document.getSelection();
				var r = document.createRange();
				r.selectNodeContents(this);
				s.removeAllRanges();
				s.addRange(r);
			}
			// IE
			else if (document.selection) {
				var r = document.body.createTextRange();
				r.moveToElementText(this);
				r.select();
			}
		});
	};


	/**
	 * Add a class to the <body> tag that identifies the browser
	 * to facilitate browser-specific CSS.
	 * Thanks to author Jon Hobbs-Smith who put this
	 * code in the public domain.
	 */
	$(function() {
		var userAgent = navigator.userAgent.toLowerCase();

		// Is this a version of IE?
		if (/msie/.test(userAgent)) {
			$('body').addClass('browserIE');

			// Add the version number
			userAgent = userAgent.substr(userAgent.indexOf('msie') + 5, 1);
			$('body').addClass('browserIE' + userAgent);
		}

		// Is this a version of Chrome?
		else if (/chrome/.test(userAgent)) {
			$('body').addClass('browserChrome');

			//Add the version number
			userAgent = userAgent.substr(userAgent.indexOf('chrome/') + 7, 1);
			$('body').addClass('browserChrome' + userAgent);
		}

		// Is this a version of Safari?
		else if (/safari/.test(userAgent)) {
			$('body').addClass('browserSafari');

			// Add the version number
			userAgent = userAgent.substr(userAgent.indexOf('version/') + 8, 1);
			$('body').addClass('browserSafari' + userAgent);
		}

		// Is this a version of Mozilla?
		else if (/mozilla/.test(userAgent)) {
			// Is it Firefox?
			if (/firefox/.test(userAgent)) {
				$('body').addClass('browserFirefox');

				// Add the version number
				userAgent = userAgent.substr(userAgent.indexOf('firefox/') + 8, 1);
				$('body').addClass('browserFirefox' + userAgent);
			}

			// If not then it must be another Mozilla
			else {
				$('body').addClass('browserMozilla');
			}
		}

		// Is this a version of Opera?
		else if (/opera/.test(userAgent)) {
			$('body').addClass('browserOpera');
		}

	});

	/**
	 * jQuery Hotkeys Plugin
	 * http://code.google.com/p/js-hotkeys/
	 *
	 * Copyright 2010, John Resig
	 * Dual licensed under the MIT or GPL Version 2 licenses.
	 *
	 * Based upon the plugin by Tzury Bar Yochay:
	 * http://github.com/tzuryby/hotkeys
	 *
	 * Original idea by:
	 * Binny V A, http://www.openjs.com/scripts/events/keyboard_shortcuts/
	 *
	 * Slightly adapted by the Public Knowledge Project,
	 * see copyright note and license terms in file header.
	 */
	$.hotkeys = {
		version: "0.8",

		specialKeys: {
			8: "backspace", 9: "tab", 13: "return", 16: "shift", 17: "ctrl", 18: "alt", 19: "pause",
			20: "capslock", 27: "esc", 32: "space", 33: "pageup", 34: "pagedown", 35: "end", 36: "home",
			37: "left", 38: "up", 39: "right", 40: "down", 45: "insert", 46: "del",
			96: "0", 97: "1", 98: "2", 99: "3", 100: "4", 101: "5", 102: "6", 103: "7",
			104: "8", 105: "9", 106: "*", 107: "+", 109: "-", 110: ".", 111 : "/",
			112: "f1", 113: "f2", 114: "f3", 115: "f4", 116: "f5", 117: "f6", 118: "f7", 119: "f8",
			120: "f9", 121: "f10", 122: "f11", 123: "f12", 144: "numlock", 145: "scroll", 191: "/", 224: "meta"
		},

		shiftNums: {
			"`": "~", "1": "!", "2": "@", "3": "#", "4": "$", "5": "%", "6": "^", "7": "&",
			"8": "*", "9": "(", "0": ")", "-": "_", "=": "+", ";": ": ", "'": "\"", ",": "<",
			".": ">",  "/": "?",  "\\": "|"
		}
	};

	/**
	 * @param {Object} handleObj
	 */
	function keyHandler( handleObj ) {
		// Only care when a possible input has been specified
		if ( typeof handleObj.data !== "string" ) {
			return;
		}

		var origHandler = handleObj.handler,
			keys = handleObj.data.toLowerCase().split(" ");

		handleObj.handler = function( event ) {
			// Keypress represents characters, not special keys
			var special = event.type !== "keypress" && $.hotkeys.specialKeys[ event.which ],
				character = String.fromCharCode( event.which ).toLowerCase(),
				key, modif = "", possible = {};

			// check combinations (alt|ctrl|shift+anything)
			if ( event.altKey && special !== "alt" ) {
				modif += "alt+";
			}

			if ( event.ctrlKey && special !== "ctrl" ) {
				modif += "ctrl+";
			}

			// TODO: Need to make sure this works consistently across platforms
			if ( event.metaKey && !event.ctrlKey && special !== "meta" ) {
				modif += "meta+";
			}

			if ( event.shiftKey && special !== "shift" ) {
				modif += "shift+";
			}

			if ( special ) {
				possible[ modif + special ] = true;

			} else {
				possible[ modif + character ] = true;
				possible[ modif + $.hotkeys.shiftNums[ character ] ] = true;

				// "$" can be triggered as "Shift+4" or "Shift+$" or just "$"
				if ( modif === "shift+" ) {
					possible[ $.hotkeys.shiftNums[ character ] ] = true;
				}
			}

			for ( var i = 0, l = keys.length; i < l; i++ ) {
				if ( possible[ keys[i] ] ) {
					return origHandler.apply( this, arguments );
				}
			}
		};
	}

	$.each([ "keydown", "keyup", "keypress" ], function() {
		$.event.special[ this ] = { add: keyHandler };
	});


}(jQuery));
