{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Copyeditor index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="common.queue.long.$pageToDisplay"}
{assign var="pageId" value="copyeditor.index"}
{include file="common/header.tpl"}

<ul class="menu">
	<li{if ($pageToDisplay == "active")} class="current"{/if}><a href="{$pageUrl}/copyeditor/index/active">{translate key="common.queue.short.active"}</a></li>
	<li{if ($pageToDisplay == "completed")} class="current"{/if}><a href="{$pageUrl}/copyeditor/index/completed">{translate key="common.queue.short.completed"}</a></li>
</ul>

<br />

{include file="copyeditor/$pageToDisplay.tpl"}

{include file="common/footer.tpl"}
