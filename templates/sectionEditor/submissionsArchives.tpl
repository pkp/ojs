{**
 * submissionsArchives.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the details of submissions in archives.
 *
 * $Id$
 *}

<h3>{translate key="editor.submissions.activeAssignments"}</h3>
<p>{translate key="editor.submissions.sectionEditor"}:&nbsp;{$sectionEditor}</p>

<table width="100%" class="listing">
	<tr><td colspan="6" class="headseparator"></td></tr>
	<tr class="heading">
		<td width="5%">{translate key="common.id"}</td>
		<td width="11%"><a href="{$pageUrl}/sectionEditor/index/submissionsArchives?sort=submitted&amp;order={$order}{if $section}&amp;section={$section}{/if}" class="sortColumn">{translate key="editor.submissions.submitted"}</a></td>
		<td width="6%">{translate key="editor.submissions.sec"}</td>
		<td>{translate key="article.authors"}</td>
		<td width="40%">{translate key="article.title"}</td>
		<td width="12%">{translate key="common.status"}</td>
	</tr>
	<tr><td colspan="6" class="headseparator"></td></tr>

{foreach name=submissions from=$submissions item=submission}
	{assign var="layoutAssignment" value=$submission->getLayoutAssignment()}
	{assign var="proofAssignment" value=$submission->getProofAssignment()}
	{assign var="articleId" value=$submission->getArticleId()}
	<tr align="top">
		<td><a href="{$requestPageUrl}/submissionEditing/{$articleId}">{$submission->getArticleId()}</a></td>
		<td>{$submission->getDateSubmitted()|date_format:$dateFormatShort}</td>
		<td>{$submission->getSectionAbbrev()}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."}</td>
		<td><a href="{$requestPageUrl}/submissionEditing/{$articleId}" class="action">{$submission->getArticleTitle()|truncate:60:"..."}</a></td>
		<td>
			{assign var="status" value=$submission->getStatus()}
			{if $status == 0}
				{translate key="editor.submissions.archived"}
			{elseif $status == 2}
				{translate key="editor.submissions.scheduled"}
			{elseif $status == 3}
				{print_issue_id articleId="$articleId"}
			{elseif $status == 4}
				{translate key="editor.submissions.declined"}					
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
		<td colspan="6" class="bottomseparator"></td>
	</tr>
{/foreach}
</table>
