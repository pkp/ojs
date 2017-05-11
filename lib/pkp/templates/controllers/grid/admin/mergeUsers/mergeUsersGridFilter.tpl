{**
 * controllers/grid/settings/admin/mergeUsers/mergeUsersGridFilter.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Filter template for merge user grid.
 *}
<script type="text/javascript">
	// Attach the form handler to the form.
	$('#mergeUserSearchForm').pkpHandler('$.pkp.controllers.form.FormHandler',
		{ldelim}
			trackFormChanges: false
		{rdelim}
	);
</script>
<form class="pkp_form" id="mergeUserSearchForm" action="{url router=$smarty.const.ROUTE_PAGE page="admin" op="mergeUsers"}" method="post">
	{csrf}
	<input type="hidden" name="oldUserId" value="{$filterData.oldUserId}" />
	{fbvFormArea id="searchDetails"}
		{fbvFormSection for="search"}
			{fbvElement type="select" id="searchField" from=$filterData.fieldOptions selected=$filterSelectionData.searchField inline=true size=$fbvStyles.size.SMALL}
			{fbvElement type="select" id="searchMatch" from=$filterData.searchMatchOptions selected=$filterSelectionData.searchMatch inline=true size=$fbvStyles.size.SMALL}
			{fbvElement type="text" id="search" value=$filterSelectionData.search size=$fbvStyles.size.MEDIUM inline=true}
		{/fbvFormSection}

		{fbvFormSection for="roleSymbolic"}
			{fbvElement type="select" id="roleSymbolic" from=$filterData.roleSymbolicOptions selected=$filterSelectionData.roleSymbolic size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}

		{fbvFormSection for="buttons"}
			{fbvFormButtons hideCancel=true submitText="common.search"}
		{/fbvFormSection}
	{/fbvFormArea}
</form>
