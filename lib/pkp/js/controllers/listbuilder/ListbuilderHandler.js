/**
 * @defgroup js_controllers_listbuilder
 */
/**
 * @file js/controllers/listbuilder/ListbuilderHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ListbuilderHandler
 * @ingroup js_controllers_listbuilder
 *
 * @brief Listbuilder row handler.
 */
(function($) {

	/** @type {Object} */
	$.pkp.controllers.listbuilder = $.pkp.controllers.listbuilder || {};



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.grid.GridHandler
	 *
	 * @param {jQueryObject} $listbuilder The listbuilder this handler is
	 *  attached to.
	 * @param {Object} options Listbuilder handler configuration.
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler =
			function($listbuilder, options) {
		this.parent($listbuilder, options);
	};
	$.pkp.classes.Helper.inherits($.pkp.controllers.listbuilder.ListbuilderHandler,
			$.pkp.controllers.grid.GridHandler);


	//
	// Private properties
	//
	/**
	 * The source type (LISTBUILDER_SOURCE_TYPE_...) of the listbuilder.
	 * @private
	 * @type {?number}
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.prototype.
			sourceType_ = null;


	/**
	 * The "save" URL of the listbuilder (for
	 * LISTBUILDER_SAVE_TYPE_INTERNAL).
	 * @private
	 * @type {?string}
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.prototype.
			saveUrl_ = null;


	/**
	 * The "save" field name of the listbuilder (for
	 * LISTBUILDER_SAVE_TYPE_EXTERNAL).
	 * @private
	 * @type {?string}
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.prototype.
			saveFieldName_ = null;


	/**
	 * The "fetch options" URL of the listbuilder (for "select" source type).
	 * @private
	 * @type {?string}
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.prototype.
			fetchOptionsUrl_ = null;


	/**
	 * Stores the calling context of the edit item click event.
	 * @private
	 * @type {HTMLElement}
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.
			prototype.editItemCallingContext_ = null;


	/**
	 * Flag whether there's still available options to be selected or not.
	 * @private
	 * @type {boolean}
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.
			prototype.availableOptions_ = false;


	//
	// Protected methods
	//
	/**
	 * @inheritDoc
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.prototype.initialize =
			function(options) {
		this.parent('initialize', options);

		// Save listbuilder options
		this.sourceType_ = options.sourceType;
		this.saveUrl_ = options.saveUrl;
		this.saveFieldName_ = options.saveFieldName;
		this.fetchOptionsUrl_ = options.fetchOptionsUrl;
		this.availableOptions_ = options.availableOptions;

		// Attach the button handlers
		var $listbuilder = this.getHtmlElement();
		// Use mousedown to avoid two events being triggered at the same time
		// (click event was being triggered together with blur event from inputs.
		// That and a syncronous ajax call triggered by those events
		// handlers, was leading to an error in IE8 and it was freezing
		// Firefox 13.0).
		$listbuilder.find('.actions .pkp_linkaction_addItem').mousedown(
				this.callbackWrapper(this.addItemHandler_));

		// Attach the content manipulation handlers
		this.attachContentHandlers_($listbuilder);

		// Sign up for notification of form submission.
		this.bind('formSubmitRequested', this.formSubmitHandler_);

		// Sign up for notification of form submitted.
		this.bind('formSubmitted', this.formSubmittedHandler_);
	};


	/**
	 * Get the "save" URL for LISTBUILDER_SAVE_TYPE_INTERNAL.
	 * @private
	 * @return {?string} URL to the "save listbuilder" handler operation.
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.prototype.getSaveUrl_ =
			function() {

		return this.saveUrl_;
	};


	/**
	 * Get the "save" field name for LISTBUILDER_SAVE_TYPE_EXTERNAL.
	 * @private
	 * @return {string} Name of the field to transmit LB contents in.
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.prototype.getSaveFieldName_ =
			function() {

		return /** @type {string} */ (this.saveFieldName_);
	};


	/**
	 * "Save" and close any editing rows in the listbuilder.
	 * @protected
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.prototype.closeEdits =
			function() {

		var $editedRow = this.getHtmlElement().find('.gridRowEdit:visible');
		if ($editedRow.length !== 0) {
			this.saveRow($editedRow);
			$editedRow.removeClass('gridRowEdit');
		}
	};


	/**
	 * Save the listbuilder.
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.prototype.save =
			function() {

		// Get deletions
		var deletions = this.getHtmlElement().find('input.deletions').val(),
				// Get insertions and modifications
				changes = [],
				numberOfRows, stringifiedData, saveUrl,
				saveFieldName, $e,
				handler = this;

		this.getHtmlElement().find('.gridRow input.isModified[value="1"]')
				.each(function(index, v) {
					var $row = $(v).parents('.gridRow'),
							params = handler.buildParamsFromInputs_($row.find(':input'));
					changes.push(params);
				});

		// The listbuilder form validator needs to know if this listbuilder contains
		// rows or not, so we pass the items number.
		numberOfRows = this.getRows().length;

		// Assemble and send to the server
		stringifiedData = JSON.stringify(
				{deletions: deletions, changes: changes, numberOfRows: numberOfRows});
		saveUrl = this.getSaveUrl_();
		if (saveUrl) {
			// Post the changes to the server using the internal
			// save handler.
			$.post(saveUrl, {data: stringifiedData},
					this.callbackWrapper(this.saveResponseHandler_, null), 'json');
		} else {
			// Supply the data to an external save handler (e.g.
			// a form handler) using a hidden field.
			saveFieldName = this.getSaveFieldName_();

			// Try to find and reuse an existing element (if
			// e.g. a previous attempt was aborted)
			$e = this.getHtmlElement()
					.find(':input[type=hidden]')
					.filter(
					function() {return $(this).attr('name') == saveFieldName;})
					.first();

			// If we couldn't find one, create one.
			if ($e.length === 0) {
				$e = $('<input type="hidden" />');
				$e.attr('name', saveFieldName);
				this.getHtmlElement().append($e);
			}

			// Set the value of the hidden element.
			$e.attr('value', stringifiedData);
		}
	};


	/**
	 * Function that will be called to save an edited row.
	 * @param {Object} $row The DOM element representing the row to save.
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.prototype.
			saveRow = function($row) {

		// Retrieve a single new row from the server.
		// (Avoid IE closure leak using this flag rather than passing
		// around a DOM element in a closure.)
		$row.addClass('saveRowResponsePlaceholder');
		var params = this.buildParamsFromInputs_($row.find(':input'));
		params.modify = true; // Flag the row for modification
		// Use a blocking request to avoid race conditions sometimes
		// duplicating items, i.e. when editing an existing item after
		// adding a new one.
		this.disableControls();
		$.ajax({
			url: this.getFetchRowUrl(),
			data: params,
			success: this.callbackWrapper(this.saveRowResponseHandler_, null),
			dataType: 'json',
			async: false
		});
	};


	//
	// Extended methods from GridHandler.
	//
	/**
	 * @inheritDoc
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.prototype.getEmptyElement =
			function($element) {
		// Listbuilders have only one empty element placeholder.
		return this.getHtmlElement().find('.empty');
	};


	//
	// Private Methods
	//
	/**
	 * Callback that will be activated when the "add item" icon is clicked
	 *
	 * @private
	 *
	 * @param {Object} callingContext The calling element or object.
	 * @param {Event=} opt_event The triggering event (e.g. a click on
	 *  a button.
	 * @return {boolean} Should return false to stop event processing.
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.prototype.addItemHandler_ =
			function(callingContext, opt_event) {

		if (this.availableOptions_) {
			// Make sure this event will be handled after any other next triggered one,
			// like blur event that comes from inputs.
			setTimeout(this.callbackWrapper(function() {
				// Close any existing edits if necessary
				this.closeEdits();

				this.disableControls();
				$.get(this.getFetchRowUrl(), {modify: true},
						this.callbackWrapper(this.appendRowResponseHandler_, null), 'json');
			}), 0);
		}

		return false;
	};


	/**
	 * Callback that will be activated when a delete icon is clicked
	 *
	 * @private
	 *
	 * @param {Object} callingContext The calling element or object.
	 * @param {Event=} opt_event The triggering event (e.g. a click on
	 *  a button.
	 * @return {boolean} Should return false to stop event processing.
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.prototype.deleteItemHandler_ =
			function(callingContext, opt_event) {

		// Close any existing edits if necessary
		this.closeEdits();

		var $callingContext = $(callingContext),
				$targetRow = $callingContext.closest('.gridRow'),
				$deletions = $callingContext.closest('.pkp_controllers_listbuilder')
						.find('.deletions'),
				rowId = $targetRow.find('input[name="rowId"]').val();

		// Append the row ID to the deletions list.
		if (rowId !== undefined) {
			$deletions.val($deletions.val() + ' ' + rowId);

			// Notify containing form (if any) about a change
			this.getHtmlElement().trigger('formChange');
		}

		this.deleteElement(/** @type {jQueryObject} */ ($targetRow));

		this.availableOptions_ = true;

		return false;
	};


	/**
	 * Callback that will be activated when a request for row appending
	 * returns.
	 *
	 * @private
	 *
	 * @param {Object} ajaxContext The AJAX request context.
	 * @param {Object} jsonData A parsed JSON response object.
	 * @return {boolean} Should return false to stop event processing.
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.prototype.
			appendRowResponseHandler_ = function(ajaxContext, jsonData) {

		var processedJsonData = this.handleJson(jsonData), $newRow;
		if (processedJsonData !== false) {
			// Show the new input row; hide the "empty" row
			$newRow = $(processedJsonData.content);
			this.getHtmlElement().find('.empty').hide().before($newRow);

			// Attach content handlers and focus
			this.attachContentHandlers_($newRow);
			$newRow.addClass('gridRowEdit');
			$newRow.find(':input').not('[type="hidden"]').first().focus();

			// If this is a select menu listbuilder, load the options
			if (this.sourceType_ == $.pkp.cons.LISTBUILDER_SOURCE_TYPE_SELECT) {
				this.disableControls();
				$.get(this.fetchOptionsUrl_, {},
						this.callbackWrapper(this.fetchOptionsResponseHandler_, null),
							'json');
			} else {
				this.enableControls();
			}

			this.callFeaturesHook('addElement', $newRow);
		}

		return false;
	};


	/**
	 * Callback that will be activated when a set of options is returned
	 * from the server for a new select control.
	 *
	 * @private
	 *
	 * @param {Object} ajaxContext The AJAX request context.
	 * @param {Object} jsonData A parsed JSON response object.
	 * @return {boolean} Should return false to stop event processing.
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.prototype.
			fetchOptionsResponseHandler_ = function(ajaxContext, jsonData) {

		// Find the currently editable select menu and fill
		var pjd = this.handleJson(jsonData),
				$listbuilder = this.getHtmlElement(),
				selectedValues = [],
				$selectInput,
				i, limit,
				$pulldown, $container, optionsCount, j,
				$option,
				label, $optgroup,
				k, optionsInsideGroup, $lastElement;

		if (pjd !== false) {
			// Get the list of already-selected options, to ensure
			// that we don't offer duplicates.
			$listbuilder.find('.gridCellDisplay :input').each(function(i, selected) {
				selectedValues[i] = $(selected).val();
			});

			// Get the currently available input row's elements
			$selectInput = $listbuilder.find(
					'.gridRowEdit:visible .selectMenu:input'
					);

			// For each pulldown (generally 1), add options.
			for (i = 0, limit = $selectInput.length; i < limit; i++) {
				// Fetch some useful properties
				$pulldown = $($selectInput[i]);
				$container = $pulldown.parents('.gridCellContainer');

				// Add the options, noting the currently selected index
				optionsCount = 0;
				$pulldown.children().empty();
				j = null;
				for (j in pjd.content[i]) {
					// Ignore optgroup labels.
					if (j == $.pkp.cons.LISTBUILDER_OPTGROUP_LABEL) {
						continue;
					}

					if (typeof(pjd.content[i][j]) == 'object') {
						// Options must go inside an optgroup.
						// Check if we have optgroup label data.
						if (
								pjd.
								content[i][$.pkp.cons.LISTBUILDER_OPTGROUP_LABEL] === undefined) {
							continue;
						}

						if (typeof(
								pjd.content[i][$.pkp.cons.LISTBUILDER_OPTGROUP_LABEL]
								) != 'object') {

							continue;
						}

						label =
								pjd.content[i][$.pkp.cons.LISTBUILDER_OPTGROUP_LABEL][j];
						if (!label) {
							continue;
						}

						$optgroup = $('<optgroup></optgroup>');
						$optgroup.attr('label', label);
						$pulldown.append($optgroup);

						k = null;
						optionsInsideGroup = 0;
						for (k in pjd.content[i][j]) {
							// Populate the optgroup.
							$option = this.populatePulldown_($optgroup,
									selectedValues, pjd.content[i][j][k], k);
							if ($option) {
								optionsCount++;
								optionsInsideGroup++;
							}
						}

						// Avoid inserting optgroups that have no option.
						if (optionsInsideGroup === 0) {
							$optgroup.remove();
						}
					} else {
						// Just insert the current option.
						$option = this.populatePulldown_($pulldown,
								selectedValues, pjd.content[i][j], j);
						if ($option) {
							optionsCount++;
						}
					}
				}

				$lastElement = $option;

				// If only one element is available, select it.
				if (optionsCount === 1 && $lastElement) {
					$lastElement.attr('selected', 'selected');
					this.availableOptions_ = false;
				}

				// If no options are available for this select menu,
				// hide the input to prevent empty dropdown.
				if (optionsCount === 0) {
					$container.find('.gridCellDisplay').show();
					$container.find('.gridCellEdit').hide();
				}
			}
		}

		this.enableControls();
		return false;
	};


	/**
	 * Populate the pulldown with options.
	 * @private
	 * @param {jQueryObject} $element The element to be populated.
	 * Can be a pulldown or an optgroup inside the pulldonw.
	 * @param {Object} selectedValues Current listbuilder
	 * selected values.
	 * @param {string} optionText The text to populate the pulldown with.
	 * @param {string} optionValue The key to populate the pulldown with.
	 * @return {Object|boolean} Return the inserted option or false.
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.prototype.
			populatePulldown_ = function(
			$element, selectedValues, optionText, optionValue) {

		var $container = $element.parents('.gridCellContainer'),
				currentIndex = $container.find('.gridCellDisplay :input').val(),
				isDuplicate = false, k,
				$option;

		// Check to see if this option is already in the LB.
		if (optionValue != currentIndex) {
			// If it's the current row, don't consider it a duplicate
			for (k = 0; k < selectedValues.length; k++) {
				if (selectedValues[k] == optionValue) {
					isDuplicate = true;
				}
			}
		}

		if (!isDuplicate) {
			// Create and populate the option node
			$option = $('<option/>');
			$option.attr('value', optionValue);
			$option.text(optionText);

			if (optionValue == currentIndex) {
				$option.attr('selected', 'selected');
			}

			$element.append($option);
			return $option;
		} else {
			return false;
		}
	};


	/**
	 * Callback that will be activated when a row is clicked for editing
	 *
	 * @private
	 *
	 * @param {HTMLElement} callingContext The calling element or object.
	 * @param {Event=} opt_event The triggering event (e.g. a click on
	 *  a button.
	 * @return {boolean} Should return false to stop event processing.
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.prototype.editItemHandler_ =
			function(callingContext, opt_event) {

		// Close any existing edits if necessary
		this.closeEdits();
		this.editItemCallingContext_ = callingContext;

		// Show inputs; hide display. IE8 is slow, and it will execute
		// this before the timeout setted in inputBlurHandler_. Insert this
		// code inside a timeout too to avoid closing inputs that are not
		// meant to.
		setTimeout(this.callbackWrapper(function() {
			var $targetRow = $(this.editItemCallingContext_).closest('.gridRow');
			$targetRow.addClass('gridRowEdit');
			$targetRow.find(':input').not('[type="hidden"]').first().focus();

			// If this is a select menu listbuilder, load the options
			if (this.sourceType_ == $.pkp.cons.LISTBUILDER_SOURCE_TYPE_SELECT) {
				this.disableControls();
				$.get(this.fetchOptionsUrl_, {},
						this.callbackWrapper(this.fetchOptionsResponseHandler_, null),
							'json');
			}
		}), 0);

		return false;
	};


	/**
	 * Helper function to turn a row into an array of parameters used
	 * to generate the DOM representation of that row when bounced
	 * off the server.
	 *
	 * @private
	 *
	 * @param {Object} $inputs The grid inputs to mine for parameters.
	 * @return {Object} A name: value association of relevant parameters.
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.prototype.
			buildParamsFromInputs_ = function($inputs) {

		var params = {};
		$.each($inputs.serializeArray(), function(k, v) {
			var name = v.name,
					value = v.value;

			params[name] = params[name] === undefined ? value :
					$.isArray(params[name]) ? params[name].concat(value) :
					[params[name], value];
		});

		return params;
	};


	/**
	 * Callback that will be activated upon keystroke in a new input field
	 * to check for a <cr> (acts as tab-to-next, or if no next, submit).
	 *
	 * @private
	 *
	 * @param {HTMLElement} callingContext The calling element or object.
	 * @param {Event=} opt_event The triggering event.
	 * @return {boolean} Should return false to stop event processing.
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.prototype.
			inputKeystrokeHandler_ = function(callingContext, opt_event) {

		var CR_KEY = 13, TAB_KEY = 9,
				$target, $row, $inputs, i;

		if (opt_event.which == CR_KEY) {
			$target = $(callingContext);
			$row = $target.parents('.gridRow');
			$inputs = $row.find(':input:visible');
			i = $inputs.index($target);
			if ($inputs.length == i + 1) {
				this.saveRow($row);
				return false; // Prevent default
			} else {
				// Not the last field. Tab to the next.
				$inputs[i + 1].focus();
				return false; // Prevent default
			}
		}
		return true;
	};


	/**
	 * Callback that will be activated when a new/modifying row's input
	 * field is blurred to check whether or not to save the row.
	 *
	 * @private
	 *
	 * @param {HTMLElement} callingContext The calling element or object.
	 * @param {Event=} opt_event The triggering event.
	 * @return {boolean} Should return false to stop event processing.
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.prototype.
			inputBlurHandler_ = function(callingContext, opt_event) {

		// Flag currently selected input using a CSS class. (Don't
		// want to pass it into the closure because of the IE memory
		// leak bug.)
		$(callingContext).closest('.gridRow').addClass('editingRowPlaceholder');

		// Check to see whether the row has lost focus after this event has
		// been processed.
		setTimeout(this.callbackWrapper(function() {
			var $editingRow = $('.editingRowPlaceholder'),
					found = false;
			$editingRow.find(':input').each(function(index, elem) {
				if (elem === document.activeElement) {
					found = true;
				}
			});

			// Clean up extra placeholder class.
			$editingRow.removeClass('editingRowPlaceholder');

			// If the focused element isn't within the current row, save.
			if (!found) {
				this.closeEdits();
			}
		}), 0);

		return true;
	};


	/**
	 * Callback to replace a grid row's content.
	 *
	 * @private
	 *
	 * @param {Object} ajaxContext The AJAX request context.
	 * @param {Object} jsonData A parsed JSON response object.
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.prototype.
			saveRowResponseHandler_ = function(ajaxContext, jsonData) {

		var processedJsonData = this.handleJson(jsonData),
				$newContent, rowId;

		if (processedJsonData !== false) {
			// Unfortunately we can't use a closure to get this from
			// the calling context. Use a class flag "saveRowResponsePlaceholder".
			// (Risks IE closure/DOM element memory leak.)
			$newContent = $(processedJsonData.content);

			// Store current row id.
			rowId = /** @type {string} */ (this.getHtmlElement()
					.find('.saveRowResponsePlaceholder').attr('id'));

			// Add to the DOM
			this.getHtmlElement().find('.saveRowResponsePlaceholder').
					replaceWith($newContent);

			// Make sure row id won't change.
			$newContent.attr('id', rowId);

			// Attach handlers for content manipulation
			this.attachContentHandlers_($newContent);

			this.callFeaturesHook('replaceElement', $newContent);
		}

		// Ensure that containing forms are notified of the changed data
		this.getHtmlElement().trigger('formChange');

		this.enableControls();
	};


	/**
	 * Callback after a save response returns from the server.
	 *
	 * @private
	 *
	 * @param {Object} ajaxContext The AJAX request context.
	 * @param {Object} jsonData A parsed JSON response object.
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.prototype.
			saveResponseHandler_ = function(ajaxContext, jsonData) {

		// Noop
	};


	/**
	 * Attach content handlers to all "click-to-edit" content within
	 * the provided context.
	 *
	 * @private
	 *
	 * @param {Object} $context The JQuery object to search for attachables.
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.prototype.
			attachContentHandlers_ = function($context) {

		// Attach click handler for text fields and select menus
		$context.find('.gridCellDisplay').click(
				this.callbackWrapper(this.editItemHandler_));

		// Attach keypress handler for text fields
		$context.find(':input')
				.keypress(this.callbackWrapper(this.inputKeystrokeHandler_))
				.blur(this.callbackWrapper(this.inputBlurHandler_));

		// Attach deletion handler
		$context.find('.pkp_linkaction_delete').click(
				this.callbackWrapper(this.deleteItemHandler_));
	};


	/**
	 * Save the Listbuilder's contents upon a "form submitted" event.
	 * @private
	 *
	 * @param {$.pkp.controllers.form.AjaxFormHandler} callingForm The form
	 *  that triggered the event.
	 * @param {Event} event The event.
	 * @return {boolean} False if the form submission should abort.
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.
			prototype.formSubmitHandler_ = function(callingForm, event) {

		// Save the contents
		this.save();

		// Prevent the submission of LB elements to the parent form
		// (except potentially for :input[name='getSaveFieldName()'])
		this.getHtmlElement().find('.gridRow :input').attr('disabled', 'disabled');

		// Continue the default (form submit) behavior
		return true;
	};


	/**
	 * Enable deactivated inputs.
	 * @private
	 *
	 * @param {$.pkp.controllers.form.AjaxFormHandler} callingForm The form
	 *  that triggered the event.
	 * @param {Event} event The event.
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.
			prototype.formSubmittedHandler_ = function(callingForm, event) {

		this.getHtmlElement().find('.gridRow :input').removeAttr('disabled');
	};


	/**
	 * Disable the add_* links and show the spinner before making AJAX calls.
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.
			prototype.disableControls = function() {

		this.getHtmlElement().
				find('span[class="options"] > a[id*="addItem"]').unbind('mousedown');

		this.getHtmlElement().find('span[class="options"] > a[id*="addItem"]')
				.mousedown(function() {return false;});
		this.getHtmlElement().find('.h3').addClass('spinner');
	};


	/**
	 * Re-enable the add_* links and hide the spinner.
	 */
	$.pkp.controllers.listbuilder.ListbuilderHandler.
			prototype.enableControls = function() {
		// rebind our 'click' handler so we can add another item
		// if needed
		this.getHtmlElement().find('span[class="options"] > a[id*="addItem"]').
				mousedown(this.callbackWrapper(this.addItemHandler_));
		this.getHtmlElement().find('.h3').removeClass('spinner');
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
