{**
 * templates/controllers/wizard/fileUpload/form/uploadedFileSummary.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Summary of the file name, type, size and dimensions.
 *
 * @uses $submissionFile SubmissionFile|SubmissionArtworkFile|SupplementaryFile The file.
 *}
<div class="pkp_uploadedFile_summary">
	<div class="filename" data-pkp-editable="true">
		<div class="display" data-pkp-editable-view="display">
			<span data-pkp-editable-displays="name">
				{$submissionFile->getLocalizedName()|escape}
			</span>
			<a href="#" class="pkpEditableToggle edit">{translate key="common.edit"}</a>
		</div>
		<div class="input" data-pkp-editable-view="input">
			{fbvFormSection title="submission.form.name" required=true}
				{fbvElement type="text" id="name" value=$submissionFile->getName(null) multilingual=true maxlength="255" required=true}
			{/fbvFormSection}
		</div>
	</div>

	<div class="details">
		{if is_a($submissionFile, 'SubmissionArtworkFile')}
			<span class="pixels">
				{translate key="common.dimensionsPixels" width=$submissionFile->getWidth() height=$submissionFile->getHeight()}
			</span>

			<span class="print">
				{translate key="common.dimensionsInches" width=$submissionFile->getPhysicalWidth(300) height=$submissionFile->getPhysicalHeight(300) dpi=300}
			</span>
		{/if}

		<span class="type {$submissionFile->getExtension()|lower|escape}">
			{$submissionFile->getExtension()|lower|escape}
		</span>

		<span class="file_size">
			{$submissionFile->getNiceFileSize()}
		</span>
	</div>
</div>
