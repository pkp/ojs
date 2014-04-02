{**
 * templates/reviewer/completed.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show reviewer's submission archive.
 *
 *}
<div id="submissions">
<table class="listing" width="100%">
	<tr><td colspan="6" class="headseparator">&nbsp;</td></tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{sort_heading key="common.id" sort="id"}</td>
		<td width="10%"><span class="disabled">{translate key="submission.date.mmdd"}</span><br />{sort_heading key="common.assigned" sort="assignDate"}</td>
		<td width="10%">{sort_heading key="submissions.sec" sort="section"}</td>
		<td width="35%">{sort_heading key="article.title" sort="title"}</td>
		<td width="20%">{sort_heading key="submission.review" sort="review"}</td>
		<td width="20%">{sort_heading key="submission.editorDecision" sort="decision"}</td>
	</tr>
	<tr><td colspan="6" class="headseparator">&nbsp;</td></tr>
{iterate from=submissions item=submission}
	{assign var="articleId" value=$submission->getId()}
	{assign var="reviewId" value=$submission->getReviewId()}

	<tr valign="top">
		<td>{$articleId|escape}</td>
		<td>{$submission->getDateNotified()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getSectionAbbrev()|escape}</td>
		<td>{if !$submission->getDeclined()}<a href="{url op="submission" path=$reviewId}" class="action">{/if}{$submission->getLocalizedTitle()|strip_tags|truncate:60:"..."}{if !$submission->getDeclined()}</a>{/if}</td>
		<td>
			{if $submission->getDeclined()}
				{translate key="sectionEditor.regrets"}
			{else}
				{assign var=recommendation value=$submission->getRecommendation()}
				{if $recommendation === '' || $recommendation === null}
					&mdash;
				{else}
					{translate key=$reviewerRecommendationOptions.$recommendation}
				{/if}
			{/if}
		</td>
		<td>
			{if $submission->getCancelled() || $submission->getDeclined()}
				&mdash;
			{else}
			{* Display the most recent editor decision *}
			{assign var=round value=$submission->getRound()}
			{assign var=decisions value=$submission->getDecisions($round)}
			{foreach from=$decisions item=decision name=lastDecisionFinder}
				{if $smarty.foreach.lastDecisionFinder.last and $decision.decision == SUBMISSION_EDITOR_DECISION_ACCEPT}
					{translate key="editor.article.decision.accept"}
				{elseif $smarty.foreach.lastDecisionFinder.last and $decision.decision == SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS}
					{translate key="editor.article.decision.pendingRevisions"}
				{elseif $smarty.foreach.lastDecisionFinder.last and $decision.decision == SUBMISSION_EDITOR_DECISION_RESUBMIT}
					{translate key="editor.article.decision.resubmit"}
				{elseif $smarty.foreach.lastDecisionFinder.last and $decision.decision == SUBMISSION_EDITOR_DECISION_DECLINE}
					{translate key="editor.article.decision.decline"}
				{/if}
			{foreachelse}
				&mdash;
			{/foreach}
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
		<td colspan="3" align="right">{page_links anchor="submissions" name="submissions" iterator=$submissions sort=$sort sortDirection=$sortDirection}</td>
	</tr>
{/if}
</table>
</div>

