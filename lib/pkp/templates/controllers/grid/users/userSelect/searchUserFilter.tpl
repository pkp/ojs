{**
 * templates/controllers/grid/user/userSelect/searchUserFilter.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Displays the form to filter results in the userSelect grid.
 *
 *}
{assign var="formId" value="searchUserFilter-"|concat:$filterData.gridId}
<script type="text/javascript">
	// Attach the form handler to the form.
	$('#{$formId}').pkpHandler('$.pkp.controllers.grid.users.stageParticipant.form.AddParticipantFormHandler',
		{ldelim}
			trackFormChanges: false
		{rdelim}
	);
</script>
<form class="pkp_form filter" id="{$formId}" action="{url op="fetchGrid"}" method="post">
	{csrf}
	{fbvFormArea id="userSearchFormArea"|concat:$filterData.gridId}
		<input type="hidden" name="submissionId" value="{$filterData.submissionId|escape}" />
		<input type="hidden" name="stageId" value="{$filterData.stageId|escape}" />
		{fbvFormSection}
			{fbvElement type="select" name="filterUserGroupId" id="filterUserGroupId"|concat:$filterData.gridId from=$filterData.userGroupOptions selected=$filterSelectionData.filterUserGroupId size=$fbvStyles.size.SMALL translate=false inline="true"}
			{fbvElement type="text" name="name" id="name"|concat:$filterData.gridId value=$filterSelectionData.name label="manager.userSearch.searchByName" size=$fbvStyles.size.LARGE inline="true"}
		{/fbvFormSection}
		{* Buttons generate their own section *}
		{fbvFormButtons hideCancel=true submitText="common.search"}
	{/fbvFormArea}
</form>
