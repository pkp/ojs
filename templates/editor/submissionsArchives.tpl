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
		<td colspan="6" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.id"}</td>
		<td width="12%"><span class="disabled"></span><br />{translate key="submissions.submitted"}</td>
		<td width="5%">{translate key="submissions.sec"}</td>
		<td width="25%">{translate key="article.authors"}</td>
		<td width="30%">{translate key="article.title"}</td>
		<td width="23%" align="right">{translate key="common.status"}</td>
	</tr>
	<tr>
		<td colspan="6" class="headseparator">&nbsp;</td>
	</tr>
	
	{foreach name="submissions" from=$submissions item=submission}
	{assign var="layoutAssignment" value=$submission->getLayoutAssignment()}
	{assign var="proofAssignment" value=$submission->getProofAssignment()}
	<tr valign="top">
		<td>{$submission->getArticleId()}</td>
		<td>{$submission->getDateSubmitted()|date_format:$dateFormatShort}</td>
		<td>{$submission->getSectionAbbrev()}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."}</td>
		<td><a href="{$requestPageUrl}/submissionEditing/{$submission->getArticleId()}" class="action">{$submission->getTitle()|truncate:60:"..."}</a></td>
		<td align="right">
			{if $submission->getStatus() == STATUS_ARCHIVED}
				{translate key="submissions.archived"}
			{elseif $submission->getStatus() == STATUS_SCHEDULED}
				{translate key="submissions.scheduled"}
			{elseif $submission->getStatus() == STATUS_PUBLISHED}
				{print_issue_id articleId="$articleId"}			
			{elseif $submission->getStatus() == STATUS_DECLINED}
				{translate key="submissions.declined"}								
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="6" class="{if $smarty.foreach.submissions.last}end{/if}separator">&nbsp;</td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="6" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="6" class="endseparator">&nbsp;</td>
	</tr>
	{/foreach}

</table>
