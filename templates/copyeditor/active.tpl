{**
 * active.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show copyeditor's active submissions.
 *
 * $Id$
 *}

<table class="listing" width="100%">
	<tr><td class="headseparator" colspan="6"></td></tr>
	<tr class="heading" valign="bottom">
			<td width="5%">{translate key="common.id"}</td>
			<td width="5%"><span class="disabled">MM-DD</span><br />{translate key="common.assign"}</td>
			<td width="5%">{translate key="submissions.sec"}</td>
			<td width="30%">{translate key="article.authors"}</td>
			<td width="40%">{translate key="article.title"}</td>
			<td width="15%" align="right">{translate key="common.status"}</td>
		</tr>
	<tr><td class="headseparator" colspan="6"></td></tr>

{foreach name=submissions from=$submissions item=submission}
	{assign var="articleId" value=$submission->getArticleId()}
	<tr valign="top">
		<td>{$articleId}</td>
		<td>{$submission->getDateNotified()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getSectionAbbrev()}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."}</td>
		<td><a href="{$requestPageUrl}/submission/{$articleId}" class="action">{$submission->getArticleTitle()|truncate:60:"..."}</a></td>
		<td align="right">
			{if not $submission->getDateCompleted()}
				{translate key="submissions.step1"}
			{else}
				{translate key="submissions.step3"}
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

