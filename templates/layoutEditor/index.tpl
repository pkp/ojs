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

{assign var="pageTitle" value="layoutEditor.journalLayoutEditor"}
{assign var="pageId" value="layoutEditor.index"}
{include file="common/header.tpl"}

<ul id="tabnav">
	<li><a href="{$pageUrl}/layoutEditor/index/active" {if ($pageToDisplay == "active")}class="active"{/if}>{translate key="common.active"}</a></li>
	<li><a href="{$pageUrl}/layoutEditor/index/completed" {if ($pageToDisplay == "completed")}class="active"{/if}>{translate key="common.completed"}</a></li>
</ul>

{include file="layoutEditor/$pageToDisplay.tpl"}

{include file="common/footer.tpl"}
