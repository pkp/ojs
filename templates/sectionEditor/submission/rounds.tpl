{**
 * rounds.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate displaying past rounds for a submission.
 *
 * $Id$
 *}

<a name="rounds"></a>
<h3>{translate key="sectionEditor.regrets.regretsAndCancels"}</h3>

<table width="100%" class="listing">
	<tr valign="top">
		<td class="heading" width="30%">{translate key="user.name"}</td>
		<td class="heading" width="15%">{translate key="submission.request"}</td>
		<td class="heading" width="15%">{translate key="sectionEditor.regrets.result"}</td>
	</tr>
{foreach from=$cancelsAndRegrets item=cancelOrRegret}
	<tr valign="top">
		<td>{$cancelOrRegret->getReviewerFullName()}</td>
		<td>
			{if $cancelOrRegret->getDateNotified()}
				{$cancelOrRegret->getDateNotified()|date_format:$dateFormatTrunc}
			{else}
				&mdash;
			{/if}
		</td>
		<td>
			{if $cancelOrRegret->getCancelled()}
				{translate key="common.cancelled"}
			{else}
				{translate key="sectionEditor.regrets.regret"}
			{/if}
		</td>
	</tr>
{foreachelse}
	<tr valign="top">
		<td class="nodata">{translate key="common.none}</td>
	</tr>
{/foreach}
</table>

<div class="separator"></div>

{section name=round loop=$numRounds}
{assign var=round value=$smarty.section.round.index}
{assign var=roundAssignments value=$reviewAssignments[$round]}
{assign var=roundDecisions value=$editorDecisions[$round]}

<h3>{translate key="sectionEditor.regrets.reviewRound" round=$round+1}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td class="label" width="20%">{translate key="editor.article.reviewVersion"}</td>
		<td class="data" width="80%">
			FIXME
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">{translate key="submission.supplementaryFiles"}</td>
		<td class="data" width="80%">
			FIXME
		</td>
	</tr>
</table>

{assign var="start" value="A"|ord}

{foreach from=$roundAssignments item=reviewAssignment key=reviewKey}

<div class="separator"></div>
<h4>{translate key="user.role.reviewer"} {$reviewKey+$start|chr} {$reviewAssignment->getReviewerFullName()}</h4>

<table width="100%" class="listing">
	<tr valign="top">
		<td width="20%">{translate key="reviewer.article.schedule"}</td>
		<td width="20%" class="heading">{translate key="submission.request"}</td>
		<td width="20%" class="heading">{translate key="submission.underway"}</td>
		<td width="20%" class="heading">{translate key="submission.due"}</td>
		<td width="20%" class="heading">{translate key="submission.acknowledge"}</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td>
			{if $reviewAssignment->getDateNotified()}
				{$reviewAssignment->getDateNotified()|date_format:$dateFormatTrunc}
			{else}
				&mdash;
			{/if}
		</td>
		<td>
			{if $reviewAssignment->getDateConfirmed()}
				{$reviewAssignment->getDateConfirmed()|date_format:$dateFormatTrunc}
			{else}
				&mdash;
			{/if}
		</td>
		<td>
			{if $reviewAssignment->getDateDue()}
				{$reviewAssignment->getDateDue()|date_format:$dateFormatTrunc}
			{else}
				&mdash;
			{/if}
		</td>
		<td>
			{if $reviewAssignment->getDateAcknowledged()}
				{$reviewAssignment->getDateAcknowledged()|date_format:$dateFormatTrunc}
			{else}
				&mdash;
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td>{translate key="submission.recommendation"}</td>
		<td colspan="4">
			{if $reviewAssignment->getRecommendation()}
				{assign var="recommendation" value=$reviewAssignment->getRecommendation()}
				{translate key=$reviewerRecommendationOptions.$recommendation}
			{else}
				{translate key="common.none"}
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="reviewer.article.reviewerComments"}</td>
		<td colspan="4">
			{if $reviewAssignment->getMostRecentPeerReviewComment()}
				{assign var="comment" value=$reviewAssignment->getMostRecentPeerReviewComment()}
				<a href="javascript:openComments('{$requestPageUrl}/viewPeerReviewComments/{$submission->getArticleId()}/{$reviewAssignment->getReviewId()}#{$comment->getCommentId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a> {$comment->getDatePosted()|date_format:$dateFormatShort}
			{else}
				<a href="javascript:openComments('{$requestPageUrl}/viewPeerReviewComments/{$submission->getArticleId()}/{$reviewAssignment->getReviewId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>
			{/if}
		</td>
	</tr>
 	<tr valign="top">
		<td class="label">{translate key="reviewer.article.uploadedFile"}</td>
		<td colspan="4">
			<table width="100%" class="data">
				{foreach from=$reviewAssignment->getReviewerFileRevisions() item=reviewerFile key=key}
				<tr valign="top">
					<td valign="middle">
						<form name="authorView{$reviewAssignment->getReviewId()}" method="post" action="{$requestPageUrl}/makeReviewerFileViewable">
							<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$reviewerFile->getFileId()}/{$reviewerFile->getRevision()}" class="file">{$reviewerFile->getFileName()}</a> {$reviewerFile->getDateModified()|date_format:$dateFormatShort}
							<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}" />
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
							<input type="hidden" name="fileId" value="{$reviewerFile->getFileId()}" />
							<input type="hidden" name="revision" value="{$reviewerFile->getRevision()}" />
							{translate key="editor.article.showAuthor"} <input type="checkbox"
name="viewable" value="1"{if $reviewerFile->getViewable()} checked="checked"{/if} />
							<input type="submit" value="{translate key="common.record"}" class="button" />
						</form>
					</td>
				</tr>
				{foreachelse}
				<tr valign="top">
					<td>{translate key="common.none"}</td>
				</tr>
				{/foreach}
			</table>
		</td>
	</tr>
	{if $rateReviewerOnTimeliness or $rateReviewerOnQuality}
		{if $rateReviewerOnTimeliness}
			<tr valign="top">
				<td class="label">{translate key="editor.article.timeliness"}</td>
				<td>
					{assign var=timeliness value=$reviewAssignment->getTimeliness()}
					{if $timeliness}
						{translate key=$reviewerRatingOptions[$timeliness]}
					{else}
						{translate key="common.none"}
					{/if}
				</td>
			</tr>
		{/if}
		{if $rateReviewerOnQuality}
			<tr valign="top">
				<td class="label">{translate key="editor.article.quality"}</td>
				<td>
					{assign var=quality value=$reviewAssignment->getQuality()}
					{if $quality}
						{translate key=$reviewerRatingOptions[$quality]}
					{else}
						{translate key="common.none"}
					{/if}
				</td>
			</tr>
		{/if}
	{/if}
</table>

{/foreach}

<div class="separator"></div>

<h3>{translate key="sectionEditor.regrets.decisionRound" round=$round+1}</h3>

{assign var=authorFiles value=$submission->getAuthorFileRevisions($round+1)}
{assign var=editorFiles value=$submission->getEditorFileRevisions($round+1)}

<table class="data" width="100%">
	<tr valign="top">
		<td class="label" width="20%">{translate key="editor.article.decision"}</td>
		<td class="value" width="80%">
			{foreach from=$roundDecisions item=editorDecision key=decisionKey}
				{if $decisionKey neq 0} | {/if}
				{assign var="decision" value=$editorDecision.decision}
				{translate key=$editorDecisionOptions.$decision} {$editorDecision.dateDecided|date_format:$dateFormatShort}
			{foreachelse}
				{translate key="common.none"}
			{/foreach}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">{translate key="submission.editorAuthorComments"}</td>
		<td class="value" width="80%">
			{if $submission->getMostRecentEditorDecisionComment()}
				{assign var="comment" value=$submission->getMostRecentEditorDecisionComment()}
				<a href="javascript:openComments('{$requestPageUrl}/viewEditorDecisionComments/{$submission->getArticleId()}#{$comment->getCommentId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a> {$comment->getDatePosted()|date_format:$dateFormatShort}
			{else}
				<a href="javascript:openComments('{$requestPageUrl}/viewEditorDecisionComments/{$submission->getArticleId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>
			{/if}
		</td>
	</tr>
	{foreach from=$authorFiles item=authorFile key=key}
		<tr valign="top">
			{if !$authorRevisionExists}
				{assign var="authorRevisionExists" value=true}
				<td width="20%" class="label" rowspan="{$authorFiles|@count}" class="label">{translate key="submission.authorVersion"}</td>
			{/if}
			<td width="80%" class="value"><a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$authorFile->getFileId()}/{$authorFile->getRevision()}" class="file">{$authorFile->getFileName()}</a> {$authorFile->getDateModified()|date_format:$dateFormatShort}</td>
		</tr>
	{foreachelse}
		<tr valign="top">
			<td width="20%" class="label">{translate key="submission.authorVersion"}</td>
			<td width="80%" colspan="4" class="nodata">{translate key="common.none"}</td>
		</tr>
	{/foreach}
	{foreach from=$editorFiles item=editorFile key=key}
		<tr valign="top">
			{if !$editorRevisionExists}
				{assign var="editorRevisionExists" value=true}
				<td width="20%" class="label" rowspan="{$editorFiles|@count}" class="label">{translate key="submission.editorVersion"}</td>
			{/if}

			<td width="30%"><a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$editorFile->getFileId()}/{$editorFile->getRevision()}" class="file">{$editorFile->getFileName()}</a> {$editorFile->getDateModified()|date_format:$dateFormatShort}</td>
		</tr>
	{foreachelse}
		<tr valign="top">
			<td width="20%" class="label">{translate key="submission.editorVersion"}</td>
			<td width="80%" colspan="4" class="nodata">{translate key="common.none"}</td>
		</tr>
	{/foreach}
</table>

<div class="separator"></div>


{/section}

