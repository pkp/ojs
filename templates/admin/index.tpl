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

<a href="{$pageUrl}/admin/settings">{translate key="admin.settings.siteSettings"}</a>

<br /><br />

<a href="{$pageUrl}/admin/journals">{translate key="admin.settings.hostedJournals"}</a>

{include file="common/footer.tpl"}