{**
 * fileUploadConfirmationForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * File revision confirmation form.
 *
 * Parameters:
 *   $submissionId: The submission for which a file has been uploaded.
 *   $stageId: The workflow stage in which the file uploader was called.
 *   $uploadedFile: The SubmissionFile object of the uploaded file.
 *   $revisedFileId: The id of the potential revision.
 *   $revisedFileName: The name of the potential revision.
 *   $reviewRoundId: The review round ID (if specified)
 *   $submissionFileOptions: A list of submission files that can be
 *    revised.
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the revision confirmation handler.
		$('#uploadForm').pkpHandler(
			'$.pkp.controllers.wizard.fileUpload.form.RevisionConfirmationHandler');
	{rdelim});
</script>

<form class="pkp_form pkp_controllers_grid_files" id="uploadForm"
		action="{url op="confirmRevision" submissionId=$submissionId stageId=$stageId fileStage=$fileStage uploadedFileId=$uploadedFile->getFileId() reviewRoundId=$reviewRoundId}"
		method="post">
	{csrf}
	{fbvFormArea id="file"}
		<div id="possibleRevision" class="pkp_controllers_grid_files_possibleRevision" style="display:none;">
			<div id="revisionWarningText">
				{fbvFormSection title="submission.upload.possibleRevision"}
					<div class="description">
						{translate key="submission.upload.possibleRevisionDescription" revisedFileName=$revisedFileName}
					</div>
					{fbvElement type="select" name="revisedFileId" id="revisedFileId" from=$submissionFileOptions selected=$revisedFileId translate=false} <br />
				{/fbvFormSection}
			</div>
		</div>
	{/fbvFormArea}
	<div class="separator"></div>
</form>
