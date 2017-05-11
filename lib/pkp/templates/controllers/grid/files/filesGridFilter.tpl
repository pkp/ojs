{**
 * controllers/grid/files/filesGridFilter.tpl
 *
 * Copyright (c) 2016-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Filter template for submission file lists.
 *}
{assign var="formId" value="submissionFilesListFilter-"|concat:$filterData.gridId:"-"|uniqid}
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
	{fbvFormArea id="submissionFilesSearchFormArea"|concat:$filterData.gridId}
		{fbvFormSection}
			{fbvElement type="text" name="search" id="search"|concat:$filterData.gridId value=$filterSelectionData.search size=$fbvStyles.size.MEDIUM inline="true"}
			{fbvElement type="select" name="column" id="column"|concat:$filterData.gridId from=$filterData.columns selected=$filterSelectionData.column size=$fbvStyles.size.SMALL translate=false inline="true"}
		{/fbvFormSection}
		{fbvFormButtons hideCancel=true submitText="common.search"}
	{/fbvFormArea}
</form>
