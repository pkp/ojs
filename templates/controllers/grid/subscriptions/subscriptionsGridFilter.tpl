{**
 * templates/controllers/grid/subscriptions/subscriptionsGridFilter.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Filter template for individual subscriptions grid.
 *}
{assign var=formId value="filterForm-"|concat:$grid->getId()}
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
	{fbvFormArea id="subscriptionsSearchFormArea"}
		{fbvFormSection title="common.search" for="search"}
			{fbvElement type="select" id="searchField" from=$filterData.fieldOptions selected=$filterSelectionData.searchField inline=true size=$fbvStyles.size.SMALL}
			{fbvElement type="select" id="searchMatch" from=$filterData.matchOptions selected=$filterSelectionData.searchMatch inline=true size=$fbvStyles.size.SMALL}
			{fbvElement type="text" name="search" id="search" value=$filterSelectionData.search size=$fbvStyles.size.LARGE inline="true"}
		{/fbvFormSection}
		{fbvFormButtons hideCancel=true submitText="common.search"}
	{/fbvFormArea}
</form>
