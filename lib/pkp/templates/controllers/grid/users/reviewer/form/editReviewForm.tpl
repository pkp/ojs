{**
 * templates/controllers/grid/user/reviewer/form/editReviewForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Limit the review files available to a reviewer who has already been
 * assigned to a submission.
 *
 *}
<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#editReviewForm').pkpHandler('$.pkp.controllers.grid.users.reviewer.form.EditReviewFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="editReviewForm" method="post" action="{url op="updateReview"}">
	{csrf}
	<input type="hidden" name="reviewAssignmentId" value="{$reviewAssignmentId|escape}" />
	<input type="hidden" name="stageId" value="{$stageId|escape}" />
	<input type="hidden" name="submissionId" value="{$submissionId|escape}" />
	<input type="hidden" name="reviewRoundId" value="{$reviewRoundId|escape}" />

	{fbvFormSection title="editor.review.importantDates"}
		{fbvElement type="text" id="responseDueDate" name="responseDueDate" label="submission.task.responseDueDate" value=$responseDueDate|date_format:$dateFormatShort inline=true size=$fbvStyles.size.MEDIUM class="datepicker"}
		{fbvElement type="text" id="reviewDueDate" name="reviewDueDate" label="editor.review.reviewDueDate" value=$reviewDueDate|date_format:$dateFormatShort inline=true size=$fbvStyles.size.MEDIUM class="datepicker"}
	{/fbvFormSection}

	{include file="controllers/grid/users/reviewer/form/noFilesWarning.tpl"}

	<h3>{translate key="editor.submissionReview.restrictFiles"}</h3>
	{url|assign:limitReviewFilesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.review.LimitReviewFilesGridHandler" op="fetchGrid" submissionId=$submissionId stageId=$stageId reviewRoundId=$reviewRoundId reviewAssignmentId=$reviewAssignmentId escape=false}
	{load_url_in_div id="limitReviewFilesGrid" url=$limitReviewFilesGridUrl}

	{fbvFormButtons}
</form>
