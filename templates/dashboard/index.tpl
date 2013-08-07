{**
 * templates/dashboard/index.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Dashboard index.
 *}
{strip}
{assign var="pageTitle" value="navigation.dashboard"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#dashboardTabs').pkpHandler(
				'$.pkp.controllers.TabHandler');
	{rdelim});
</script>
<div id="dashboardTabs">
	<ul>
		<li><a href="{url op="tasks"}">{translate key="dashboard.tasks"}</a></li>
		<li><a href="{url op="submissions"}">{translate key="dashboard.submissions"}</a></li>
		{if array_intersect(array(ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER, ROLE_ID_SERIES_EDITOR, ROLE_ID_REVIEWER, ROLE_ID_PRESS_ASSISTANT), $userRoles)}
			<li><a href="{url op="archives"}">{translate key="navigation.archives"}</a></li>
		{/if}
	</ul>
</div>

{include file="common/footer.tpl"}
