{**
 * completed.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the details of completed submissions.
 *
 * $Id$
 *}

<table width="100%" class="listing">
	<tr><td colspan="7" class="headseparator"></td></tr>
	<tr>
		<td width="5%">{translate key="common.id"}</td>
		<td width="12%">{translate key="submissions.assigned"}</td>
		<td width="6%">{translate key="submissions.sec"}</td>
		<td>{translate key="article.authors"}</td>
		<td width="30%">{translate key="article.title"}</td>
		<td width="12%">{translate key="submissions.completed"}</td>
		<td width="12%">{translate key="common.status"}</td>
	</tr>
	<tr><td colspan="7" class="headseparator"></td></tr>
{foreach name=submissions from=$submissions item=submission}
	{assign var="articleId" value=$submission->getArticleId()}
	{assign var="layoutAssignment" value=$submission->getLayoutAssignment()}

	<tr valign="top">
		<td><a href="{$requestPageUrl}/submission/{$articleId}">{$articleId}</a></td>
		<td>{$layoutAssignment->getDateNotified()|date_format:$dateFormatShort}</td>
		<td>{$submission->getSectionAbbrev()}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."}</td>
		<td><a href="{$requestPageUrl}/submission/{$articleId}" class="action">{$submission->getArticleTitle()|truncate:60:"..."}</a></td>
		<td>{$layoutAssignment->getDateCompleted()|date_format:$dateFormatShort}</td>
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
		<td colspan="7" class="bottomseparator"></td>
	</tr>

{/foreach}

</table>
