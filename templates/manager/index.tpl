{**
 * templates/manager/index.tpl
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Journal management index.
 *
 *}
{strip}
{assign var="pageTitle" value="manager.journalManagement"}
{include file="common/header.tpl"}
{/strip}

{if $newVersionAvailable}
<div class="warningMessage">{translate key="site.upgradeAvailable.manager" currentVersion=$currentVersion latestVersion=$latestVersion siteAdminName=$siteAdmin->getFullName() siteAdminEmail=$siteAdmin->getEmail()}</div>
{/if}

<div id="managementPages">
<h3>{translate key="manager.managementPages"}</h3>

<ul class="plain">
	<li>&#187; <a href="{url op="files"}">{translate key="manager.filesBrowser"}</a></li>
	<li>&#187; <a href="{url op="reviewForms"}">{translate key="manager.reviewForms"}</a></li>
	<li>&#187; <a href="{url page="rtadmin"}">{translate key="manager.readingTools"}</a></li>
	<li>&#187; <a href="{url op="payments"}">{translate key="manager.payments"}</a></li>
	{if $publishingMode == $smarty.const.PUBLISHING_MODE_SUBSCRIPTION}
		<li>&#187; <a href="{url op="subscriptionsSummary"}">{translate key="manager.subscriptions"}</a></li>
	{/if}
	{call_hook name="Templates::Manager::Index::ManagementPages"}
</ul>
</div>

{include file="common/footer.tpl"}
