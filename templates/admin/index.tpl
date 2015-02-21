{**
 * templates/admin/index.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site administration index.
 *
 *}
{strip}
{assign var="pageTitle" value="admin.siteAdmin"}
{include file="common/header.tpl"}
{/strip}

{if $newVersionAvailable}
<div class="warningMessage">{translate key="site.upgradeAvailable.admin" currentVersion=$currentVersion latestVersion=$latestVersion}</div>
{/if}

<div id="siteManagement">
<h3>{translate key="admin.siteManagement"}</h3>

<ul>
	<li><a href="{url op="settings"}">{translate key="admin.siteSettings"}</a></li>
	<li><a href="{url op="journals"}">{translate key="admin.hostedJournals"}</a></li>
	<li><a href="{url op="languages"}">{translate key="common.languages"}</a></li>
	<li><a href="{url op="auth"}">{translate key="admin.authSources"}</a></li>
	<li><a href="{url op="categories"}">{translate key="admin.categories"}</a></li>
	{call_hook name="Templates::Admin::Index::SiteManagement"}
</ul>
</div>
<div id="adminFunctions">
<h3>{translate key="admin.adminFunctions"}</h3>

<ul>
	<li><a href="{url op="systemInfo"}">{translate key="admin.systemInformation"}</a></li>
	<li><a href="{url op="expireSessions"}" onclick="return confirm('{translate|escape:"jsparam" key="admin.confirmExpireSessions"}')">{translate key="admin.expireSessions"}</a></li>
	<li><a href="{url op="clearDataCache"}">{translate key="admin.clearDataCache"}</a></li>
	<li><a href="{url op="clearTemplateCache"}" onclick="return confirm('{translate|escape:"jsparam" key="admin.confirmClearTemplateCache"}')">{translate key="admin.clearTemplateCache"}</a></li>
	<li><a href="{url op="clearScheduledTaskLogFiles"}" onclick="return confirm('{translate|escape:"jsparam" key="admin.scheduledTask.confirmClearLogs"}')">{translate key="admin.scheduledTask.clearLogs"}</a></li>
	<li><a href="{url op="mergeUsers"}">{translate key="admin.mergeUsers"}</a></li>
	{call_hook name="Templates::Admin::Index::AdminFunctions"}
</ul>
</div>
{include file="common/footer.tpl"}

