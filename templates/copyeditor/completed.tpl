{**
 * completed.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show copyeditor's submission archive.
 *
 * $Id$
 *}

<table width="100%" class="listing">
	<tr><td colspan="8" class="headseparator"></td></tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.id"}</td>
		<td width="5%"><span class="disabled">MM-DD</span><br />{translate key="submissions.assigned"}</td>
		<td width="5%">{translate key="submissions.sec"}</td>
		<td width="30%">{translate key="article.authors"}</td>
		<td width="40%">{translate key="article.title"}</td>
		<td width="5%">{translate key="submissions.completed"}</td>
		<td width="10%">{translate key="common.status"}</td>
	</tr>
	<tr><td colspan="8" class="headseparator"></td></tr>
{foreach from=$submissions name=submission item=submission}
<div class="hitlistRecord">
	{assign var="articleId" value=$submission->getArticleId()}
	<tr valign="top">
		<td>{$articleId}</td>
		<td>{$submission->getDateNotified()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getSectionAbbrev()}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."}</td>
		<td><a href="{$requestPageUrl}/submission/{$articleId}" class="action">{$submission->getArticleTitle()|truncate:60:"..."}</a></td>
		<td>{$submission->getDateFinalCompleted()|date_format:$dateFormatTrunc}</td>
		<td>
			{assign var="status" value=$submission->getStatus()}
			{if $status == 0}
				{translate key="submissions.archived"}
			{elseif $status == 1}
				{translate key="submissions.queued"}
			{elseif $status == 2}
				{translate key="submissions.scheduled"}
			{elseif $status == 3}
				{print_issue_id articleId="$articleId"}			
			{elseif $status == 4}
				{translate key="submissions.declined"}								
			{/if}
		</td>
	</tr>

	<tr>
		<td colspan="7" class="{if $smarty.foreach.submissions.last}end{/if}separator"></td>
	</tr>

{foreachelse}
	<tr>
		<td colspan="7" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="7" class="endseparator"></td>
	</tr>
{/foreach}
</table>

