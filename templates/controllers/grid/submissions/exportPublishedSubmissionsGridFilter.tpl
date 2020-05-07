{**
 * templates/controllers/grid/pubIds/exportPublishedSubmissionsGridFilter.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Filter template for submissions lists.
 *}
{assign var="formId" value="submissionsListFilter-"|concat:$filterData.gridId}
<script type="text/javascript">
	// Attach the form handler to the form.
	$('#{$formId}').pkpHandler('$.pkp.controllers.form.ClientFormHandler',
		{ldelim}
			trackFormChanges: false
		{rdelim}
	);
</script>
<form class="pkp_form filter" id="{$formId}" action="{url op="fetchGrid"}" method="post">
	{csrf}
	{fbvFormArea id="submissionSearchFormArea"|concat:$filterData.gridId}
		{fbvFormSection}
			{fbvElement type="select" name="column" id="column"|concat:$filterData.gridId from=$filterData.columns selected=$filterSelectionData.column size=$fbvStyles.size.SMALL translate=false inline="true"}
			{fbvElement type="search" name="search" id="search"|concat:$filterData.gridId value=$filterSelectionData.search size=$fbvStyles.size.MEDIUM inline="true"}
		{/fbvFormSection}
		{fbvFormSection}
			{fbvElement type="select" name="issueId" id="issueId"|concat:$filterData.gridId from=$filterData.issues selected=$filterSelectionData.issueId size=$fbvStyles.size.SMALL translate=false inline="true"}
			{fbvElement type="select" name="statusId" id="statusId"|concat:$filterData.gridId from=$filterData.status selected=$filterSelectionData.statusId size=$fbvStyles.size.SMALL translate=false inline="true"}
		{/fbvFormSection}
		{* Buttons generate their own section *}
		{fbvFormButtons hideCancel=true submitText="common.search"}
	{/fbvFormArea}
</form>
