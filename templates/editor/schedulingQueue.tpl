{**
 * schedulingQueue.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Articles waiting to be scheduled for publishing.
 *
 * $Id$
 *}

{assign var="pageTitle" value="editor.schedulingQueue"}
{assign var="currentUrl" value="$pageUrl/editor/schedulingQueue"}
{assign var="pageId" value="editor.schedulingQueue"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{$pageUrl}/editor/createIssue">{translate key="editor.navigation.createIssue"}</a></li>
	<li class="current"><a href="{$pageUrl}/editor/schedulingQueue">{translate key="editor.navigation.submissionsInScheduling"}</a></li>
	<li><a href="{$pageUrl}/editor/issueToc">{translate key="editor.navigation.liveIssues"}</a></li>
	<li><a href="{$pageUrl}/editor/backIssues">{translate key="editor.navigation.issueArchive"}</a></li>
</ul>
<br/>

<form method="post" action="{$pageUrl}/editor/updateSchedulingQueue" onsubmit="return confirm('{translate|escape:"javascript" key="editor.schedulingQueue.saveChanges"}')">

	<table class="listing" width="100%">
		<tr><td colspan="6" class="headseparator"></td></tr>
		<tr valign="top" class="heading">
			<td width="12%"><a href="{$pageUrl}/editor/schedulingQueue?sort=submitted&amp;order={$order}{if $section}&amp;section={$section}{/if}" class="sortColumn">{translate key="editor.schedulingQueue.submitted"}</a></td>
			<td width="10%"><a href="{$pageUrl}/editor/schedulingQueue?sort=section&amp;order={$order}{if $section}&amp;section={$section}{/if}" class="sortColumn">{translate key="section.section"}</a></td>
			<td width="20%">{translate key="article.authors"}</td>
			<td width="28%"><a href="{$pageUrl}/editor/schedulingQueue?sort=title&amp;order={$order}{if $section}&amp;section={$section}{/if}" class="sortColumn">{translate key="article.title"}</a></td>
			<td width="20%">{translate key="editor.schedulingQueue.schedule"}</td>
			<td width="10%">{translate key="common.remove"}</td>
		</tr>
		<tr><td colspan="6" class="headseparator"></td></tr>
		{foreach from=$schedulingQueueSubmissions name="submissions" item=article}
			{assign var="articleId" value=$article->getArticleId()}
			{assign var="onclick" value="onclick=\"javascript:loadUrl('$requestPageUrl/submission/$articleId');\""}
			<tr valign="top">
				<td width="12%" {$onclick}>{$article->getDateSubmitted()|date_format:"$dateFormatShort"}</td>
				<td width="10%" {$onclick}>{$article->getSectionAbbrev()}</td>
				<td width="20%" {$onclick}>{$article->getAuthorString()}</td>
				<td width="28%" {$onclick}>{$article->getArticleTitle()|truncate:25:"..."}</td>
				<td width="20%"><select name="schedule[{$article->getArticleID()}]" class="selectMenu">{html_options options=$issueOptions}</select></td>
				<td width="10%"><input type="checkbox" name="remove[]" value="{$article->getArticleID()}" /></td>
			</tr>
			<tr>
				<td colspan="6" class="{if $smarty.foreach.submissions.last}end{/if}separator"></td>
			</tr>
		{foreachelse}
			<tr>
				<td colspan="6" class="nodata">{translate key="submissions.noSubmissions"}</td>
			</tr>
			<tr>
				<td colspan="6" class="endseparator"></td>
			</tr>
		{/foreach}
	</table>
	<input type="submit" class="button" value="{translate key="common.saveChanges"}" />
</form>

<form>{translate key="section.section"}:&nbsp;<select name="section" onchange="location.href='{$pageUrl}/editor/schedulingQueue?section='+this.options[this.selectedIndex].value" size="1" class="selectMenu">{html_options options=$sectionOptions selected=$section}</select></form>

{include file="common/footer.tpl"}
