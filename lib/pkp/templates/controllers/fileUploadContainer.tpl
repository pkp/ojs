{**
 * controllers/fileUploadContainer.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Markup for file uploader widget.
 *
 * @uses $id string ID for the HTML element wrapper. Typically used by parent
 *  forms to instantiate the plupload utility.
 * @uses $stringDragFile string (optional) Translation key for the drag and
 *  drop zone label
 * @uses $stringAddFile string (optional) Translation key for the add file
 *  button
 * @uses $stringChangeFile string (optional) Translation key for the change
 *  file button
 * @uses $browseButton string (optional) Element id for the browse button. When
 *  a custom id is used, FileUploadFormHandler must pass this id in the
 * `uploaderOptions`.
 *}
{if !$stringDragFile}
	{assign var=stringDragFile value="common.upload.dragFile"}
{/if}
{if !$stringAddFile}
	{assign var=stringAddFile value="common.upload.addFile"}
{/if}
{if !$stringChangeFile}
	{assign var=stringChangeFile value="common.upload.changeFile"}
{/if}
{if !$browseButton}
	{assign var=browseButton value="pkpUploaderButton"}
{/if}


<div id="{$id}" class="pkp_controller_fileUpload loading">
	<div class="pkp_uploader_loading">
		{**
		 * This wrapper div is a hack to emulate the inPlaceNotification.tpl
		 * structure. There's currently not a way to use these notifications
		 * without loading the JavaScript handler, but in this case we don't
		 * have the required settings.
		 *}
		<div class="pkp_notification">
			{translate|assign:"warningMessage" key="common.fileUploaderError"}
			{translate|assign:"warningTitle" key="common.warning"}
			{include file="controllers/notification/inPlaceNotificationContent.tpl" notificationId=$id
				notificationStyleClass="notifyWarning" notificationContents=$warningMessage}
		</div>
	</div>

	{* The file upload and drag-and-drop area *}
	<div id="pkpUploaderDropZone" class="pkp_uploader_drop_zone">

		<div class="pkp_uploader_drop_zone_label">
			{translate key=$stringDragFile}
		</div>

		<div class="pkp_uploader_details">
			<span class="pkpUploaderProgress">
				{translate key="common.percentage" percentage='<span class="percentage">0</span>'}
			</span>{* Live progress (%) *}
			<div class="pkp_uploader_progress_bar_wrapper">
				<span class="pkpUploaderProgressBar"></span>{* Live progress bar*}
			</div>
			<span class="pkpUploaderFilename"></span>{* Uploaded file name *}
		</div>

		{* Placeholder for errors during upload *}
		<div class="pkpUploaderError"></div>

		{* Button to add/change file *}
		<button id="{$browseButton|escape}" class="pkp_uploader_button pkp_button" tabindex="-1">
			<span class="pkp_uploader_button_add">
				{translate key=$stringAddFile}
			</span>
			<span class="pkp_uploader_button_change">
				{translate key=$stringChangeFile}
			</span>
		</button>
	</div>
</div>
