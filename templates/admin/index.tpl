{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site administration index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="admin.siteAdmin"}
{assign var="pageId" value="admin.index"}
{include file="common/header.tpl"}

<div class="blockTitle">{translate key="admin.siteManagement"}</div>

<div class="block">
	<ul>
		<li><a href="{$pageUrl}/admin/settings">{translate key="admin.siteSettings"}</a></li>
		<li><a href="{$pageUrl}/admin/journals">{translate key="admin.hostedJournals"}</a></li>
		<li><a href="{$pageUrl}/admin/languages">{translate key="common.languages"}</a></li>
	</ul>
</div>

<br />

<div class="blockTitle">{translate key="admin.adminFunctions"}</div>
<div class="block">
	<ul>
		<li><a href="{$pageUrl}/admin/systemInfo">{translate key="admin.systemInformation"}</a></li>
		<li><a href="{$pageUrl}/admin/expireSessions" onclick="return confirm('{translate|escape:"javascript" key="admin.confirmExpireSessions"}')">{translate key="admin.expireSessions"}</a></li>
		<li><a href="{$pageUrl}/admin/clearTemplateCache" onclick="return confirm('{translate|escape:"javascript" key="admin.confirmClearTemplateCache"}')">{translate key="admin.clearTemplateCache"}</a></li>
	</ul>
</div>

{include file="common/footer.tpl"}
