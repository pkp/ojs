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
	<li><a href="{$pageUrl}/proofreader/index/active" {if ($pageToDisplay == "active")}class="active"{/if}>{translate key="common.active"}</a></li>
	<li><a href="{$pageUrl}/proofreader/index/completed" {if ($pageToDisplay == "completed")}class="active"{/if}>{translate key="common.completed"}</a></li>
</ul>

<br />

{include file="proofreader/$pageToDisplay.tpl"}

{include file="common/footer.tpl"}
