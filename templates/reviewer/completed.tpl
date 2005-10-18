{**
 * completed.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show reviewer's submission archive.
 *
 * $Id$
 *}

<table class="listing" width="100%">
	<tr><td colspan="8" class="headseparator">&nbsp;</td></tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.id"}</td>
		<td width="5%"><span class="disabled">MM-DD</span><br />{translate key="common.assigned"}</td>
		<td width="5%">{translate key="submissions.sec"}</td>
		<td width="35%">{translate key="article.title"}</td>
		<td width="25%">{translate key="editor.article.decision"}</td>
		<td width="5%">{translate key="submissions.completed"}</td>
		<td width="10%">{translate key="common.status"}</td>
		<td width="10%">{translate key="submissions.reviewRound"}</td>
	</tr>
	<tr><td colspan="8" class="headseparator">&nbsp;</td></tr>
{iterate from=submissions item=submission}
	{assign var="articleId" value=$submission->getArticleId()}
	{assign var="reviewId" value=$submission->getReviewId()}

	<tr valign="top">
		<td>{$articleId}</td>
		<td>{$submission->getDateNotified()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getSectionAbbrev()|escape}</td>
		<td><a href="{$requestPageUrl}/submission/{$reviewId}" class="action">{$submission->getArticleTitle()|strip_unsafe_html|truncate:60:"..."}</a></td>
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
		<td>
			{if $submission->getDeclined()}
				{translate key="submissions.declined"}
			{elseif $submission->getCancelled()}
				{translate key="common.cancelled"}
			{else}
				{$submission->getDateCompleted()|date_format:$dateFormatTrunc|default:"&mdash;"}
			{/if}
		</td>
		<td>
			{assign var="status" value=$submission->getStatus()}
			{if $submission->getCancelled() || $submission->getDeclined()}
				{translate  key="common.notApplicableShort"}
			{elseif $status == STATUS_ARCHIVED}
				{translate key="submissions.archived"}
			{elseif $status == STATUS_QUEUED}
				{translate key="submissions.queued"}
			{elseif $status == STATUS_SCHEDULED}
				{translate key="submissions.scheduled"}
			{elseif $status == STATUS_PUBLISHED}
				{print_issue_id articleId="$articleId"}			
			{elseif $status == STATUS_DECLINED}
				{translate key="submissions.declined"}								
			{/if}
		</td>
		<td>{$submission->getRound()}</td>
	</tr>

	<tr>
		<td colspan="8" class="{if $submissions->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $submissions->wasEmpty()}
	<tr>
		<td colspan="8" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="8" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="4" align="left">{page_info iterator=$submissions}</td>
		<td colspan="3" align="right">{page_links name="submissions" iterator=$submissions}</td>
	</tr>
{/if}
</table>
