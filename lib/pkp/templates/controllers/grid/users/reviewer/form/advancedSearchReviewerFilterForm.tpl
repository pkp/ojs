{**
 * templates/controllers/grid/user/reviewer/form/advancedSearchReviewerFilterForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Displays the form to filter results in the reviewerSelect grid.
 *
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Handle filter form submission
		$('#reviewerFilterForm').pkpHandler('$.pkp.controllers.form.ClientFormHandler',
			{ldelim}
				trackFormChanges: false
			{rdelim}
		);
	{rdelim});
</script>

<form class="pkp_form filter" id="reviewerFilterForm" action="{url router=$smarty.const.ROUTE_COMPONENT component="grid.users.reviewerSelect.ReviewerSelectGridHandler" op="fetchGrid"}" method="post" class="pkp_controllers_reviewerSelector">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="advancedSearchReviewerFilterFormNotification"}
	{fbvFormArea id="reviewerSearchForm"}
		<input type="hidden" id="submissionId" name="submissionId" value="{$submissionId|escape}" />
		<input type="hidden" id="stageId" name="stageId" value="{$stageId|escape}" />
		<input type="hidden" id="reviewRoundId" name="reviewRoundId" value="{$reviewRoundId|escape}" />
		<input type="hidden" name="clientSubmit" value="1" />

		{fbvFormSection title="manager.reviewerSearch.searchByName"}
			{fbvElement type="text" id="name" value=$reviewerValues.name|escape}
		{/fbvFormSection}

		{capture assign="extraFilters"}
			{fbvFormSection inline="true" size=$fbvStyles.size.MEDIUM}
				{fbvElement type="rangeSlider" id="done" min=0 max=100 label="manager.reviewerSearch.doneAmount" valueMin=$reviewerValues.doneMin|default:0 valueMax=$reviewerValues.doneMax|default:100 toggleable=true toggleable_label="manager.reviewerSearch.doneAmountToggle" enabled=$reviewerValues.doneEnabled}
			{/fbvFormSection}
			{fbvFormSection inline="true" size=$fbvStyles.size.MEDIUM}
				{fbvElement type="rangeSlider" id="last" min=0 max=365 label="manager.reviewerSearch.lastAmount" valueMin=$reviewerValues.lastMin|default:0 valueMax=$reviewerValues.lastMax|default:365 toggleable=true toggleable_label="manager.reviewerSearch.lastAmountToggle" enabled=$reviewerValues.lastEnabled}
			{/fbvFormSection}
			{fbvFormSection inline="true" size=$fbvStyles.size.MEDIUM}
				{fbvElement type="rangeSlider" id="avg" min=0 max=365 label="manager.reviewerSearch.avgAmount" valueMin=$reviewerValues.avgMin|default:0 valueMax=$reviewerValues.avgMax|default:365 toggleable=true toggleable_label="manager.reviewerSearch.avgAmountToggle" enabled=$reviewerValues.avgEnabled}
			{/fbvFormSection}
			{fbvFormSection inline="true" size=$fbvStyles.size.MEDIUM}
				{fbvElement type="rangeSlider" id="active" min=0 max=100 label="manager.reviewerSearch.activeAmount" valueMin=$reviewerValues.activeMin|default:0 valueMax=$reviewerValues.activeMax|default:100 toggleable=true toggleable_label="manager.reviewerSearch.activeAmountToggle" enabled=$reviewerValues.activeEnabled}
			{/fbvFormSection}

			{fbvFormSection title="manager.reviewerSearch.form.interests.instructions"}
				{fbvElement type="interests" id="interests" interests=$interestSearchKeywords}
			{/fbvFormSection}
			{if $reviewRound > 1}
				{if $previousReviewRounds}
					{assign var="checked" value=true}
				{else}
					{assign var="checked" value=false}
				{/if}
				{fbvFormSection for="previousReviewRounds" list=true}
					{fbvElement type="checkbox" label="manager.reviewerSearch.form.previousReviewRounds" id="previousReviewRounds" checked=$checked}
				{/fbvFormSection}
			{/if}
		{/capture}

		<div id="reviewerAdvancedSearchFilters">
			{include file="controllers/extrasOnDemand.tpl"
				id="reviewerAdvancedSearchFiltersWrapper"
				widgetWrapper="#reviewerAdvancedSearchFilters"
				moreDetailsText="search.advancedSearchMore"
				lessDetailsText="search.advancedSearchLess"
				extraContent=$extraFilters
			}
		</div>

		{fbvFormSection class="pkp_helpers_text_right"}
			{fbvElement type="submit" id="submitFilter" label="common.search"}
		{/fbvFormSection}
	{/fbvFormArea}
</form>
