/**
 * @defgroup js_controllers_wizard_fileUpload_form
 */
/**
 * @file js/controllers/wizard/fileUpload/form/FileUploadFormHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FileUploadFormHandler
 * @ingroup js_controllers_wizard_fileUpload_form
 *
 * @brief File upload tab handler.
 */
(function($) {

	/** @type {Object} */
	$.pkp.controllers.wizard.fileUpload.form =
			$.pkp.controllers.wizard.fileUpload.form || { };



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.form.AjaxFormHandler
	 *
	 * @param {jQueryObject} $form The wrapped HTML form element.
	 * @param {Object} options Form validation options.
	 */
	$.pkp.controllers.wizard.fileUpload.form.FileUploadFormHandler =
			function($form, options) {

		this.parent($form, options);

		// Set internal state properties.
		this.hasFileSelector_ = options.hasFileSelector;
		this.hasGenreSelector_ = options.hasGenreSelector;

		if (options.presetRevisedFileId) {
			this.presetRevisedFileId_ = options.presetRevisedFileId;
		}
		this.fileGenres_ = options.fileGenres;

		this.$uploader_ = options.$uploader;

		// Attach the uploader handler to the uploader HTML element.
		this.attachUploader_(this.$uploader_, options.uploaderOptions);

		this.uploaderSetup(options.$uploader);

		// Enable/disable the uploader and genre selection based on selection
		this.$revisedFileSelector_ = $form.find('#revisedFileId')
				.change(this.callbackWrapper(this.revisedFileChange));
		if (this.hasGenreSelector_) {
			this.$genreSelector = $form.find('#genreId')
					.change(this.callbackWrapper(this.genreChange));
		}

		this.setUploaderVisibility_();
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.wizard.fileUpload.form.FileUploadFormHandler,
			$.pkp.controllers.form.AjaxFormHandler);


	//
	// Private properties
	//
	/**
	 * Whether the file upload form has a file selector.
	 * @private
	 * @type {boolean}
	 */
	$.pkp.controllers.wizard.fileUpload.form.FileUploadFormHandler
			.hasFileSelector_ = false;


	/**
	 * The file upload form's file selector if available.
	 * @private
	 * @type {boolean?}
	 */
	$.pkp.controllers.wizard.fileUpload.form.FileUploadFormHandler
			.$revisedFileSelector_ = null;


	/**
	 * Whether the file upload form has a genre selector.
	 * @private
	 * @type {boolean}
	 */
	$.pkp.controllers.wizard.fileUpload.form.FileUploadFormHandler
			.hasGenreSelector_ = false;


	/**
	 * The file upload form's genre selector if available.
	 * @private
	 * @type {boolean?}
	 */
	$.pkp.controllers.wizard.fileUpload.form.FileUploadFormHandler
			.$genreSelector_ = null;


	/**
	 * A preset revised file id (if any).
	 * @private
	 * @type {?string}
	 */
	$.pkp.controllers.wizard.fileUpload.form.FileUploadFormHandler
			.presetRevisedFileId_ = null;


	/**
	 * All currently available file genres.
	 * @private
	 * @type {Object}
	 */
	$.pkp.controllers.wizard.fileUpload.form.FileUploadFormHandler
			.fileGenres_ = null;


	/**
	 * A jQuery object referencing the DOM element plupload is attached to
	 * @private
	 * @type {Object}
	 */
	$.pkp.controllers.wizard.fileUpload.form.FileUploadFormHandler
			.$uploader_ = null;


	//
	// Public methods
	//
	/**
	 * The setup callback of the uploader.
	 * @param {jQueryObject} $uploader Element that contains the plupload object.
	 */
	$.pkp.controllers.wizard.fileUpload.form.FileUploadFormHandler.prototype.
			uploaderSetup = function($uploader) {

		var uploadHandler = $.pkp.classes.Handler.getHandler($uploader);

		// Subscribe to uploader events.
		uploadHandler.pluploader.bind('BeforeUpload',
				this.callbackWrapper(this.prepareFileUploadRequest));
		uploadHandler.pluploader.bind('FileUploaded',
				this.callbackWrapper(this.handleUploadResponse));
		uploadHandler.pluploader.bind('FilesRemoved',
				this.callbackWrapper(this.handleRemovedFiles));
	};


	/**
	 * Prepare the request parameters for the file upload request.
	 * @param {Object} caller The original context in which the callback was called.
	 * @param {Object} pluploader The pluploader object.
	 */
	$.pkp.controllers.wizard.fileUpload.form.FileUploadFormHandler.prototype.
			prepareFileUploadRequest = function(caller, pluploader) {

		var $uploadForm = this.getHtmlElement(),
				multipartParams = { },
				// Add the uploader user group id.
				$uploaderUserGroupId = $uploadForm.find('#uploaderUserGroupId');

		$uploaderUserGroupId.attr('disabled', 'disabled');
		multipartParams.uploaderUserGroupId = $uploaderUserGroupId.val();

		// Add the revised file to the upload message.
		if (this.hasFileSelector_) {
			this.$revisedFileSelector_.attr('disabled', 'disabled');
			multipartParams.revisedFileId = this.$revisedFileSelector_.val();
		} else {
			if (this.presetRevisedFileId_ !== null) {
				multipartParams.revisedFileId = this.presetRevisedFileId_;
			} else {
				multipartParams.revisedFileId = 0;
			}
		}

		// Add the file genre to the upload message.
		if (this.hasGenreSelector_) {
			this.$genreSelector.attr('disabled', 'disabled');
			multipartParams.genreId = this.$genreSelector.val();
		} else {
			multipartParams.genreId = '';
		}

		// Add the upload message parameters to the uploader.
		pluploader.settings.multipart_params = multipartParams;
	};


	/**
	 * Handle the response of a "file upload" request.
	 * @param {Object} caller The original context in which the callback was called.
	 * @param {Object} pluploader The pluploader object.
	 * @param {Object} file The data of the uploaded file.
	 * @param {{response: string}} ret The serialized JSON response.
	 */
	$.pkp.controllers.wizard.fileUpload.form.FileUploadFormHandler.prototype.
			handleUploadResponse = function(caller, pluploader, file, ret) {

		// Handle the server's JSON response.
		var jsonData = this.handleJson($.parseJSON(ret.response)),
				$uploadForm = this.getHtmlElement();

		if (jsonData !== false) {
			// Trigger the file uploaded event.
			this.trigger('fileUploaded', jsonData.uploadedFile);

			// Display the revision confirmation form.
			if (jsonData.content !== '') {
				this.getHtmlElement().replaceWith(jsonData.content);
			}
		}

		// Trigger validation on the form. This doesn't happen automatically
		// until `blur` is triggered on the file input field, requiring the
		// user to click before any disabled form functions become available.
		this.getHtmlElement().valid();
	};


	/**
	 * Pass the `FilesRemoved` event from plupload on to FileUploadWizardHandler
	 * so it can delete the file.
	 *
	 * TODO this is necessary because only the FileUploadWizardHandler knows
	 *  the delete URL. But other file upload utilities could benefit from this
	 *  feature, so it would be best to internalize this functionality in the
	 *  UploadHandler by passing in a deleteURL option. This is a task that
	 *  should be handled when the file upload process is rewritten to support
	 *  a multi-file upload workflow.
	 * @param {Object} caller The original context in which the callback was called.
	 * @param {Object} pluploader The pluploader object.
	 * @param {Object} file The data of the uploaded file.
	 */
	$.pkp.controllers.wizard.fileUpload.form.FileUploadFormHandler.prototype.
			handleRemovedFiles = function(caller, pluploader, file) {
		this.trigger('filesRemoved', [pluploader, file]);
	};


	/**
	 * Internal callback to handle form submission.
	 *
	 * @param {Object} validator The validator plug-in.
	 * @param {HTMLElement} formElement The wrapped HTML form.
	 */
	$.pkp.controllers.wizard.fileUpload.form.FileUploadFormHandler.prototype.
			submitForm = function(validator, formElement) {

		// There is no form to submit (file already uploaded).
		// Trigger event to signal that user requests the form to be submitted.
		this.trigger('formSubmitted');
	};


	/**
	 * Handle the "change" event of the revised file selector.
	 * @param {HTMLElement} revisedFileElement The original context in
	 *  which the event was triggered.
	 * @param {Event} event The change event.
	 * @return {boolean} Event handling status.
	 */
	$.pkp.controllers.wizard.fileUpload.form.FileUploadFormHandler.prototype.
			revisedFileChange = function(revisedFileElement, event) {

		// Enable/disable the genre field when a revision is selected
		if (!this.$revisedFileSelector_.val()) {
			this.$genreSelector.removeAttr('disabled');
		} else {
			this.$genreSelector.val(this.fileGenres_[this.$revisedFileSelector_.val()]);
			this.$genreSelector.attr('disabled', 'disabled');
		}

		this.setUploaderVisibility_();

		return false;
	};


	/**
	 * Handle the "change" event of the genre selector, if it exists.
	 * @param {HTMLElement} genreElement The original context in
	 *  which the event was triggered.
	 * @param {Event} event The change event.
	 */
	$.pkp.controllers.wizard.fileUpload.form.FileUploadFormHandler.prototype.
			genreChange = function(genreElement, event) {

		this.setUploaderVisibility_();
	};


	//
	// Private methods
	//
	/**
	 * Attach the uploader handler.
	 * @private
	 * @param {jQueryObject} $uploader The wrapped HTML uploader element.
	 * @param {Object} options Uploader options.
	 */
	$.pkp.controllers.wizard.fileUpload.form.FileUploadFormHandler.prototype.
			attachUploader_ = function($uploader, options) {

		// Attach the uploader handler to the uploader div.
		$uploader.pkpHandler('$.pkp.controllers.UploaderHandler', options);
	};


	/**
	 * Adjust the display of the plupload component depending on required
	 * settings
	 * @private
	 */
	$.pkp.controllers.wizard.fileUpload.form.FileUploadFormHandler.prototype.
			setUploaderVisibility_ = function() {

		if ((this.hasGenreSelector_ && this.$genreSelector.val()) ||
				this.$revisedFileSelector_.val()) {
			this.showUploader_();
		} else if (!this.hasGenreSelector_ && !this.hasFileSelector_) {
			this.showUploader_();
		} else {
			this.hideUploader_();
		}
	};


	/**
	 * Hide the plupload component
	 * @private
	 */
	$.pkp.controllers.wizard.fileUpload.form.FileUploadFormHandler.prototype.
			hideUploader_ = function() {
		this.$uploader_.addClass('pkp_screen_reader');
	};


	/**
	 * Show the the plupload component
	 * @private
	 */
	$.pkp.controllers.wizard.fileUpload.form.FileUploadFormHandler.prototype.
			showUploader_ = function() {
		this.$uploader_.removeClass('pkp_screen_reader');
		// Reset the button position
		$.pkp.classes.Handler.getHandler(this.$uploader_)
				.pluploader.refresh();
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
