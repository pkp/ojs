{**
 * templates/controllers/grid/settings/roles/userGroupsGridFilter.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 *}

<script type="text/javascript">
	// Attach the form handler to the form.
	$('#userGroupSearchForm').pkpHandler('$.pkp.controllers.form.ToggleFormHandler');
</script>
<form class="pkp_form filter" id="userGroupSearchForm" action="{url router=$smarty.const.ROUTE_COMPONENT component="grid.settings.roles.UserGroupGridHandler" op="fetchGrid"}" method="post">
	{csrf}
	{fbvFormArea id="userGroupSearchFormArea"}
		{fbvFormSection description="grid.userGroup.filter.listRoles" size=$fbvStyles.size.SMALL inline=true}
			{fbvElement type="select" id="selectedStageId" from=$filterData.stageOptions selected=$filterSelectionData.selectedStageId}
		{/fbvFormSection}
		{fbvFormSection description="grid.userGroup.filter.permissionLevel" size=$fbvStyles.size.SMALL inline=true}
			{fbvElement type="select" id="selectedRoleId" from=$filterData.roleOptions selected=$filterSelectionData.selectedRoleId}
		{/fbvFormSection}
	{/fbvFormArea}
</form>
