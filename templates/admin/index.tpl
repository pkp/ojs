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
{include file="common/header.tpl"}

<div class="blockTitle">{translate key="admin.siteManagement"}</div>
<div class="block">
	<ul>
		<li><a href="{$pageUrl}/admin/settings">{translate key="admin.settings.siteSettings"}</a></li>
		<li><a href="{$pageUrl}/admin/journals">{translate key="admin.settings.hostedJournals"}</a></li>
	</ul>
</div>

<br />

<div class="blockTitle">{translate key="admin.adminFunctions"}</div>
<div class="block">
	<ul>
		<li><a href="#" onclick="confirmAction('{$pageUrl}/admin/expireSessions', '{translate|escape:"javascript" key="admin.confirmExpireSessions"}')">{translate key="admin.expireSessions"}</a></li>
		<li><a href="#" onclick="confirmAction('{$pageUrl}/admin/clearTemplateCache', '{translate|escape:"javascript" key="admin.confirmClearTemplateCache"}')">{translate key="admin.clearTemplateCache"}</a></li>
	</ul>
</div>

{include file="common/footer.tpl"}
