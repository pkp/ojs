{**
 * templates/controllers/modals/editorDecision/form/initiateExternalReviewForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form used to initiate the first review round.
 *
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#initiateReview').pkpHandler('$.pkp.controllers.form.AjaxFormHandler', null);
	{rdelim});
</script>

<p>{translate key="editor.submission.externalReviewDescription"}</p>
<form class="pkp_form" id="initiateReview" method="post" action="{url op="saveExternalReview"}" >
	{csrf}
	<input type="hidden" name="submissionId" value="{$submissionId|escape}" />
	<input type="hidden" name="stageId" value="{$stageId|escape}" />

	<!-- Available submission files -->
	{url|assign:filesForReviewUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.submission.SelectableSubmissionDetailsFilesGridHandler" op="fetchGrid" submissionId=$submissionId stageId=$stageId escape=false}
	{load_url_in_div id="filesForReviewGrid" url=$filesForReviewUrl}
	{fbvFormButtons submitText="editor.submission.decision.sendExternalReview"}
</form>
