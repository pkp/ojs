{**
 * templates/controllers/wizard/fileUpload/form/submissionArtworkFileMetadataForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Artwork file metadata form.
 *
 * Parameters:
 *  $submissionFile: The submission artwork file.
 *  $stageId: The workflow stage id from which the upload
 *   wizard was called.
 *  $showButtons: True iff form buttons should be presented.
 *}
<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#metadataForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="metadataForm" action="{url component="api.file.ManageFileApiHandler" op="saveMetadata" submissionId=$submissionFile->getSubmissionId() stageId=$stageId reviewRoundId=$reviewRoundId fileStage=$submissionFile->getFileStage() fileId=$submissionFile->getFileId() escape=false}" method="post">
	{csrf}

	{* Editable metadata *}
	{fbvFormArea id="fileMetaData"}

		{* File detail summary *}
		{fbvFormSection}
			{include file="controllers/wizard/fileUpload/form/uploadedFileSummary.tpl" submissionFile=$submissionFile}
		{/fbvFormSection}

		{fbvFormSection title="grid.artworkFile.caption" inline=true size=$fbvStyles.size.MEDIUM}
			{fbvElement type="textarea" id="artworkCaption" height=$fbvStyles.height.SHORT value=$submissionFile->getCaption()}
		{/fbvFormSection}
		{fbvFormSection title="grid.artworkFile.credit" inline=true size=$fbvStyles.size.MEDIUM}
			{fbvElement type="textarea" id="artworkCredit" height=$fbvStyles.height.SHORT value=$submissionFile->getCredit()}
		{/fbvFormSection}
		{fbvFormSection title="grid.artworkFile.copyrightOwner" inline=true size=$fbvStyles.size.MEDIUM}
			{fbvElement type="textarea" id="artworkCopyrightOwner" height=$fbvStyles.height.SHORT value=$submissionFile->getCopyrightOwner()}
		{/fbvFormSection}
		{fbvFormSection title="grid.artworkFile.permissionTerms" inline=true size=$fbvStyles.size.MEDIUM}
			{fbvElement type="textarea" id="artworkPermissionTerms" height=$fbvStyles.height.SHORT value=$submissionFile->getPermissionTerms()}
		{/fbvFormSection}
	{/fbvFormArea}

	{if $showButtons}
		{fbvElement type="hidden" id="showButtons" value=$showButtons}
		{fbvFormButtons submitText="common.save"}
	{/if}
</form>
