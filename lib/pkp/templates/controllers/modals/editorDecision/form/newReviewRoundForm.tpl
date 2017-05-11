{**
 * templates/controllers/modals/editorDecision/form/newReviewRoundForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form used to create a new review round (after the first round)
 *
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#newRoundForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler', null);
	{rdelim});
</script>

<p>{translate key="editor.submission.newRoundDescription"}</p>
<form class="pkp_form" id="newRoundForm" method="post" action="{url op="saveNewReviewRound"}" >
	{csrf}
	<input type="hidden" name="submissionId" value="{$submissionId|escape}" />
	<input type="hidden" name="stageId" value="{$stageId|escape}" />
	<input type="hidden" name="reviewRoundId" value="{$reviewRoundId|escape}" />
	{** a new review round always implies a RESUBMIT decision **}
	<input type="hidden" name="decision" value="{$smarty.const.SUBMISSION_EDITOR_DECISION_RESUBMIT}" />

	<!-- Revision files grid (Displays only revisions at first, and hides all other files which can then be displayed with filter button -->
	{url|assign:newRoundRevisionsUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.review.SelectableReviewRevisionsGridHandler" op="fetchGrid" submissionId=$submissionId stageId=$stageId reviewRoundId=$reviewRoundId escape=false}
	{load_url_in_div id="newRoundRevisionsGrid" url=$newRoundRevisionsUrl}

	{fbvFormButtons submitText="editor.submission.createNewRound"}
</form>

