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

<table class="listing">
	<tr><td class="headseparator" colspan="6"></td></tr>
	<tr valign="bottom" class="heading">
		<td width="5%">{translate key="common.id"}</td>
		<td width="12%">{translate key="submissions.submitted"}</td>
		<td width="6%">{translate key="submissions.sec"}</td>
		<td>{translate key="article.authors"}</td>
		<td width="35%">{translate key="article.title"}</td>
		<td width="12%">{translate key="common.status"}</td>
	</tr>
{foreach name=submissions from=$submissions item=submission}
	{assign var="articleId" value=$submission->getArticleId()}
	<tr valign="top">
		<td>{$articleId}</td>
		<td>{$submission->getDateSubmitted()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getSectionAbbrev()}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."}</td>
		<td><a href="{$requestPageUrl}/submission/{$articleId}" class="action">{$submission->getArticleTitle()|truncate:60:"..."}</a></td>
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

