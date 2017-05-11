{**
 * templates/controllers/wizard/fileUpload/form/fileUploadForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Files upload form.
 *
 * Parameters:
 *   $submissionId: The submissionId for which a file is being uploaded.
 *   $stageId: The workflow stage in which the file uploader was called.
 *   $uploaderUserGroupOptions: An array of user groups that are allowed
 *    to upload.
 *   $defaultUserGroupId: A pre-selected user group (optional).
 *   $revisionOnly: Whether the user can upload new files or not.
 *   $revisedFileId: The id of the file to be revised (optional).
 *    When set to a number then the user may not choose the file
 *    to be revised.
 *   $revisedFileName: The name of the file to be revised (if any).
 *   $genreId: The preset genre of the file to be uploaded (optional).
 *   $submissionFileOptions: A list of submission files that can be
 *    revised.
 *   $currentSubmissionFileGenres: An array that assigns genres to the submission
 *    files that can be revised.
 *   $submissionFileGenres: A list of all available submission file genres.
 *
 * This form implements several states:
 *
 * 1) Uploading of a revision to an existing file with the
 *    file to be revised already known:
 *    - $revisionOnly is true.
 *    - $revisedFileId is set to a number.
 *    - $submissionFileOptions will be ignored.
 *    -> No file selector will be shown.
 *    -> A file genre cannot be set.
 *
 * 2) Uploading of a revision to an existing file where the
 *    file to be revised must still be selected by the user.
 *    - $revisionOnly is true.
 *    - $revisedFileId is not set to a number.
 *    - $submissionFileOptions must not be empty.
 *    -> A selector with files that can be revised will
 *       be shown. Selection of a revised file is mandatory.
 *       If a revised file id is given then that file will
 *       be pre-selected.
 *    -> A file genre cannot be set.
 *
 * 3) Uploading of a file that may or may not be a revision
 *    of an existing file (free upload).
 *    - $revisionOnly is false.
 *    - $revisedFileId does not have to be a number.
 *    - $submissionFileOptions is not empty.
 *    -> A selector with files that can be revised will
 *       be shown. Selection of a revised file is optional.
 *       If the revised file id is set then this file will
 *       be pre-selected in the drop-down.
 *    -> A file genre selector will be shown but will be
 *       deactivated as soon as the user selects a file
 *       to be revised. Otherwise selection of a genre is
 *       mandatory.
 *    -> Uploaded files will be checked against existing
 *       files to identify possible revisions.
 *
 * 4) Uploading of a new file when no previous files
 *    exist at all at this workflow stage.
 *    - $revisionOnly is false.
 *    - $revisedFileId must not be a number.
 *    - $submissionFileOptions is empty.
 *    -> No file selector will be shown.
 *    -> A file genre selector will be shown. Selection of
 *       a genre is mandatory.
 *
 * The following decision tree shows the input parameters
 * and the corresponding use cases (RO: $revisionOnly,
 * RF: $revisedFileId, FO: $submissionFileOptions,
 * y=given, n=not given, o=any/ignored):
 *
 *   RO  RF  FO
 *   y   y   o  -> 1)
 *   |   n   y  -> 2)
 *   |   |   n  -> not allowed (skip loading form and show a message to user)
 *
 *       FO  RF
 *   n   y   o  -> 3)
 *   |   n   y  -> not allowed
 *   |   |   n  -> 4)
 *}

{* Implement the above decision tree and configure the form based on the identified use case. *}
{assign var="showFileNameOnly" value=false}
{if $revisionOnly}
	{assign var="showGenreSelector" value=false}
	{if is_numeric($revisedFileId)}
		{* Use case 1: Revision of a known file *}
		{assign var="showFileSelector" value=false}
		{assign var="showFileNameOnly" value=true}
	{else}
		{* Use case 2: Revision of a file which still must be chosen *}
		{if empty($submissionFileOptions)}{assign var="revisionOnlyWithoutFileOptions" value=true}{/if}
		{assign var="showFileSelector" value=true}
	{/if}
{else}
	{assign var="showGenreSelector" value=true}
	{if empty($submissionFileOptions)}
		{* Use case 4: Upload a new file *}
		{if is_numeric($revisedFileId)}{"A revised file id cannot be given when uploading a new file!"|fatalError}{/if}
		{assign var="showFileSelector" value=false}
	{else}
		{* Use case 3: Upload a new file or a revision *}
		{assign var="showFileSelector" value=true}
	{/if}

	{* Disable the genre selector for reviewer attachements *}
	{if $isReviewAttachment}{assign var="showGenreSelector" value=false}{/if}
{/if}

{if $revisionOnlyWithoutFileOptions}
	<br /><br />
	{translate key="submission.upload.noAvailableReviewFiles"}
	<br /><br />
{else}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the upload form handler.
		$('#uploadForm').pkpHandler(
			'$.pkp.controllers.wizard.fileUpload.form.FileUploadFormHandler',
			{ldelim}
				hasFileSelector: {if $showFileSelector}true{else}false{/if},
				hasGenreSelector: {if $showGenreSelector}true{else}false{/if},
				presetRevisedFileId: {$revisedFileId|json_encode},
				// File genres currently assigned to submission files.
				fileGenres: {ldelim}
					{foreach name=currentSubmissionFileGenres from=$currentSubmissionFileGenres key=submissionFileId item=fileGenre}
						{if !empty($fileGenre)}
							{$submissionFileId|json_encode}: {$fileGenre|json_encode}{if !$smarty.foreach.currentSubmissionFileGenres.last},{/if}
						{/if}
					{/foreach}
				{rdelim},
				$uploader: $('#plupload'),
				uploaderOptions: {ldelim}
					uploadUrl: {url|json_encode op="uploadFile" submissionId=$submissionId stageId=$stageId fileStage=$fileStage reviewRoundId=$reviewRoundId assocType=$assocType assocId=$assocId escape=false},
					baseUrl: {$baseUrl|json_encode},
				{rdelim}
			{rdelim});
	{rdelim});
</script>

<form class="pkp_form" id="uploadForm" action="#" method="post">
	{csrf}
	{fbvFormArea id="file"}
		{if $assocType && $assocId}
			<input type="hidden" name="assocType" value="{$assocType|escape}" />
			<input type="hidden" name="assocId" value="{$assocId|escape}" />
		{/if}
		{if count($uploaderUserGroupOptions) > 1}
			{fbvFormSection label="submission.upload.userGroup" required=true}
				{fbvElement type="select" name="uploaderUserGroupId" id="uploaderUserGroupId" from=$uploaderUserGroupOptions selected=$defaultUserGroupId translate=false required=true}
			{/fbvFormSection}
		{else}
			<input type="hidden" id="uploaderUserGroupId" name="uploaderUserGroupId" value="{$uploaderUserGroupOptions|@key}" />
		{/if}

		{if $showFileNameOnly}
			{fbvFormSection title="submission.submit.currentFile"}
				{$revisedFileName}
			{/fbvFormSection}

			{* Save the revised file ID in a hidden input field. *}
			<input type="hidden" id="revisedFileId" name="revisedFileId" value="{$revisedFileId}" />
		{elseif $showFileSelector}
			{* TODO: This should be a radio button selection, where the select is displayed only if the user chooses to replace a file *}
			{if $revisionOnly}
				{assign var=revisionSelectTitle value="submission.upload.selectMandatoryFileToRevise"}
			{else}
				{assign var=revisionSelectTitle value="submission.upload.selectOptionalFileToRevise"}
			{/if}
			{fbvFormSection title=$revisionSelectTitle required=$revisionOnly}
				{fbvElement type="select" name="revisedFileId" id="revisedFileId" from=$submissionFileOptions selected=$revisedFileId translate=false}
			{/fbvFormSection}
		{/if}

		{if $showGenreSelector}
			{fbvFormSection title="submission.upload.fileContents" required=true}
				{translate|assign:"defaultLabel" key="submission.upload.selectComponent"}
				{fbvElement type="select" name="genreId" id="genreId" from=$submissionFileGenres translate=false defaultLabel=$defaultLabel defaultValue="" required="true" selected=$genreId required=true}
			{/fbvFormSection}
		{/if}

		{fbvFormSection}
			{* The uploader widget *}
			{include file="controllers/fileUploadContainer.tpl" id="plupload"}
		{/fbvFormSection}

		{if $ensuringLink}
			<div id="{$ensuringLink->getId()}" class="pkp_linkActions">
				{include file="linkAction/linkAction.tpl" action=$ensuringLink contextId="uploadForm"}
			</div>
		{/if}
	{/fbvFormArea}
</form>
{/if}
