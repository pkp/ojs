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

{assign var="pageTitle" value="common.queue.long.$pageToDisplay"}
{include file="common/header.tpl"}

<ul class="menu">
	<li{if ($pageToDisplay == "active")} class="current"{/if}><a href="{$pageUrl}/author/index/active">{translate key="common.queue.short.active"}</a></li>
	<li{if ($pageToDisplay == "completed")} class="current"{/if}><a href="{$pageUrl}/author/index/completed">{translate key="common.queue.short.completed"}</a></li>
</ul>

{include file="author/$pageToDisplay.tpl"}

<p>{translate key="author.submit.startHere"}<br/>
<a href="{$pageUrl}/author/submit" class="action">{translate key="author.submit.startHereLink"}</a><br />
</p>

<div class="separator"></div>

{include file="common/footer.tpl"}
