{**
 * templates/dashboard/index.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Dashboard index.
 *}
{include file="common/header.tpl" pageTitle="navigation.submissions"}

<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#dashboardTabs').pkpHandler('$.pkp.controllers.TabHandler');
	{rdelim});
</script>
<div id="dashboardTabs" class="pkp_controllers_tab">
	<ul>
		<li><a name="myQueue" href="{url op="myQueue"}">{translate key="dashboard.myQueue"}</a></li>
		{if array_intersect(array(ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER), (array)$userRoles)}
			<li><a name="active" href="{url op="active"}">{translate key="common.queue.long.active"}</a></li>
		{/if}
		{if array_intersect(array(ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_REVIEWER, ROLE_ID_ASSISTANT), (array)$userRoles)}
			<li><a name="archives" href="{url op="archives"}">{translate key="navigation.archives"}</a></li>
		{/if}
	</ul>
</div>

{include file="common/footer.tpl"}
