{**
 * submissionsInReview.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show section editor's submissions in review.
 *
 * $Id$
 *}
<a name="submissions"></a>

<table width="100%" class="listing">
	<tr><td colspan="7" class="headseparator">&nbsp;</td></tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.id"}</td>
		<td width="5%"><span class="disabled">MM-DD</span><br />{translate key="submissions.submit"}</td>
		<td width="5%">{translate key="submissions.sec"}</td>
		<td width="20%">{translate key="article.authors"}</td>
		<td width="30%">{translate key="article.title"}</td>
		<td width="30%">
			{translate key="submission.peerReview"}
			<table width="100%">
				<tr valign="top">
					<td width="33%" style="padding: 0 4px 0 0; font-size: 1.0em">{translate key="submission.ask"}</td>
					<td width="33%" style="padding: 0 4px 0 0; font-size: 1.0em">{translate key="submission.due"}</td>
					<td width="34%" style="padding: 0 4px 0 0; font-size: 1.0em">{translate key="submission.done"}</td>
				</tr>
			</table>
		</td>
		<td width="5%">{translate key="submissions.ruling"}</td>
	</tr>
	<tr><td colspan="7" class="headseparator">&nbsp;</td></tr>

{iterate from=submissions item=submission}

	{assign var="articleId" value=$submission->getArticleId()}
	<tr valign="top">
		<td>{$submission->getArticleId()}</td>
		<td>{$submission->getDateSubmitted()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getSectionAbbrev()|escape}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."|escape}</td>
		<td><a href="{url op="submissionReview" path=$submission->getArticleId()}" class="action">{$submission->getArticleTitle()|strip_unsafe_html|truncate:40:"..."}</a></td>
		<td>
		<table width="100%">
			{foreach from=$submission->getReviewAssignments() item=reviewAssignments}
				{foreach from=$reviewAssignments item=assignment name=assignmentList}
					{if !$assignment->getCancelled()}
					<tr valign="top">
						<td width="33%" style="padding: 0 4px 0 0; font-size: 1.0em">{if $assignment->getDateNotified()}{$assignment->getDateNotified()|date_format:$dateFormatTrunc}{else}&mdash;{/if}</td>
						<td width="33%" style="padding: 0 4px 0 0; font-size: 1.0em">{if $assignment->getDateCompleted() || !$assignment->getDateConfirmed()}&mdash;{else}{$assignment->getWeeksDue()|default:"&mdash;"}{/if}</td>
						<td width="34%" style="padding: 0 4px 0 0; font-size: 1.0em">{if $assignment->getDateCompleted()}{$assignment->getDateCompleted()|date_format:$dateFormatTrunc}{else}&mdash;{/if}</td>
					</tr>
					{/if}
				{foreachelse}
					<tr valign="top">
						<td width="33%" style="padding: 0 4px 0 0; font-size: 1.0em">&mdash;</td>
						<td width="33%" style="padding: 0 4px 0 0; font-size: 1.0em">&mdash;</td>
						<td width="34%" style="padding: 0 0 0 0; font-size:1.0em">&mdash;</td>
					</tr>
				{/foreach}
			{/foreach}			
		</table>
		</td>
		<td>
			{foreach from=$submission->getDecisions() item=decisions}
				{foreach from=$decisions item=decision name=decisionList}
					{if $smarty.foreach.decisionList.last}
						{$decision.dateDecided|date_format:$dateFormatTrunc}				
					{/if}
				{foreachelse}
					&mdash;
				{/foreach}
			{/foreach}			
		</td>
	</tr>
	<tr>
		<td colspan="7" class="{if $submissions->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $submissions->wasEmpty()}
	<tr>
		<td colspan="7" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="7" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="5" align="left">{page_info iterator=$submissions}</td>
		<td colspan="2" align="right">{page_links anchor="submissions" name="submissions" iterator=$submissions searchField=$searchField searchMatch=$searchMatch search=$search dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth section=$section}</td>
	</tr>
{/if}
</table>
