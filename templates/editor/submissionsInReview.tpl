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

<div id="summary">
	<table>
		<tr>
			<td>{translate key="editor.submissions.activeAssignments"}</td>
			<td align="right">{translate key="editor.submissions.editor}:&nbsp;{$editor}</td>
		</tr>
		<tr>
			<td colspan="2">{translate key="editor.submissions.showBy"}:&nbsp;<select name="section" onchange="location.href='{$pageUrl}/editor/index/submissionsInReview?section='+this.options[this.selectedIndex].value" size="1" class="selectMenu">{html_options options=$sectionOptions selected=$section}</select></td>
		</tr>
	</table>
</div>

<div id="hitlistTitles">
	<table>
		<tr>
			<td width="10%" align="center">{translate key="editor.submissions.secEditor"}</td>
			<td width="9%" align="center"><a href="{$pageUrl}/editor/index/submissionsInReview?sort=submitted&amp;order={$order}{if $section}&amp;section={$section}{/if}" class="sortColumn">{translate key="editor.submissions.submitMMDD"}</a></td>
			<td width="6%" align="center">{translate key="editor.submissions.sec"}</td>
			<td align="center">{translate key="editor.submissions.authors"}</td>
			<td width="19%" align="center">
			<table style="border: none;">
			<tr style="border: none;">
				<td align="center" colspan="3" style="border: none;">{translate key="editor.submissions.peerReview"}</td>
			</tr>
			<tr style="border: none; border-top: 1px solid #CCC;">
				<td width="33%" align="center" style="border-top: 1px solid #CCC;">{translate key="editor.submissions.invite"}</td>
				<td width="33%" align="center" style="border-top: 1px solid #CCC;">{translate key="editor.submissions.accept"}</td>
				<td width="33%" align="center" style="border: none; border-top: 1px solid #CCC;">{translate key="editor.submissions.done"}</td>
			</tr>
			</table>
			</td>
			<td width="9%" align="center">{translate key="editor.submissions.editorDecision"}</td>
		</tr>
	</table>
</div>

{foreach from=$submissions item=submission}

<div class="hitlistRecord">
	<table>
		{assign var="articleId" value=$submission->getArticleId()}
		{assign var="onclick" value="onclick=\"javascript:loadUrl('$requestPageUrl/submissionReview/$articleId');\""}
		<tr class="{cycle values="row,rowAlt"}" {$onclick}>
			<td width="10%" align="center">{assign var="editAssignment" value=$submission->getEditor()}{$editAssignment->getEditorFullName()}</td>
			<td width="9%" align="center">{$submission->getDateSubmitted()|date_format:$dateMonthDay}</td>
			<td width="6%" align="center">{$submission->getSectionAbbrev()}</td>
			<td>
				{foreach from=$submission->getAuthors() item=author name=authorList}
					{$author->getLastName()}{if !$smarty.foreach.authorList.last},{/if}
				{/foreach}
			</td>
			<td width="19%" align="center">
			<table style="border: none;">
			{foreach from=$submission->getReviewAssignments() item=reviewAssignments}
				{foreach from=$reviewAssignments item=assignment name=assignmentList}
					{assign var="bottomBorder" value="border-bottom: 1px solid #CCC;"}
					<tr style="border: none; {$bottomBorder}">
						<td width="33%" align="center" style="{$bottomBorder}">{if $assignment->getDateInitiated()}{$assignment->getDateInitiated()|date_format:$dateMonthDay}{else}&mdash;{/if}</td>
						<td width="33%" align="center" style="{$bottomBorder}">{if $assignment->getDateConfirmed()}{$assignment->getDateConfirmed()|date_format:$dateMonthDay}{else}&mdash;{/if}</td>
						<td width="33%" align="center" style="border: none; {$bottomBorder}">{if $assignment->getDateCompleted()}{$assignment->getDateCompleted()|date_format:$dateMonthDay}{else}&mdash;{/if}</td>
					</tr>
				{foreachelse}
					&mdash;
				{/foreach}
			{/foreach}			
			</table>
			</td>
			<td width="9%" align="center">
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
	</table>
</div>

{foreachelse}

<div class="hitlistNoRecords">
{translate key="editor.submissions.noSubmissions"}
</div>

{/foreach}
