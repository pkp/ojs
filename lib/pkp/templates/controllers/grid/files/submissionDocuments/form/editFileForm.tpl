{**
 * templates/controllers/grid/files/submissionDocuments/form/editFileForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Library Files form for editing an existing file
 *}

<script type="text/javascript">
	// Attach the file upload form handler.
	$(function() {ldelim}
		$('#uploadForm').pkpHandler(
			'$.pkp.controllers.form.AjaxFormHandler'
		);
	{rdelim});
</script>

<form class="pkp_form" id="uploadForm" action="{url op="updateFile" fileId=$libraryFile->getId()}" method="post">
	{csrf}
	<input type="hidden" name="submissionId" value="{$submissionId|escape}" />
	{fbvFormArea id="name"}
		{fbvFormSection title="common.name" required=true}
			{fbvElement type="text" id="libraryFileName" value=$libraryFileName maxlength="255" multilingual=true required=true}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="type"}
		{fbvFormSection title="common.type" required=true}
			{translate|assign:"defaultLabel" key="common.chooseOne"}
			{fbvElement type="select" from=$fileTypes id="fileType" selected=$libraryFile->getType() defaultValue="" defaultLabel=$defaultLabel required=true}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="file"}
		{fbvFormSection title="common.file"}
			<table id="fileInfo" class="data" width="100%">
			<tr valign="top">
				<td width="20%" class="label">{translate key="common.fileName"}</td>
				<td width="80%" class="value">{$libraryFile->getOriginalFileName()|escape}</a></td>
			</tr>
			<tr valign="top">
				<td class="label">{translate key="common.fileSize"}</td>
				<td class="value">{$libraryFile->getNiceFileSize()}</td>
			</tr>
			<tr valign="top">
				<td class="label">{translate key="common.dateUploaded"}</td>
				<td class="value">{$libraryFile->getDateUploaded()|date_format:$datetimeFormatShort}</td>
			</tr>
			</table>
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons}
</form>
<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
