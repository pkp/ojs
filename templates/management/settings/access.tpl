{**
 * templates/management/settings/access.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Access and Security page.
 *}

{strip}
{assign var="pageTitle" value="navigation.access"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#accessTabs').pkpHandler(
			'$.pkp.controllers.tab.settings.managementSettings.UsersAndRolesTabHandler',
			{ldelim}
				userGridContentUrl:'{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.AccessSettingsTabHandler" tab="users" op="showTab"}',
			{rdelim}

		);
	{rdelim});
</script>
<div id="accessTabs">
	<ul>
		<li><a name="users" href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.AccessSettingsTabHandler" op="showTab" tab="users"}">{translate key="manager.users"}</a></li>
		<li><a name="roles" href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.AccessSettingsTabHandler" op="showTab" tab="roles"}">{translate key="manager.roles"}</a></li>
		<li><a name="siteAccessOptions" href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.AccessSettingsTabHandler" op="showTab" tab="siteAccessOptions"}">{translate key="manager.siteAccessOptions.siteAccessOptions"}</a></li>
	</ul>
</div>

{include file="common/footer.tpl"}
