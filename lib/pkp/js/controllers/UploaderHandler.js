/**
 * @file js/controllers/UploaderHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UploaderHandler
 * @ingroup js_controllers
 *
 * @brief PKP file uploader widget handler.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.Handler
	 *
	 * @param {jQueryObject} $uploader the wrapped HTML uploader element.
	 * @param {{
	 *  uploadUrl: string,
	 *  baseUrl: string
	 *  }} options options to be passed
	 *  into the validator plug-in.
	 */
	$.pkp.controllers.UploaderHandler = function($uploader, options) {
		this.parent($uploader, options);

		// Check whether we really got a div to attach
		// our uploader to.
		if (!$uploader.is('div')) {
			throw new Error(['An uploader widget controller can only be attached',
				' to a div!'].join(''));
		}

		var uploaderOptions,
				pluploaderId,
				$browseButton,
				self;

		// Set up options to pass to plupload
		uploaderOptions = {
			url: options.uploadUrl,
			// Flash settings
			flash_swf_url: options.baseUrl +
					'/lib/pkp/lib/vendor/moxiecode/plupload/js/Moxie.swf',
			// Silverlight settings
			silverlight_xap_url: options.baseUrl +
					'/lib/pkp/lib/vendor/moxiecode/plupload/js/Moxie.xap'
		};
		if (typeof options.filters) {
			uploaderOptions.filters = options.filters;
		}
		if (typeof options.resize) {
			uploaderOptions.resize = options.resize;
		}
		if (typeof options.browse_button) {
			uploaderOptions.browse_button = options.browse_button;
		}
		if (typeof options.multipart_params) {
			uploaderOptions.multipart_params = options.multipart_params;
		}
		uploaderOptions = $.extend(
				{},
				this.self('DEFAULT_PROPERTIES_'),
				uploaderOptions
				);

		// Create the uploader with the puploader plug-in.
		// Setup the upload widget.
		this.pluploader = new plupload.Uploader(uploaderOptions);
		this.pluploader.init();
		this.updateStatus('waiting');

		// Cache re-used DOM references
		this.$progress = $uploader.find('.pkpUploaderProgress .percentage');
		this.$progressBar = $uploader.find('.pkpUploaderProgressBar');
		this.$fileName = $uploader.find('.pkpUploaderFilename');

		// Bind to the pluploader for some configuration
		this.pluploader.bind('FilesAdded',
				this.callbackWrapper(this.startUpload));
		this.pluploader.bind('UploadProgress',
				this.callbackWrapper(this.updateProgress));
		this.pluploader.bind('Error',
				this.callbackWrapper(this.handleError));
		this.pluploader.bind('FileUploaded',
				this.callbackWrapper(this.uploadComplete));
		this.pluploader.bind('QueueChanged',
				this.callbackWrapper(this.refreshUploader));

		// Ensure clicks on the visual button don't attempt to submit the form
		$browseButton = $('#' + uploaderOptions.browse_button, this.getHtmlElement());
		$browseButton.click(function(e) {
			return false;
		});

		this.pluploader.refresh();

		// Fake a focus effect on the visual button when plupload's hidden
		// button is focused
		self = this;
		setTimeout(function() {
			self.getHtmlElement().find('.moxie-shim input')
					.focus(function(e) {
						$browseButton.addClass('in_focus');
					})
					.blur(function(e) {
						$browseButton.removeClass('in_focus');
					});
		}, 100);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.UploaderHandler, $.pkp.classes.Handler);


	//
	// Public methods
	//
	/**
	 * Initiate upload of a file
	 * @param {Object} caller The original context in which the callback was called.
	 * @param {Object} pluploader The pluploader object.
	 * @param {Object} file The data of the uploaded file.
	 *
	 */
	$.pkp.controllers.UploaderHandler.prototype.
			startUpload = function(caller, pluploader, file) {

		// Prevent > 1 files from being added.
		if (pluploader.files.length > 1) {
			pluploader.removeFile(pluploader.files[0]);
		}

		// Initiate the upload process
		this.updateStatus('uploading');
		pluploader.start();
	};


	/**
	 * Update the progress indicator for a file
	 * @param {Object} caller The original context in which the callback was called.
	 * @param {Object} pluploader The pluploader object.
	 * @param {Object} file The data of the uploaded file.
	 *
	 */
	$.pkp.controllers.UploaderHandler.prototype.
			updateProgress = function(caller, pluploader, file) {

		this.$progress.html(file.percent);
		this.$progressBar.css('width', file.percent + '%');
	};


	/**
	 * Indicate the file upload has completed
	 * @param {Object} caller The original context in which the callback was called.
	 * @param {Object} pluploader The pluploader object.
	 * @param {Object} file The data of the uploaded file.
	 * @param {{response: string}} response
	 */
	$.pkp.controllers.UploaderHandler.prototype.
			uploadComplete = function(caller, pluploader, file, response) {
		var jsonData = $.parseJSON(response.response), filename = file.name;

		if (!jsonData.status) {
			this.showError(jsonData.content);
			return;
		}

		if (typeof jsonData.uploadedFile !== 'undefined') {
			filename = jsonData.uploadedFile.name || jsonData.uploadedFile.fileLabel;

			// Store uploaded file data so that it can be referenced during
			// other API events. This is used by the submission file wizard
			// to delete files that are uploaded then replaced before submission
			// is complete.
			//
			// See: $.pkp.controllers.wizard.fileUpload.FileUploadWizardHandler.
			//		prototype.handleRemovedFiles
			file.storedData = jsonData.uploadedFile;
		}

		this.$fileName.html(filename);
		this.updateStatus('complete');
		this.$progress.html('0');
		this.$progressBar.css('width', 0);
	};


	/**
	 * Handle error revents from plupload
	 * @param {Object} caller The original context in which the callback was called.
	 * @param {Object} pluploader The pluploader object.
	 * @param {{message: string}} err An object describing an error condition
	 *
	 */
	$.pkp.controllers.UploaderHandler.prototype.
			handleError = function(caller, pluploader, err) {
		this.showError(err.message);
	};


	/**
	 * Display an error if encountered during upload
	 * @param {string} msg The error message
	 *
	 */
	$.pkp.controllers.UploaderHandler.prototype.
			showError = function(msg) {

		this.$progress.html('0');
		this.$progressBar.css('width', 0);
		this.updateStatus('error');
		this.getHtmlElement().find('.pkpUploaderError').html(msg);
	};


	/**
	 * Refresh the uploader interface so buttons work correctly.
	 * @param {Object} caller The original context in which the callback was called.
	 * @param {Object} pluploader The pluploader object.
	 * @param {Object} file The data of the uploaded file.
	 *
	 */
	$.pkp.controllers.UploaderHandler.prototype.
			refreshUploader = function(caller, pluploader, file) {
		pluploader.refresh();
	};


	/**
	 * Update the status of the element in the DOM
	 * @param {string} status The new status
	 *
	 */
	$.pkp.controllers.UploaderHandler.prototype.
			updateStatus = function(status) {
		this.getHtmlElement().removeClass('loading waiting uploading error complete')
				.addClass(status);
	};


	//
	// Private static properties
	//
	/**
	 * Default options
	 * @private
	 * @type {Object}
	 * @const
	 */
	$.pkp.controllers.UploaderHandler.DEFAULT_PROPERTIES_ = {
		runtimes: 'html5,flash,silverlight,html4',
		max_file_size: $.pkp.cons.UPLOAD_MAX_FILESIZE,
		multi_selection: false,
		file_data_name: 'uploadedFile',
		multipart: true,
		headers: {'browser_user_agent': navigator.userAgent},
		browse_button: 'pkpUploaderButton',
		drop_element: 'pkpUploaderDropZone'
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
