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

<div id="hitlistTitles">
	<table>
		<tr>
			<td width="5%" align="center">{translate key="common.id"}</td>
			<td width="12%" align="center">{translate key="submissions.assigned"}</td>
			<td width="6%" align="center">{translate key="submissions.sec"}</td>
			<td align="center">{translate key="article.authors"}</td>
			<td width="30%" align="center">{translate key="article.title"}</td>
			<td width="12%" align="center">{translate key="submissions.completed"}</td>
			<td width="12%" align="center">{translate key="common.status"}</td>
		</tr>
	</table>
</div>
{foreach from=$submissions item=submission}
<div class="hitlistRecord">
	<table>
		{assign var="articleId" value=$submission->getArticleId()}
		<tr class="{cycle values="row,rowAlt"}">
			<td width="5%" align="center"><a href="{$requestPageUrl}/submission/{$articleId}">{$articleId}</a></td>
			<td width="12%" align="center">{$submission->getDateNotified()|date_format:$dateFormatShort}</td>
			<td width="6%" align="center">{$submission->getSectionAbbrev()}</td>
			<td>
				{foreach from=$submission->getAuthors() item=author name=authorList}
					{$author->getLastName()}{if !$smarty.foreach.authorList.last},{/if}
				{/foreach}
			</td>
			<td width="30%"><a href="{$requestPageUrl}/submission/{$articleId}">{$submission->getArticleTitle()|truncate:60:"..."}</a></td>
			<td width="12%" align="center">{$submission->getDateFinalCompleted()|date_format:$dateFormatShort}</td>
			<td width="12%" align="center">
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
	</table>
</div>

{foreachelse}

<div class="hitlistNoRecords">
{translate key="editor.submissions.noSubmissions"}
</div>

{/foreach}
