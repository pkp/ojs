{**
 * templates/copyeditor/active.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show copyeditor's active submissions.
 *
 *}
<div id="submissions">
<table class="listing" width="100%">
	<tr><td class="headseparator" colspan="6">&nbsp;</td></tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{sort_heading key="common.id" sort="id"}</td>
		<td width="5%"><span class="disabled">{translate key="submission.date.mmdd"}</span><br />{sort_heading key="common.assigned" sort="assignDate"}</td>
		<td width="5%">{sort_heading key="submissions.sec" sort="section"}</td>
		<td width="30%">{sort_heading key="article.authors" sort="authors"}</td>
		<td width="35%">{sort_heading key="article.title" sort="title"}</td>
		<td width="15%" align="right">{sort_heading key="common.status" sort="status"}</td>
	</tr>
	<tr><td class="headseparator" colspan="6">&nbsp;</td></tr>

{iterate from=submissions item=submission}
	{assign var="copyeditingInitialSignoff" value=$submission->getSignoff('SIGNOFF_COPYEDITING_INITIAL')}
	{assign var="finalCopyeditSignoff" value=$submission->getSignoff('SIGNOFF_COPYEDITING_FINAL')}
	{assign var="articleId" value=$submission->getId()}
	<tr valign="top">
		<td>{$articleId|escape}</td>
		<td>{$copyeditingInitialSignoff->getDateNotified()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getSectionAbbrev()|escape}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."|escape}</td>
		<td><a href="{url op="submission" path=$articleId}" class="action">{$submission->getLocalizedTitle()|strip_tags|truncate:60:"..."}</a></td>
		<td align="right">
			{if not $copyeditingInitialSignoff->getDateCompleted()}
				{translate key="submission.copyedit.initialCopyedit"}
			{else}
				{if $copyeditingInitialSignoff->getDateCompleted() && not $finalCopyeditSignoff->getDateUnderway()}
					{translate key="submission.copyedit.initialCopyedit"} {translate key="common.completed"}
				{else}
					{if $finalCopyeditSignoff->getDateUnderway() and not $finalCopyeditSignoff->getDateCompleted()}
						{translate key="submission.copyedit.finalCopyedit"}
					{else}
						{translate key="submission.copyedit.finalCopyedit"} {translate key="common.completed"}
					{/if}
				{/if}
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="6" class="{if $submissions->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $submissions->wasEmpty()}
	<tr>
		<td colspan="6" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="6" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="4" align="left">{page_info iterator=$submissions}</td>
		<td colspan="2" align="right">{page_links anchor="submissions" name="submissions" iterator=$submissions sort=$sort sortDirection=$sortDirection}</td>
	</tr>
{/if}
</table>
</div>

