{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Layout editor index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="layoutEditor.submissions.$pageToDisplay"}
{assign var="pageId" value="layoutEditor.index"}
{include file="common/header.tpl"}

<ul class="menu">
	<li{if ($pageToDisplay == "active")} class="current"{/if}><a href="{$pageUrl}/layoutEditor/index/active">{translate key="common.active"}</a></li>
	<li{if ($pageToDisplay == "completed")} class="current"{/if}><a href="{$pageUrl}/layoutEditor/index/completed">{translate key="common.completed"}</a></li>
</ul>

<br />

{include file="layoutEditor/$pageToDisplay.tpl"}

{include file="common/footer.tpl"}
