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

{assign var="pageTitle" value="sectionEditor.submissions.$pageToDisplay"}
{assign var="thisUrl" value=$currentUrl}
{assign var="pageId" value="sectionEditor.index"}

{include file="common/header.tpl"}

<ul class="menu">
	<li{if ($pageToDisplay == "submissionsInReview")} class="current"{/if}><a href="{$pageUrl}/sectionEditor/index/submissionsInReview">{translate key="editor.navigation.submissionsInReview"}</a></li>
	<li{if ($pageToDisplay == "submissionsInEditing")} class="current"{/if}><a href="{$pageUrl}/sectionEditor/index/submissionsInEditing">{translate key="editor.navigation.submissionsInEditing"}</a></li>
	<li{if ($pageToDisplay == "submissionsArchives")} class="current"{/if}><a href="{$pageUrl}/sectionEditor/index/submissionsArchives">{translate key="editor.navigation.submissionsArchives"}</a></li>
</ul>

<br />

{assign var="dateMonthDay" value="%m-%d"}

{include file="sectionEditor/$pageToDisplay.tpl"}

<form>
{translate key="section.section"}: <select name="section" onchange="location.href='{$thisUrl}?section='+this.options[this.selectedIndex].value" size="1">{html_options options=$sectionOptions selected=$section}</select>
</form>

{include file="common/footer.tpl"}
