{**
 * submissionsArchives.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show listing of submission archives.
 *
 * $Id$
 *}

<table width="100%" class="listing">
	<tr>
		<td colspan="6" class="headseparator"></td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.id"}</td>
		<td width="5%"><span class="disabled">MM-DD</span><br />{translate key="submissions.submit"}</td>
		<td width="5%">{translate key="submissions.sec"}</td>
		<td width="25%">{translate key="article.authors"}</td>
		<td width="30%">{translate key="article.title"}</td>
		<td width="25%" align="right">{translate key="submission.status"}</td>
	</tr>
	<tr>
		<td colspan="6" class="headseparator"></td>
	</tr>
	
	{foreach name="submissions" from=$submissions item=submission}
	{assign var="layoutAssignment" value=$submission->getLayoutAssignment()}
	{assign var="proofAssignment" value=$submission->getProofAssignment()}
	<tr valign="top">
		<td>{$submission->getArticleId()}</td>
		<td>{$submission->getDateSubmitted()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getSectionAbbrev()}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."}</td>
		<td><a href="{$requestPageUrl}/submissionEditing/{$submission->getArticleId()}" class="action">{$submission->getTitle()|truncate:60:"..."}</a></td>
		<td align="right">
			{if $submission->getStatus() == ARCHIVED}
				{translate key="submissions.archived"}
			{elseif $submission->getStatus() == SCHEDULED}
				{translate key="submissions.scheduled"}
			{elseif $submission->getStatus() == PUBLISHED}
				{print_issue_id articleId="$articleId"}			
			{elseif $submission->getStatus() == DECLINED}
				{translate key="common.declined"}								
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
