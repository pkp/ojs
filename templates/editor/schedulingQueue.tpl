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
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{$pageUrl}/editor/createIssue">{translate key="editor.navigation.createIssue"}</a></li>
	<li class="current"><a href="{$pageUrl}/editor/schedulingQueue">{translate key="common.queue.short.submissionsInScheduling"}</a></li>
	<li><a href="{$pageUrl}/editor/futureIssues">{translate key="editor.navigation.futureIssues"}</a></li>
	<li><a href="{$pageUrl}/editor/backIssues">{translate key="editor.navigation.issueArchive"}</a></li>
</ul>

<br/>

<form>{translate key="section.section"}:&nbsp;<select name="section" onchange="location.href='{$pageUrl}/editor/schedulingQueue?section='+this.options[this.selectedIndex].value" size="1" class="selectMenu">{html_options options=$sectionOptions selected=$section}</select></form>

<br />

<form method="post" action="{$pageUrl}/editor/updateSchedulingQueue" onsubmit="return confirm('{translate|escape:"javascript" key="editor.schedulingQueue.saveChanges"}')">

<table class="listing" width="100%">
	<tr>
		<td colspan="7" class="headseparator">&nbsp;</td>
	</tr>
	<tr valign="bottom" class="heading">
		<td width="5%">{translate key="common.id"}</td>
		<td width="5%"><span class="disabled">MM-DD</span><br />{translate key="submissions.submit"}</td>
		<td width="5%">{translate key="submissions.sec"}</td>
		<td width="20%">{translate key="article.authors"}</td>
		<td width="35%">{translate key="article.title"}</td>
		<td width="20%">{translate key="editor.schedulingQueue.schedule"}</td>
		<td width="10%">{translate key="common.remove"}</td>
	</tr>
	<tr>
		<td colspan="7" class="headseparator">&nbsp;</td>
	</tr>
	{foreach from=$schedulingQueueSubmissions name="submissions" item=submission}
	<tr valign="top">
		<td>{$submission->getArticleId()}</td>
		<td>{$submission->getDateSubmitted()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getSectionAbbrev()}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."}</td>
		<td><a href="{$requestPageUrl}/submission/{$submission->getArticleId()}" class="action">{$submission->getTitle()|truncate:40:"..."}</a></td>
		<td><select name="schedule[{$submission->getArticleID()}]" class="selectMenu">{html_options options=$issueOptions}</select></td>
		<td width="10%"><input type="checkbox" name="remove[]" value="{$submission->getArticleID()}" /></td>
	</tr>
	<tr>
		<td colspan="7" class="{if $smarty.foreach.submissions.last}end{/if}separator">&nbsp;</td>
	</tr>
{foreachelse}
	<tr>
		<td colspan="7" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="7" class="endseparator">&nbsp;</td>
	</tr>
	{/foreach}
</table>

<input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" />
</form>

{include file="common/footer.tpl"}
