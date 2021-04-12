{**
 * templates/controllers/tab/workflow/form/fileSubmissionComplete.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 *}
{assign var=finishXMLSubmissionForm value="finishXMLSubmissionForm"|uniqid}

<div id="finishXMLSubmission" class="pkp_helpers_text_center">
	<h2>{translate key="submission.submit.fileAdded"}</h2>

	<br>
	<script>
		// Attach the handler.
		$(function() {ldelim}
			$('#{$finishXMLSubmissionForm}').pkpHandler(
					'$.pkp.controllers.form.CancelActionAjaxFormHandler',
					{ldelim}
						csrfToken: {csrf type=json},
						cancelUrl: {url|json_encode component="api.file.ManageFileApiHandler" op="deleteFile" fileId=$fileId submissionId=$submissionId revision=$revision stageId=$stageId fileStage=$fileStage csrfToken=$csrfToken suppressNotification=true escape=false},

		{rdelim}
			);
			{rdelim});
	</script>

	<form id="{$finishXMLSubmissionForm}" class="pkp_form" action="{url op="finishFileSubmission" fileId=$fileId submissionId=$submissionId revision=$revision stageId=$stageId fileStage=$fileStage}" method="post">
		{csrf}
		{fbvFormButtons}
	</form>

</div>
