{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Editor index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="editor.navigation.editorAdministration"}
{assign var="currentUrl" value="$pageUrl/editor"}
{assign var="pageId" value="editor.index"}
{include file="common/header.tpl"}

<ul id="tabnav">
	<li><a href="{$pageUrl}/editor/index/submissionsUnassigned" {if ($pageToDisplay == "submissionsUnassigned")}class="active"{/if}>{translate key="editor.navigation.unassigned"}</a></li>
	<li><a href="{$pageUrl}/editor/index/submissionsInReview" {if ($pageToDisplay == "submissionsInReview")}class="active"{/if}>{translate key="editor.navigation.submissionsInReview"}</a></li>
	<li><a href="{$pageUrl}/editor/index/submissionsInEditing" {if ($pageToDisplay == "submissionsInEditing")}class="active"{/if}>{translate key="editor.navigation.submissionsInEditing"}</a></li>
	<li><a href="{$pageUrl}/editor/index/submissionsArchives" {if ($pageToDisplay == "submissionsArchives")}class="active"{/if}>{translate key="editor.navigation.submissionsArchives"}</a></li>
</ul>

{assign var="dateMonthDay" value="%m-%d"}

{include file="editor/$pageToDisplay.tpl"}

{include file="common/footer.tpl"}
