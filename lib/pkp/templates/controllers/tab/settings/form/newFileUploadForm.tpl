{**
 * templates/controllers/tab/settings/form/newFileUploadForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * File upload form.
 *}

<script type="text/javascript">
	// Attach the file upload form handler.
	$(function() {ldelim}
		$('#uploadForm').pkpHandler(
			'$.pkp.controllers.form.FileUploadFormHandler',
			{ldelim}
				$uploader: $('#plupload'),
				uploaderOptions: {ldelim}
					{if $fileType == 'css'}
						filters: {ldelim}
							mime_types : [
								{ldelim} title : "CSS files", extensions : "css" {rdelim}
							]
						{rdelim},
					{/if}
					uploadUrl: {url|json_encode op="uploadFile" fileSettingName=$fileSettingName fileType=$fileType escape=false},
					baseUrl: {$baseUrl|json_encode}
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

	{fbvFormButtons}
</form>
