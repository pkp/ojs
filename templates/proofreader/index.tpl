{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Proofreader index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="proofreader.submissions.$pageToDisplay"}
{assign var="pageId" value="proofreader.index"}
{include file="common/header.tpl"}

<ul class="menu">
	<li{if ($pageToDisplay == "active")} class="current"{/if}><a href="{$pageUrl}/proofreader/index/active">{translate key="common.active"}</a></li>
	<li{if ($pageToDisplay == "completed")} class="current"{/if}><a href="{$pageUrl}/proofreader/index/completed">{translate key="proofreader.submissions.completed"}</a></li>
</ul>

<br />

{include file="proofreader/$pageToDisplay.tpl"}

{include file="common/footer.tpl"}
