{**
 * templates/controllers/tab/settings/form/newImageFileUploadForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Image file upload form.
 *}

<script type="text/javascript">
	// Attach the file upload form handler.
	$(function() {ldelim}
		$('#uploadForm').pkpHandler(
			'$.pkp.controllers.form.FileUploadFormHandler',
			{ldelim}
				$uploader: $('#plupload'),
				uploaderOptions: {ldelim}
					uploadUrl: {url|json_encode op="uploadFile" fileSettingName=$fileSettingName fileType=$fileType escape=false},
					baseUrl: {$baseUrl|json_encode},
					filters: {ldelim}
						mime_types : [
							{ldelim} title : "Image files", extensions : "jpg,jpeg,png" {rdelim}
						]
					{rdelim}
				{rdelim}
			{rdelim}
		);
	{rdelim});
</script>

<form id="uploadForm" class="pkp_form" action="{url op="saveFile" fileSettingName=$fileSettingName fileType=$fileType}" method="post">
	{csrf}
	<input type="hidden" name="temporaryFileId" id="temporaryFileId" value="" />
	{fbvFormArea id="file"}
		{fbvFormSection title="common.file"}
			{include file="controllers/fileUploadContainer.tpl" id="plupload"}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="extraFileData"}
		{fbvFormSection title="common.altText"}
			{fbvElement type="text" label="common.altTextInstructions" multilingual=true id="imageAltText" value=$imageAltText}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons}
</form>
