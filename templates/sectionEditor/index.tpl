{**
 * index.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Section editor index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="common.queue.long.$pageToDisplay"}
{assign var="thisUrl" value=$currentUrl}
{assign var="currentUrl" value="$pageUrl/sectionEditor"}

{include file="common/header.tpl"}

<ul class="menu">
	<li{if ($pageToDisplay == "submissionsInReview")} class="current"{/if}><a href="{$pageUrl}/sectionEditor/index/submissionsInReview">{translate key="common.queue.short.submissionsInReview"}</a></li>
	<li{if ($pageToDisplay == "submissionsInEditing")} class="current"{/if}><a href="{$pageUrl}/sectionEditor/index/submissionsInEditing">{translate key="common.queue.short.submissionsInEditing}</a></li>
	<li{if ($pageToDisplay == "submissionsArchives")} class="current"{/if}><a href="{$pageUrl}/sectionEditor/index/submissionsArchives">{translate key="common.queue.short.submissionsArchives"}</a></li>
</ul>

<br />

{include file="sectionEditor/$pageToDisplay.tpl"}

<form>
{translate key="section.section"}: <select name="section" class="selectMenu" onchange="location.href='{$thisUrl}?section='+this.options[this.selectedIndex].value" size="1">{html_options options=$sectionOptions selected=$section}</select>
</form>

{include file="common/footer.tpl"}
