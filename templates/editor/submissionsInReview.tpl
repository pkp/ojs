{**
 * submissionsInReview.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show editor's submissions in review.
 *
 * $Id$
 *}

<table width="100%" class="listing">
	<tr>
		<td colspan="8" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.id"}</td>
		<td width="5%"><span class="disabled">MM-DD</span><br />{translate key="submissions.submit"}</td>
		<td width="5%">{translate key="submissions.sec"}</td>
		<td width="15%">{translate key="article.authors"}</td>
		<td width="30%">{translate key="article.title"}</td>
		<td width="30%">
			{translate key="submission.peerReview"}
			<table width="100%" cols="3">
				<tr>
					<td style="padding: 0 4px 0 0; font-size: 1.0em">{translate key="submission.request"}</td>
					<td style="padding: 0 4px 0 0; font-size: 1.0em">{translate key="submission.start"}</td>
					<td style="padding: 0 0 0 0; font-size: 1.0em">{translate key="submission.complete"}</td>
				</tr>
			</table>
		</td>
		<td width="5%">{translate key="submissions.editorRuling"}</td>
		<td width="5%">{translate key="article.sectionEditor"}</td>
	</tr>
	<tr>
		<td colspan="8" class="headseparator">&nbsp;</td>
	</tr>
	
	{foreach name=submissions from=$submissions item=submission}
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
					<td style="padding: 0 4px 0 0; font-size: 1.0em">{if $assignment->getDateNotified()}{$assignment->getDateNotified()|date_format:$dateFormatTrunc}{else}&mdash;{/if}</td>
					<td style="padding: 0 4px 0 0; font-size: 1.0em">{if $assignment->getDateConfirmed()}{$assignment->getDateConfirmed()|date_format:$dateFormatTrunc}{else}&mdash;{/if}</td>
					<td style="padding: 0 0 0 0; font-size: 1.0em">{if $assignment->getDateCompleted()}{$assignment->getDateCompleted()|date_format:$dateFormatTrunc}{else}&mdash;{/if}</td>
				</tr>
				{foreachelse}
				<tr>
					<td style="padding: 0 4px 0 0; font-size: 1.0em">&mdash;</td>
					<td style="padding: 0 4px 0 0; font-size: 1.0em">&mdash;</td>
					<td style="padding: 0 0 0 0; font-size: 1.0em">&mdash;</td>
				</tr>
				{/foreach}
			{foreachelse}
				<tr>
					<td style="padding: 0 4px 0 0; font-size: 1.0em">&mdash;</td>
					<td style="padding: 0 4px 0 0; font-size: 1.0em">&mdash;</td>
					<td style="padding: 0 0 0 0; font-size: 1.0em">&mdash;</td>
				</tr>
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
			{foreachelse}
				&mdash;
			{/foreach}
		</td>
		<td>{assign var="editAssignment" value=$submission->getEditor()}{$editAssignment->getEditorInitials()}</td>
	</tr>
	<tr>
		<td colspan="8" class="{if $smarty.foreach.submissions.last}end{/if}separator">&nbsp;</td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="8" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="8" class="endseparator">&nbsp;</td>
	</tr>
	{/foreach}

</table>
