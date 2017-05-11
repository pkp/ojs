/**
 * @defgroup js_controllers_wizard
 */
/**
 * @file js/controllers/wizard/WizardHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class WizardHandler
 * @ingroup js_controllers_wizard
 *
 * @brief Basic wizard handler.
 */
(function($) {

	/** @type {Object} */
	$.pkp.controllers.wizard = $.pkp.controllers.wizard || { };



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.TabHandler
	 *
	 * @param {jQueryObject} $wizard A wrapped HTML element that
	 *  represents the wizard.
	 * @param {{
	 *  enforceLinear: boolean,
	 *  cancelButtonText: string,
	 *  continueButtonTest: string,
	 *  finishButtonText: string
	 *  }} options options to configure the form handler.
	 */
	$.pkp.controllers.wizard.WizardHandler = function($wizard, options) {
		this.parent($wizard, options);

		// Add the wizard buttons
		this.addWizardButtons_($wizard, options);

		this.enforceLinear_ = options.hasOwnProperty('enforceLinear') ?
				options.enforceLinear : true;

		// Start the wizard.
		this.startWizard();

		// Bind the wizard events to handlers.
		this.bindWizardEvents();

		// Assume that we usually have forms in the wizard
		// tabs and bind to form events.
		this.bind('formValid', this.formValid);
		this.bind('formInvalid', this.formInvalid);
		this.bind('formSubmitted', this.formSubmitted);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.wizard.WizardHandler, $.pkp.controllers.TabHandler);


	//
	// Private properties
	//
	/**
	 * The continue button.
	 * @private
	 * @type {jQueryObject?}
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.$continueButton_ = null;


	/**
	 * The progress indicator.
	 * @private
	 * @type {jQueryObject?}
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.$progressIndicator_ = null;


	/**
	 * The continue button label.
	 * @private
	 * @type {?string}
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.continueButtonText_ = null;


	/**
	 * The finish button label.
	 * @private
	 * @type {?string}
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.finishButtonText_ = null;


	/**
	 * Whether or not to enforce linear progress through the wizard.
	 * @private
	 * @type {?boolean}
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.enforceLinear_ = null;


	//
	// Private methods
	//
	/**
	 * Show the loading spinner
	 *
	 * @private
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.showProgressIndicator_ =
			function() {
		this.getProgressIndicator().css('opacity', 1);
	};


	/**
	 * Hide the loading spinner
	 *
	 * @private
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.hideProgressIndicator_ =
			function() {
		this.getProgressIndicator().css('opacity', 0);
	};


	//
	// Public methods
	//
	/**
	 * Handle the user's request to advance the wizard.
	 *
	 * NB: Please do not override this method. This is an internal event
	 * handler. Override the wizardAdvanceRequested() and wizardAdvance()
	 * event handlers instead if you want to provide custom behavior.
	 *
	 * @param {HTMLElement} buttonElement The button that triggered the event.
	 * @param {Event} event The triggered event.
	 * @return {boolean} Should return false to stop event propagation.
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.continueRequest =
			function(buttonElement, event) {

		// Trigger the "advance requested" event on the current
		// tab's children to give it a chance to veto the advance
		// request.
		var advanceRequestedEvent = new $.Event('wizardAdvanceRequested');
		this.getCurrentTab().children().first().trigger(advanceRequestedEvent);

		// Advance the wizard if the advanceRequestEvent handler didn't
		// prevent it.
		if (!advanceRequestedEvent.isDefaultPrevented()) {
			this.advanceOrClose_();
		}
		return false;
	};


	/**
	 * Handle "form valid" events that may be triggered by forms in the
	 * wizard tab.
	 *
	 * @param {HTMLElement} formElement The form that triggered the event.
	 * @param {Event} event The triggered event.
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.formValid =
			function(formElement, event) {

		// The default implementation enables the continue button
		// as soon as the form validates.
		this.enableContinueButton();
	};


	/**
	 * Handle "form invalid" events that may be triggered by forms in the
	 * wizard tab.
	 *
	 * @param {HTMLElement} formElement The form that triggered the event.
	 * @param {Event} event The triggered event.
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.formInvalid =
			function(formElement, event) {

		// The default implementation disables the continue button
		// as if the form no longer validates.
		this.disableContinueButton();
	};


	/**
	 * Handle "form submitted" events that may be triggered by forms in the
	 * wizard tab.
	 *
	 * @param {HTMLElement} formElement The form that triggered the event.
	 * @param {Event} event The triggered event.
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.formSubmitted =
			function(formElement, event) {

		// The default implementation advances the wizard.
		this.advanceOrClose_();
	};


	/**
	 * Handle the user's request to cancel the wizard.
	 *
	 * NB: Please do not override this method. This is an internal event
	 * handler. Override the wizardCancel() event handler instead if you
	 * want to provide custom behavior.
	 *
	 * @param {HTMLElement} buttonElement The button that triggered the event.
	 * @param {Event} event The triggered event.
	 * @return {boolean} Should return false to stop event propagation.
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.cancelRequest =
			function(buttonElement, event) {

		// This is a 'cancel' click, so unregister forms without prompting.
		this.checkForm_(false);

		// Trigger the "cancel requested" event on the current
		// tab's children to give it a chance to veto the cancel
		// request.
		var cancelRequestedEvent = new $.Event('wizardCancelRequested');
		this.getCurrentTab().children().first().trigger(cancelRequestedEvent);

		// Trigger the wizardCancel event if the
		// cancelRequestEvent handler didn't prevent it.
		if (!cancelRequestedEvent.isDefaultPrevented()) {
			this.trigger('wizardCancel');
		}
		return false;
	};


	/**
	 * Handle the wizard "cancel requested" event.
	 *
	 * Please override this method to clean up before the wizard is
	 * being canceled. You can execute event.preventDefault() if you
	 * don't want the wizard to cancel.
	 *
	 * NB: This is a fallback handler that will be called if no other
	 * event handler calls the event.stopPropagation() method.
	 *
	 * @param {HTMLElement} wizardElement The wizard's HTMLElement on
	 *  which the event was triggered.
	 * @param {Event} event The triggered event.
	 * @return {boolean} Return false if not overridden and if check form
	 * returns true.
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.wizardCancelRequested =
			function(wizardElement, event) {

		if (this.checkForm_(true)) {
			// User doesn't wants to leave. Return false to stop cancel request.
			return false;
		}

		// User wants to leave or there is no form inside this wizard.
		return true;
	};


	/**
	 * Handle the wizard "advance requested" event.
	 *
	 * Please override this method to make custom validation checks or
	 * place server requests before you let the wizard advance to the next
	 * step. You can execute event.preventDefault() if you don't want
	 * the wizard to advance because you encountered errors during
	 * validation.
	 *
	 * NB: This is a fallback handler that will be called if no other
	 * event handler calls the event.stopPropagation() method.
	 *
	 * @param {HTMLElement} wizardElement The wizard's HTMLElement on
	 *  which the event was triggered.
	 * @param {Event} event The triggered event.
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.wizardAdvanceRequested =
			function(wizardElement, event) {

		// If we find a form then submit it.
		var $form = this.getForm_();
		if ($form) {
			// Try to submit the form.
			if ($form.submit()) {
				this.disableContinueButton();
				this.showProgressIndicator_();
			}

			// Prevent default event handling so that the form
			// can do its validation checks first.
			event.preventDefault();
		}
	};


	/**
	 * Handle the "wizard advance" event. The default implementation
	 * advances the wizard to the next step and disables the previous step.
	 *
	 * In most cases you probably don't want to override this method unless
	 * you want to provide a different navigation experience. Form validation
	 * and submission or similar tasks should be done in the
	 * wizardAdvanceRequested() event handler.
	 *
	 * @param {HTMLElement} wizardElement The wizard's HTMLElement on
	 *  which the event was triggered.
	 * @param {Event} event The triggered event.
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.wizardAdvance =
			function(wizardElement, event) {

		// The wizard can only be advanced one step at a time.
		// The step cannot be greater than the number of wizard
		// tabs and not less than 1.
		var currentStep = this.getCurrentStep(),
				lastStep = this.getNumberOfSteps() - 1,
				targetStep = currentStep + 1,
				$wizard, $continueButton;

		// Do not advance beyond the last step.
		if (targetStep > lastStep) {
			throw new Error('Trying to set an invalid wizard step!');
		}

		// Enable the target step.
		$wizard = this.getHtmlElement();
		$wizard.tabs('enable', targetStep);

		// Advance to the target step.
		$wizard.tabs('option', 'active', targetStep);

		if (this.enforceLinear_) {
			// Disable the previous step.
			$wizard.tabs('disable', currentStep);
		}

		// If this is the last step then change the text on the
		// continue button to finish.
		$continueButton = this.getContinueButton();
		if (targetStep === lastStep) {
			$continueButton.text(
					/** @type {string} */ (this.getFinishButtonText()));
		}

		this.hideProgressIndicator_();
		this.enableContinueButton();
	};


	//
	// Protected methods
	//
	/**
	 * (Re-)Start the wizard.
	 * @protected
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.startWizard = function() {

		// Retrieve the wizard element.
		var $wizard = this.getHtmlElement(),
				$continueButton, disabledSteps, i;

		// Do we re-start the wizard?
		if (this.getCurrentStep() !== 0) {
			// Make sure that the first step is enabled, otherwise
			// we cannot select it.
			$wizard.tabs('enable', 0);

			// Go to the first step.
			$wizard.tabs('option', 'active', 0);

			// Reset the continue button label.
			$continueButton = this.getContinueButton();
			$continueButton.text(
					/** @type {string} */ (this.getContinueButtonText()));
		}

		if (this.enforceLinear_) {
			// Disable all but the first step.
			disabledSteps = [];
			for (i = 1; i < this.getNumberOfSteps(); i++) {
				disabledSteps.push(i);
			}
			$wizard.tabs('option', 'disabled', disabledSteps);
		}
	};


	/**
	 * Bind wizard events to default event handlers.
	 * @protected
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.bindWizardEvents = function() {
		this.bind('wizardCancelRequested', this.wizardCancelRequested);
		this.bind('wizardAdvanceRequested', this.wizardAdvanceRequested);
		this.bind('wizardAdvance', this.wizardAdvance);
	};


	/**
	 * Get the current wizard step.
	 * @protected
	 * @return {number} The current wizard step.
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.
			getCurrentStep = function() {

		return this.getCurrentTabIndex();
	};


	/**
	 * Get the continue button.
	 * @protected
	 * @return {jQueryObject} The continue button.
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.
			getContinueButton = function() {

		return this.$continueButton_;
	};


	/**
	 * Get the progress indicator.
	 * @protected
	 * @return {jQueryObject} The progress indicator.
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.
			getProgressIndicator = function() {

		return this.$progressIndicator_;
	};


	/**
	 * Get the continue button label.
	 * @protected
	 * @return {?string} The text to display on the continue button.
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.
			getContinueButtonText = function() {

		return this.continueButtonText_;
	};


	/**
	 * Get the finish button label.
	 * @protected
	 * @return {?string} The text to display on the continue button
	 *  in the last wizard step.
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.
			getFinishButtonText = function() {

		return this.finishButtonText_;
	};


	/**
	 * Count the wizard steps.
	 * @return {number} The current number of wizard steps.
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.
			getNumberOfSteps = function() {

		var $wizard = this.getHtmlElement();
		return $wizard.find('ul').first().children().length;
	};


	//
	// Private methods
	//
	/**
	 * Return the current form (if any).
	 *
	 * @private
	 * @return {jQueryObject?} The form (if any).
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.getForm_ = function() {
		var i, $element, $tabContent;

		// If we find a form in the current tab then return it.
		$tabContent = this.getCurrentTab().children();
		for (i = 0; i < $tabContent.length; i++) {
			$element = $($tabContent[i]);
			if ($element.is('form')) {
				return $element;
			}
		}

		return null;
	};


	/**
	 * Continue to the next step or, if this is the last step,
	 * then close the wizard.
	 *
	 * @private
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.advanceOrClose_ =
			function() {
		var currentStep = this.getCurrentStep(),
				lastStep = this.getNumberOfSteps() - 1;

		if (currentStep < lastStep) {
			this.trigger('wizardAdvance');
		} else {
			this.trigger('wizardClose');
		}
	};


	/**
	 * Helper method to look for changed forms.
	 *
	 * @param {boolean} prompt Whether or not to prompt.
	 * @return {boolean} Whether or not they wish to cancel. If no form is
	 * available in wizard, also return false.
	 * @private
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.checkForm_ =
			function(prompt) {
		var $form = this.getForm_(),
				handler;
		if ($form !== null) {
			handler = $.pkp.classes.Handler.getHandler($('#' + $form.attr('id')));
			if (prompt) {
				if (handler.formChangesTracked) {
					if (!confirm($.pkp.locale.form_dataHasChanged)) {
						return true; // the user has clicked cancel, they wish to stay.
					} else {
						handler.unregisterForm();
					}
				}
			} else {
				handler.unregisterForm();
			}
		}
		return false;
	};


	/**
	 * Add wizard buttons to the wizard.
	 *
	 * @private
	 * @param {jQueryObject} $wizard The wizard element.
	 * @param {Object} options The wizard options.
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.addWizardButtons_ =
			function($wizard, options) {

		// Add space before wizard buttons.
		var $wizardButtons =
				$('<div id="wizardButtons" class="modal_buttons"></div>'),
				$cancelButton, $continueButton, $progressIndicator;

		if (options.continueButtonText) {
			// Add continue/finish button.
			$continueButton = $(
					'<button id="continueButton" class="pkp_button"></button>')
					.text(options.continueButtonText);
			$wizardButtons.append($continueButton);

			$progressIndicator = $(
					'<span class="pkp_spinner"></span>');
			$wizardButtons.append($progressIndicator);

			$continueButton.
					// Attach the continue request handler.
					bind('click',
							this.callbackWrapper(this.continueRequest));
			this.$continueButton_ = /** @type {jQueryObject} */ $continueButton;
			this.$progressIndicator_ = $progressIndicator;

			// Remember the button labels.
			this.continueButtonText_ = options.continueButtonText;
			if (options.finishButtonText) {
				this.finishButtonText_ = options.finishButtonText;
			} else {
				this.finishButtonText_ = options.continueButtonText;
			}
		}

		if (options.cancelButtonText) {
			// Add cancel button.
			$cancelButton = $('<a id="cancelButton" class="cancel" href="#"></a>')
					.text(options.cancelButtonText);
			$wizardButtons.append($cancelButton);

			// Attach the cancel request handler.
			$cancelButton.bind('click',
					this.callbackWrapper(this.cancelRequest));
		}

		// Insert wizard buttons.
		$wizard.after($wizardButtons);
	};


	/**
	 * Disable the continue button
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.disableContinueButton =
			function() {
		this.getContinueButton().attr('disabled', 'disabled');
	};


	/**
	 * Enable the continue button
	 */
	$.pkp.controllers.wizard.WizardHandler.prototype.enableContinueButton =
			function() {
		this.getContinueButton().removeAttr('disabled');
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
