{**
 * submissionsInReview.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the details of submissions in review.
 *
 * $Id$
 *}

<h3>{translate key="editor.submissions.activeAssignments"}</h3>
<p>{translate key="editor.submissions.sectionEditor"}:&nbsp;{$sectionEditor}</p>

<table width="100%" class="listing">
	<tr><td colspan="7" class="headseparator"></td></tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.id"}</td>
		<td width="9%">{translate key="editor.submissions.submitMMDD"}</td>
		<td width="6%">{translate key="editor.submissions.sec"}</td>
		<td>{translate key="article.authors"}</td>
		<td width="30%">{translate key="article.title"}</td>
		<td width="19%">
			{translate key="editor.submissions.peerReview"}
			<table width="100%" cols="3">
				<tr>
					<td style="padding: 0 4px 0 0; font-size: 1.0em">{translate key="editor.submissions.invite"}</td>
					<td style="padding: 0 4px 0 0; font-size: 1.0em">{translate key="editor.submissions.accept"}</td>
					<td style="padding: 0 4px 0 0; font-size: 1.0em">{translate key="common.done"}</td>
				</tr>
			</table>
		</td>
		<td width="9%">{translate key="editor.submissions.editorDecision"}</td>
	</tr>
	<tr><td colspan="7" class="headseparator"></td></tr>

{foreach name=submissions from=$submissions item=submission}

	{assign var="articleId" value=$submission->getArticleId()}
	<tr valign="top">
		<td>{$submission->getArticleId()}</td>
		<td>{$submission->getDateSubmitted()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getSectionAbbrev()}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."}</td>
		<td><a href="{$requestPageUrl}/submissionReview/{$submission->getArticleId()}" class="action">{$submission->getTitle()|truncate:40:"..."}</a></td>
		<td>
		<table width="100%" cols="3">
			{foreach from=$submission->getReviewAssignments() item=reviewAssignments}
				{foreach from=$reviewAssignments item=assignment name=assignmentList}
					<tr>
						<td style="padding: 0 4px 0 0; font-size: 1.0em">{if $assignment->getDateInitiated()}{$assignment->getDateInitiated()|date_format:$dateFormatTrunc}{else}&mdash;{/if}</td>
						<td style="padding: 0 4px 0 0; font-size: 1.0em">{if $assignment->getDateConfirmed()}{$assignment->getDateConfirmed()|date_format:$dateFormatTrunc}{else}&mdash;{/if}</td>
						<td style="padding: 0 4px 0 0; font-size: 1.0em">{if $assignment->getDateCompleted()}{$assignment->getDateCompleted()|date_format:$dateFormatTrunc}{else}&mdash;{/if}</td>
					</tr>
					{foreachelse}
						<tr>
							<td style="padding: 0 4px 0 0; font-size: 1.0em">&mdash;</td>
							<td style="padding: 0 4px 0 0; font-size: 1.0em">&mdash;</td>
							<td style="padding: 0 0 0 0; font-size:1.0em">&mdash;</td>
						</tr>
					{/foreach}
				{/foreach}			
			</table>
		</td>
		<td>
			{foreach from=$submission->getDecisions() item=decisions}
				{foreach from=$decisions item=decision name=decisionList}
					{if $smarty.foreach.decisionList.last}
						{$decision.dateDecided|date_format:$dateMonthDay}				
					{/if}
				{foreachelse}
					&mdash;
				{/foreach}
			{/foreach}			
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
		<td colspan="7" class="bottomseparator"></td>
	</tr>
{/foreach}

</table>
