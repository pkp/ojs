{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Section editor index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="editor.navigation.sectionEditorAdministration"}
{assign var="pageId" value="sectionEditor.index"}
{include file="common/header.tpl"}

<ul id="tabnav">
	<li><a href="{$pageUrl}/sectionEditor/index/submissionsInReview" {if ($pageToDisplay == "submissionsInReview")}class="active"{/if}>{translate key="editor.navigation.submissionsInReview"}</a></li>
	{if ($managementModel != 1)}
		<li><a href="{$pageUrl}/sectionEditor/index/submissionsInEditing" {if ($pageToDisplay == "submissionsInEditing")}class="active"{/if}>{translate key="editor.navigation.submissionsInEditing"}</a></li>
	{/if}
	<li><a href="{$pageUrl}/sectionEditor/index/submissionsArchives" {if ($pageToDisplay == "submissionsArchives")}class="active"{/if}>{translate key="editor.navigation.submissionsArchives"}</a></li>
</ul>

{assign var="dateMonthDay" value="%m-%d"}

{include file="sectionEditor/$pageToDisplay.tpl"}

{include file="common/footer.tpl"}
