{**
 * templates/reviewer/review/step1.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the review step 1 page
 *
 *}
<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#reviewStep1Form').pkpHandler(
			'$.pkp.controllers.form.AjaxFormHandler'
		);
	{rdelim});
</script>

<form class="pkp_form" id="reviewStep1Form" method="post" action="{url page="reviewer" op="saveStep" path=$submission->getId() step="1" escape=false}">
{csrf}
{include file="common/formErrors.tpl"}

{fbvFormArea id="reviewStep1"}
	{fbvFormSection title="reviewer.step1.request"}
		<p>{$reviewerRequest|nl2br}</p>
	{/fbvFormSection}
	{fbvFormSection label="submission.title"}
		{$submission->getLocalizedTitle()|strip_unsafe_html}
	{/fbvFormSection}
	{fbvFormSection label=$descriptionFieldKey}
		{$submission->getLocalizedAbstract()|strip_unsafe_html}
	{/fbvFormSection}

	<div class="pkp_linkActions">
		{include file="linkAction/linkAction.tpl" action=$viewMetadataAction contextId="reviewStep1Form"}
	</div>
	<br /><br />
	{fbvFormSection title="reviewer.submission.reviewSchedule"}
		{fbvElement type="text" id="dateNotified" label="reviewer.submission.reviewRequestDate" value=$submission->getDateNotified()|date_format:$dateFormatShort readonly=true inline=true size=$fbvStyles.size.SMALL}
		{fbvElement type="text" id="responseDue" label="reviewer.submission.responseDueDate" value=$submission->getDateResponseDue()|date_format:$dateFormatShort readonly=true inline=true size=$fbvStyles.size.SMALL}
		{fbvElement type="text" id="dateDue" label="reviewer.submission.reviewDueDate" value=$submission->getDateDue()|date_format:$dateFormatShort readonly=true inline=true size=$fbvStyles.size.SMALL}
	{/fbvFormSection}
	<br /><br />
		<div class="pkp_linkActions">
			{include file="linkAction/linkAction.tpl" action=$aboutDueDatesAction contextId="reviewStep1"}
		</div>
	<br /><br />
	{if $competingInterestsAction}
		{fbvFormSection label="reviewer.submission.competingInterests" description="reviewer.submission.enterCompetingInterests"}
			<div class="pkp_linkActions">
				{include file="linkAction/linkAction.tpl" action=$competingInterestsAction contextId="reviewStep1"}
			</div>
		{/fbvFormSection}
	{/if}

	{if $competingInterestsText != null}
		{assign var="hasCI" value=true}
		{assign var="noCI" value=false}
	{else}
		{assign var="hasCI" value=false}
		{assign var="noCI" value=true}
	{/if}
	{if $hasCI || $currentContext->getSetting('reviewerCompetingInterestsRequired')}
		{fbvFormSection list=true}
			{fbvElement type="radio" value="noCompetingInterests" id="noCompetingInterests" name="competingInterestOption" checked=$noCI label="reviewer.submission.noCompetingInterests" disabled=$reviewIsComplete}
			<br /><br />
			{fbvElement type="radio" value="hasCompetingInterests" id="hasCompetingInterests" name="competingInterestOption" checked=$hasCI label="reviewer.submission.hasCompetingInterests" disabled=$reviewIsComplete}
		{/fbvFormSection}

		{fbvFormSection}
			{fbvElement type="textarea" name="competingInterestsText" id="competingInterestsText" value=$competingInterestsText size=$fbvStyles.size.MEDIUM disabled=$reviewIsComplete rich=true}
		{/fbvFormSection}
	{/if}

	{if $reviewAssignment->getDateConfirmed()}
		{fbvFormButtons hideCancel=true submitText="common.saveAndContinue" submitDisabled=$reviewIsComplete}
	{else}
		{fbvFormButtons submitText="reviewer.submission.acceptReview" cancelText="reviewer.submission.declineReview" cancelAction=$declineReviewAction submitDisabled=$reviewIsComplete}
	{/if}
{/fbvFormArea}
</form>
