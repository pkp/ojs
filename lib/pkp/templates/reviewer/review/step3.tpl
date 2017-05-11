{**
 * templates/reviewer/review/step3.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the step 3 review page
 *}
<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#reviewStep3Form').pkpHandler(
			'$.pkp.controllers.form.reviewer.ReviewerReviewStep3FormHandler'
		);
	{rdelim});
</script>

<form class="pkp_form" id="reviewStep3Form" method="post" action="{url op="saveStep" path=$submission->getId() step="3"}">
	{csrf}
	{include file="common/formErrors.tpl"}
{fbvFormArea id="reviewStep3"}
	{url|assign:reviewFilesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.review.ReviewerReviewFilesGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$reviewAssignment->getStageId() reviewRoundId=$reviewRoundId reviewAssignmentId=$reviewAssignment->getId() escape=false}
	{load_url_in_div id="reviewFiles" url=$reviewFilesGridUrl}

	{if $viewGuidelinesAction}
		{fbvFormSection title="reviewer.submission.reviewerGuidelines"}
			<div id="viewGuidelines">
				{include file="linkAction/linkAction.tpl" action=$viewGuidelinesAction contextId="viewGuidelines"}
			</div>
		{/fbvFormSection}
	{/if}

	{fbvFormSection label="submission.review" description="reviewer.submission.reviewDescription"}
		{if $reviewForm}
			{include file="reviewer/review/reviewFormResponse.tpl"}
		{else}
			{fbvFormSection}
				{fbvElement type="textarea" id="comments" name="comments" value=$comment readonly=$reviewIsComplete label=submission.comments.canShareWithAuthor rich=true}
			{/fbvFormSection}
			{fbvFormSection}
				{fbvElement type="textarea" id="commentsPrivate" name="commentsPrivate" value=$commentPrivate readonly=$reviewIsComplete label=submission.comments.cannotShareWithAuthor rich=true}
			{/fbvFormSection}
		{/if}
	{/fbvFormSection}

	{fbvFormSection label="common.upload" description="reviewer.submission.uploadDescription"}
		{url|assign:reviewAttachmentsGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.attachment.ReviewerReviewAttachmentsGridHandler" op="fetchGrid" assocType=$smarty.const.ASSOC_TYPE_REVIEW_ASSIGNMENT assocId=$submission->getReviewId() submissionId=$submission->getId() stageId=$submission->getStageId() reviewIsComplete=$reviewIsComplete escape=false}
		{load_url_in_div id="reviewAttachmentsGridContainer" url=$reviewAttachmentsGridUrl}
	{/fbvFormSection}

	{$additionalFormFields}

	{url|assign:cancelUrl page="reviewer" op="submission" path=$submission->getId() step=2 escape=false}
	{fbvFormButtons submitText="reviewer.submission.submitReview" confirmSubmit="reviewer.confirmSubmit" cancelText="navigation.goBack" cancelUrl=$cancelUrl cancelUrlTarget="_self" submitDisabled=$reviewIsComplete}
{/fbvFormArea}
</form>
<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
