{**
 * templates/controllers/grid/files/proof/manageProofFiles.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Allows editor to add more files to the publication format
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#manageProofFilesForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<!-- Current proof files -->
<p>{translate key="editor.submission.proof.manageProofFilesDescription"}</p>

<div id="existingFilesContainer">
	<form class="pkp_form" id="manageProofFilesForm" action="{url component="grid.files.proof.ManageProofFilesGridHandler" op="updateProofFiles" submissionId=$submissionId}" method="post">
		{csrf}
		{fbvFormArea id="manageProofFiles"}
			{fbvFormSection}
				<input type="hidden" name="submissionId" value="{$submissionId|escape}" />
				<input type="hidden" name="stageId" value="{$smarty.const.WORKFLOW_STAGE_ID_PRODUCTION}" />
				<input type="hidden" name="representationId" value="{$representationId|escape}" />
				{url|assign:availableReviewFilesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.proof.ManageProofFilesGridHandler" op="fetchGrid" submissionId=$submissionId representationId=$representationId escape=false}
				{load_url_in_div id="availableReviewFilesGrid" url=$availableReviewFilesGridUrl}
			{/fbvFormSection}

			{fbvFormButtons}
		{/fbvFormArea}
	</form>
</div>
