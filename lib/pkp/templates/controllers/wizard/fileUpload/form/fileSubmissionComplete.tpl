{**
 * fileSubmissionComplete.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Last step of the file upload wizard.
 *}
<div id="finishSubmissionForm" class="pkp_helpers_text_center">
	<h2>{translate key="submission.submit.fileAdded"}</h2>
	{if $fileStage != $smarty.const.SUBMISSION_FILE_PROOF}
		<button class="pkp_button" type="button" name="newFile" id="newFile">{translate key='submission.submit.newFile'}</button>
	{/if}
	<br>
	<br>
</div>
