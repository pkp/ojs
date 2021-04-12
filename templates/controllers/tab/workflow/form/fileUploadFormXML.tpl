{**
 * templates/controllers/tab/workflow/form/lfileUploadFormXML.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Xml file upload form.
 *}

{* generate a unique ID for the form *}
{assign var="uploadFormXML" value="uploadFormXML"|uniqid|escape}

<script type="text/javascript">
	// Attach the file upload form handler.
	$(function() {ldelim}
		$('#{$uploadFormXML}').pkpHandler(
			'$.pkp.controllers.form.FileUploadFormHandler',
			{ldelim}
				$uploader: $('#plupload'),
				uploaderOptions: {ldelim}
					uploadUrl: {url|json_encode router=$smarty.const.ROUTE_COMPONENT  op="uploadFile" submissionId=$submissionId stageId=$stageId uploadRoles=$uploadRoles fileStage=$fileStage  escape=false},
					baseUrl: {$baseUrl|json_encode},
					filters: {ldelim}
						mime_types : [
							{ldelim} title : "XML", extensions : "xml" {rdelim}
						]
					{rdelim}
				{rdelim}
			{rdelim}
		);
	{rdelim});
</script>

<form id="{$uploadFormXML}" class="pkp_form" action="{url op="finishUpload" submissionId=$submissionId stageId=$stageId fileStage=$fileStage}" method="post">
	{csrf}

	{fbvFormArea id="file"}
		{fbvFormSection title="common.file"}
			{include file="controllers/fileUploadContainer.tpl" id="plupload" browseButton=""}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons}
</form>
