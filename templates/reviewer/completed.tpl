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
	<tr><td colspan="7" class="headseparator"></td></tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.id"}</td>
		<td width="5%"><span class="disabled">MM-DD</span><br />{translate key="submissions.assigned"}</td>
		<td width="5%">{translate key="submissions.sec"}</td>
		<td width="45%">{translate key="article.title"}</td>
		<td width="25%">{translate key="editor.article.decision"}</td>
		<td width="5%">{translate key="submissions.completed"}</td>
		<td width="10%">{translate key="common.status"}</td>
	</tr>
	<tr><td colspan="7" class="headseparator"></td></tr>
{foreach name=submissions from=$submissions item=submission}
	{assign var="articleId" value=$submission->getArticleId()}
	{assign var="reviewId" value=$submission->getReviewId()}

	<tr valign="top">
		<td>{$articleId}</td>
		<td>{$submission->getDateNotified()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getSectionAbbrev()}</td>
		<td><a href="{$requestPageUrl}/submission/{$reviewId}" class="action">{$submission->getArticleTitle()|truncate:60:"..."}</a></td>
		<td>
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
		</td>
		<td>{$submission->getDateCompleted()|date_format:$dateFormatTrunc|default:"&mdash;"}</td>
		<td>
			{assign var="status" value=$submission->getStatus()}
			{if $status == ARCHIVED}
				{translate key="submissions.archived"}
			{elseif $status == QUEUED}
				{translate key="submissions.queued"}
			{elseif $status == SCHEDULED}
				{translate key="submissions.scheduled"}
			{elseif $status == PUBLISHED}
				{print_issue_id articleId="$articleId"}			
			{elseif $status == DECLINED}
				{translate key="editor.submissions.declined"}								
			{/if}
		</td>
	</tr>

	<tr>
		<td colspan="7" class="{if $smarty.foreach.submissions.last}end{/if}separator"></td>
	</tr>
{foreachelse}
	<tr>
		<td colspan="7" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="7" class="endseparator"></td>
	</tr>

{/foreach}
</table>
