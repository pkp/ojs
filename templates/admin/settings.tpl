{**
 * templates/admin/settings.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Administration settings page, with tabs.
 *}
{strip}
{assign var="pageTitle" value="admin.settings"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#settingsTabs').pkpHandler(
				'$.pkp.controllers.TabHandler');
	{rdelim});
</script>
<div id="settingsTabs">
	<ul>
		<li><a name="siteSetup" href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.AdminSettingsTabHandler" op="showTab" tab="siteSetup"}">{translate key="admin.siteSetup"}</a></li>
		<li><a name="adminLanguages" href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.AdminSettingsTabHandler" op="showTab" tab="languages"}">{translate key="common.languages"}</a></li>
		<li><a name="adminPlugins" href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.AdminSettingsTabHandler" op="showTab" tab="plugins"}">{translate key="common.plugins"}</a></li>
        <li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.AdminSettingsTabHandler" op="showTab" tab="navigationMenus"}">{translate key="manager.navigationMenus"}</a></li>
	</ul>
</div>

{include file="common/footer.tpl"}
