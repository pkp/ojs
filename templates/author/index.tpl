{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Journal author index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="author.journalAuthor"}
{assign var="pageId" value="author.index"}
{include file="common/header.tpl"}

<ul class="menu">
	<li{if ($pageToDisplay == "active")} class="current"{/if}><a href="{$pageUrl}/author/index/active">{translate key="common.active"}</a></li>
	<li{if ($pageToDisplay == "completed")} class="current"{/if}><a href="{$pageUrl}/author/index/completed">{translate key="author.submit.archives"}</a></li>
</ul>

{include file="author/$pageToDisplay.tpl"}

{include file="common/footer.tpl"}
