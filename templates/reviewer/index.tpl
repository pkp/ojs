{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Reviewer index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="reviewer.journalReviewer"}
{assign var="pageId" value="reviewer.index"}
{include file="common/header.tpl"}

<ul id="tabnav">
	<li><a href="{$pageUrl}/reviewer/index/active" {if ($pageToDisplay == "active")}class="active"{/if}>{translate key="common.active"}</a></li>
	<li><a href="{$pageUrl}/reviewer/index/completed" {if ($pageToDisplay == "completed")}class="active"{/if}>{translate key="common.completed"}</a></li>
</ul>

{include file="reviewer/$pageToDisplay.tpl"}

{include file="common/footer.tpl"}
