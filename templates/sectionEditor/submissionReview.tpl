{**
 * submissionReview.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the details of a submission.
 *
 * DO: Reviewer comments need to be implemented.
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.submissionReview"}
{assign var="pageId" value="sectionEditor.submissionReview"}
{include file="common/header.tpl"}

<ul id="tabnav">
	<li><a href="{$requestPageUrl}/summary/{$submission->getArticleId()}">{translate key="submission.summary"}</a></li>
	<li><a href="{$requestPageUrl}/submission/{$submission->getArticleId()}">{translate key="submission.submission"}</a></li>
	<li><a href="{$requestPageUrl}/submissionReview/{$submission->getArticleId()}" class="active">{translate key="submission.submissionReview"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEditing/{$submission->getArticleId()}">{translate key="submission.submissionEditing"}</a></li>
	<li><a href="{$requestPageUrl}/submissionHistory/{$submission->getArticleId()}">{translate key="submission.submissionHistory"}</a></li>
</ul>
<ul id="subnav">
{section name="tabRounds" start=0 loop=$submission->getCurrentRound()}
	{assign var="tabRound" value=$smarty.section.tabRounds.index+1}
	<li><a href="{$requestPageUrl}/submissionReview/{$submission->getArticleId()}/{$tabRound}" {if $round eq $tabRound}class="active"{/if}>{translate key="submission.round" round=$tabRound}</a></li>
{/section}
</ul>

<div class="tableContainer">
<table width="100%">
<tr class="submissionRow">
	<td class="submissionBox">
		<div class="leftAligned">
			<div>{foreach from=$submission->getAuthors() item=author key=authorKey}{if $authorKey neq 0},{/if} {$author->getFullName()}{/foreach}</div>
			<div class="submissionTitle">{$submission->getArticleTitle()}</div>
		</div>
		<div class="submissionId">{$submission->getArticleId()}</div>
	</td>
</tr>
</table>
</div>

<br />

<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td colspan="2">{translate key="submission.reviewVersion"}</td>
</tr>
<tr class="submissionRow">
	<td class="submissionBox" width="50%" valign="top">
		<span class="boldText">{translate key="editor.article.originalFile"}:</span>
		{if $submissionFile}
			<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$submissionFile->getFileId()}/{$submissionFile->getRevision()}" class="file">{$submissionFile->getFileName()}</a> {$submissionFile->getDateModified()|date_format:$dateFormatShort}
		{else}
			{translate key="common.none"}
		{/if}
		{if $reviewFile}
			<div class="indented">
				<form method="post" action="{$requestPageUrl}/designateReviewVersion">
					<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
					<input type="checkbox" name="designate" value="1" disabled="disabled"> <span class="disabledText">{translate key="editor.article.designateReviewVersion"}</span>
					<input type="submit" value="{translate key="common.record"}" disabled="disabled">
				</form>
			</div>
		{else}
			<div class="indented">
				<form method="post" action="{$requestPageUrl}/designateReviewVersion">
					<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
					<input type="checkbox" name="designate" value="1"> {translate key="editor.article.designateReviewVersion"}
					<input type="submit" value="{translate key="common.record"}">
				</form>
			</div>	
		{/if}
	</td>
	<td class="submissionBox" width="50%" valign="top">
		<span class="boldText">{translate key="editor.article.reviewVersion"}:</span>
		{if $reviewFile}
			<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$reviewFile->getFileId()}/{$reviewFile->getRevision()}" class="file">{$reviewFile->getFileName()}</a> {$reviewFile->getDateModified()|date_format:$dateFormatShort}
		{else}
			{translate key="common.none"}
		{/if}
		<div class="indented">
			<form method="post" action="{$requestPageUrl}/uploadReviewVersion" enctype="multipart/form-data">
				<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
				<input type="file" name="upload">
				<input type="submit" name="submit" value="{translate key="common.upload"}">
			</form>
		</div>
	</td>
</tr>
</table>
</div>

<br />

<a name="peerReview"></a>
<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.peerReview"}</td>
</tr>
{assign var="start" value="A"|ord} 
{assign var="numReviewAssignments" value=$reviewAssignments|@count} 
{foreach from=$reviewAssignments item=reviewAssignment key=reviewKey}
{assign var="reviewId" value=$reviewAssignment->getReviewId()}
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td class="reviewLabel">
					<span class="boldText">{translate key="user.role.reviewer"} {$reviewKey+$start|chr}</span>
				</td>
				<td>
					<table class="plainFormat" width="100%">
						<tr>
							<td width="25%">{$reviewAssignment->getReviewerFullName()}</td>
							<td align="center" width="25%">
								{if $reviewAssignment->getRecommendation()}
									<form method="post" action="{$requestPageUrl}/reinitiateReview">
										<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
										<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
										<input type="submit" value="{translate key="editor.article.initiate"}" disabled="disabled">
									</form>
								{elseif not $reviewAssignment->getDateInitiated() and not $reviewAssignment->getCancelled()}
									<form method="post" action="{$requestPageUrl}/initiateReview">
										<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
										<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
										<input type="submit" value="{translate key="editor.article.initiate"}">
									</form>
								{elseif $reviewAssignment->getDateInitiated() and not $reviewAssignment->getCancelled()}
									<form method="post" action="{$requestPageUrl}/initiateReview">
										<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
										<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
										<input type="submit" value="{translate key="editor.article.initiate"}" disabled="disabled">
									</form>
								{elseif $reviewAssignment->getDateInitiated() and $reviewAssignment->getCancelled()}
									<form method="post" action="{$requestPageUrl}/reinitiateReview">
										<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
										<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
										<input type="submit" value="{translate key="editor.article.reinitiate"}">
									</form>
								{/if}	
							</td>
							<td align="center" width="25%">
								{if $reviewAssignment->getRecommendation()}
									<form method="post" action="{$requestPageUrl}/removeReview">
										<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
										<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
										<input type="submit" value="{translate key="editor.article.clear"}" disabled="disabled">
									</form>
								{elseif not $reviewAssignment->getDateInitiated() and not $reviewAssignment->getCancelled()}
									<form method="post" action="{$requestPageUrl}/removeReview">
										<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
										<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
										<input type="submit" value="{translate key="editor.article.clear"}">
									</form>
								{elseif $reviewAssignment->getDateInitiated() and not $reviewAssignment->getCancelled()}
									<form method="post" action="{$requestPageUrl}/removeReview">
										<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
										<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
										<input type="submit" value="{translate key="editor.article.clear"}" disabled="disabled">
									</form>
								{elseif $reviewAssignment->getDateInitiated() and $reviewAssignment->getCancelled()}
									<form method="post" action="{$requestPageUrl}/removeReview">
										<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
										<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
										<input type="submit" value="{translate key="editor.article.clear"}" disabled="disabled">
									</form>
								{/if}
							</td>
							<td align="center" width="25%">
								{if $reviewAssignment->getRecommendation()}
									<form method="post" action="{$requestPageUrl}/cancelReview">
										<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
										<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
										<input type="submit" value="{translate key="common.cancel"}" disabled="disabled">
									</form>
								{elseif not $reviewAssignment->getDateInitiated() and not $reviewAssignment->getCancelled()}
									<form method="post" action="{$requestPageUrl}/cancelReview">
										<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
										<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
										<input type="submit" value="{translate key="common.cancel"}" disabled="disabled">
									</form>
								{elseif $reviewAssignment->getDateInitiated() and not $reviewAssignment->getCancelled()}
									<form method="post" action="{$requestPageUrl}/cancelReview">
										<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
										<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
										<input type="submit" value="{translate key="common.cancel"}">
									</form>
								{elseif $reviewAssignment->getDateInitiated() and $reviewAssignment->getCancelled()}
									<form method="post" action="{$requestPageUrl}/cancelReview">
										<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
										<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
										<input type="submit" value="{translate key="common.cancel"}" disabled="disabled">
									</form>
								{/if}
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</td>
</tr>
<tr class="submissionRow">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td class="reviewLabel">
					<span class="boldText">{translate key="reviewer.article.schedule"}</span>
				</td>
				<td colspan="3">
					<table class="plainFormat" width="100%">
						<tr>
							<td align="center" width="25%"><strong>{translate key="submission.request"}</strong></td>
							<td align="center" width="25%"><strong>{translate key="submission.acceptance"}</strong></td>
							<td align="center" width="25%">
								<form method="post" action="{$requestPageUrl}/setDueDate/{$reviewAssignment->getArticleId()}/{$reviewAssignment->getReviewId()}">
									<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
									<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
									<input type="submit" value=" {translate key="submission.due"}">
								</form>
							</td>
							<td align="center" width="25%">
								<form method="post" action="{$requestPageUrl}/thankReviewer">
									<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
									<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
									<input type="submit" value="{translate key="submission.thank"}">
								</form>
							</td>
						</tr>
						<tr>
							<td align="center">{if $reviewAssignment->getDateNotified()}{$reviewAssignment->getDateNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
							<td align="center">{if $reviewAssignment->getDateConfirmed()}{$reviewAssignment->getDateConfirmed()|date_format:$dateFormatShort}{else}-{/if}</td>
							<td align="center">{if $reviewAssignment->getDateDue()}{$reviewAssignment->getDateDue()|date_format:$dateFormatShort}{else}<a href="{$requestPageUrl}/setDueDate/{$reviewAssignment->getArticleId()}/{$reviewAssignment->getReviewId()}">{translate key="editor.article.setDueDate"}</a>{/if}</td>
							<td align="center">{if $reviewAssignment->getDateAcknowledged()}{$reviewAssignment->getDateAcknowledged()|date_format:$dateFormatShort}{else}-{/if}</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="reviewLabel">
					<span class="boldText">{translate key="reviewer.article.recommendation"}</span>
				</td>
				<td colspan="3">
					<table class="plainFormat" width="100%">
						<tr>
							<td width="75%">
								{if $reviewAssignment->getRecommendation()}
									{assign var="recommendation" value=$reviewAssignment->getRecommendation()}
									<span class="boldTextAlt">{translate key=$reviewerRecommendationOptions.$recommendation}</span>
								{else}
									{translate key="common.none"}
								{/if}
							</td>
							<td width="25%">
								{if $reviewAssignment->getRecommendation()}
									<form method="post" action="{$requestPageUrl}/enterReviewerRecommendation">
										<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
										<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
										<input type="submit" value="{translate key="editor.article.enterRecommendation"}" disabled="disabled">
									</form>
								{elseif not $reviewAssignment->getDateInitiated() and not $reviewAssignment->getCancelled()}
									<form method="post" action="{$requestPageUrl}/enterReviewerRecommendation">
										<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
										<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
										<input type="submit" value="{translate key="editor.article.enterRecommendation"}" disabled="disabled">
									</form>
								{elseif $reviewAssignment->getDateInitiated() and not $reviewAssignment->getCancelled()}
									<form method="post" action="{$requestPageUrl}/enterReviewerRecommendation">
										<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
										<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
										<input type="submit" value="{translate key="editor.article.enterRecommendation"}">
									</form>
								{elseif $reviewAssignment->getDateInitiated() and $reviewAssignment->getCancelled()}
									<form method="post" action="{$requestPageUrl}/enterReviewerRecommendation">
										<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
										<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
										<input type="submit" value="{translate key="editor.article.enterRecommendation"}" disabled="disabled">
									</form>
								{/if}			
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="reviewLabel">
					<span class="boldText">{translate key="reviewer.article.reviewerComments"}</a></span>
				</td>
				<td colspan="3">
					{if $reviewAssignment->getMostRecentPeerReviewComment()}
						{assign var="comment" value=$reviewAssignment->getMostRecentPeerReviewComment()}
						<a href="javascript:openComments('{$requestPageUrl}/viewPeerReviewComments/{$submission->getArticleId()}/{$reviewAssignment->getReviewId()}#{$comment->getCommentId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>{$comment->getDatePosted()|date_format:$dateFormatShort}
					{else}
						<a href="javascript:openComments('{$requestPageUrl}/viewPeerReviewComments/{$submission->getArticleId()}/{$reviewAssignment->getReviewId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>
					{/if}
				</td>
			</tr>
			{foreach from=$reviewAssignment->getReviewerFileRevisions() item=reviewerFile key=key}
			<tr>
				<td class="reviewLabel">
					{if $key eq "0"}
						<span class="boldText">{translate key="reviewer.article.uploadedFile"}</span>
					{/if}
				</td>
				<td>
					<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$reviewerFile->getFileId()}/{$reviewerFile->getRevision()}" class="file">{$reviewerFile->getFileName()}</a> {$reviewerFile->getDateModified()|date_format:$dateFormatShort}
				</td>
				<td colspan="2">
					<form name="authorView{$reviewAssignment->getReviewId()}" method="post" action="{$requestPageUrl}/makeReviewerFileViewable">
						<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
						<input type="hidden" name="fileId" value="{$reviewerFile->getFileId()}">
						<input type="hidden" name="revision" value="{$reviewerFile->getRevision()}">
						<input type="checkbox" name="viewable" value="1" {if $reviewerFile->getViewable()}checked="checked"{/if}> {translate key="editor.article.showAuthor"}
						<input type="submit" value="{translate key="common.record"}">
					</form>
				</td>
			</tr>
			{foreachelse}
			<tr>
				<td class="reviewLabel">
					<span class="boldText">{translate key="reviewer.article.uploadedFile"}</span>
				</td>
				<td colspan="3">
					{translate key="common.none"}
				</td>
			</tr>
			{/foreach}
			<tr>
				<td class="reviewLabel" valign="top">
					<form method="post" action="{$requestPageUrl}/notifyReviewer">
						<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
						<span class="boldText">{translate key="editor.article.toReviewer"}</span><input type="image" src="{$baseUrl}/templates/images/mail.gif">
					</form>
				</td>
				<td colspan="3">
					{foreach from=$notifyReviewerLogs[$reviewId] item=emailLog}
						<img src="{$baseUrl}/templates/images/letter.gif" />{$emailLog->getDateSent()|date_format:$dateFormatShort}
					{foreachelse}
						{translate key="common.none"}
					{/foreach}
				</td>
			</tr>
			{if $rateReviewerOnTimeliness or $rateReviewerOnQuality}
			<form method="post" action="{$requestPageUrl}/rateReviewer">
				<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
				<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
				<tr>
					<td class="reviewLabel">
						<span class="boldText">{translate key="editor.article.rateReviewer"}</span>
					</td>
					{if $rateReviewerOnTimeliness}
					<td>
						<table class="plainFormat">
							<tr>
								<td align="right">
									<span class="boldText">{translate key="editor.article.timeliness"}</span>
								</td>
								<td>
									<select name="timeliness"{if not $reviewAssignment->getRecommendation()} disabled=DISABLED{/if}>
									{html_options_translate options=$reviewerRatingOptions selected=$reviewAssignment->getTimeliness()}
									</select>
								</td>
							</tr>
						</table>
					</td>
					{/if}
					{if $rateReviewerOnQuality}
					<td>
						<table class="plainFormat">
							<tr>
								<td align="right">
									<span class="boldText">{translate key="editor.article.quality"}</span>
								</td>
								<td>
									<select name="quality"{if not $reviewAssignment->getRecommendation()} disabled=DISABLED{/if}>
									{html_options_translate options=$reviewerRatingOptions selected=$reviewAssignment->getQuality()}
									</select>
								</td>
							</tr>
						</table>
					</td>
					{/if}
					<td width="40%">
						<table class="plainFormat">
							<tr>
								<td>
									<input type="submit" value="{translate key="common.record"}"{if not $reviewAssignment->getRecommendation()} disabled=DISABLED{/if}>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</form>
			{/if}
		</table>
	</td>
</tr>
<tr class="submissionDivider">
	<td></td>
</tr>
{/foreach}
{section name="selectReviewer" start=0 loop=$numSelectReviewers}
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<span class="boldText">{$smarty.section.selectReviewer.index+$numReviewAssignments+$start|chr}.</span>
		<span class="disabledText">{translate key="editor.article.noneSelected"}</span>
	</td>
</tr>
{/section}
{if $showPeerReviewOptions}
<tr class="submissionDivider">
	<td></td>
</tr>
<tr>
	<td width="100%">
		<table class="plainFormat" width="100%">
			<tr>
				<td class="label" valign="top">{translate key="editor.article.peerReviewOptions"}</td>
				<td valign="top">
					<div class="rightAligned">
						<form method="post" action="{$requestPageUrl}/initiateAllReviews">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="submit" value="{translate key="editor.article.initiateAllReviews"}">
						</form>
					</div>
					<div class="rightAligned">
						<form method="post" action="{$requestPageUrl}/selectReviewer/{$submission->getArticleId()}">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="submit" value="{translate key="editor.article.addReviewer"}">
						</form>
					</div>
				</td>
			</tr>
		</table>
	</td>
</tr>
{/if}
</table>
</div>
		
<br />

<a name="editorReview"></a>
<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.editorReview"}</td>
</tr>
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td>
					<span class="boldText">{translate key="user.role.editor"}:</span>
					{if $editor}
						{$editor->getEditorFullName()}
					{else}
						{translate key="editor.article.noEditorSelected"}
					{/if}	
				</td>
			</tr>
		</table>
	</td>
</tr>
<tr class="submissionRow">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td class="reviewLabel" valign="top">
					<span class="boldText">{translate key="editor.article.decision"}</span>
				</td>
				<td colspan="5">
					{foreach from=$submission->getDecisions($round) item=editorDecision key=decisionKey}
						{if $decisionKey neq 0} | {/if}
						{assign var="decision" value=$editorDecision.decision}
						<span class="boldTextAlt">{translate key=$editorDecisionOptions.$decision}</span>
						{$editorDecision.dateDecided|date_format:$dateFormatShort}
					{foreachelse}
						{translate key="common.none"}
					{/foreach}
				</td>
			</tr>
			<tr>
				<td></td>
				<td colspan="5">
					<div class="indented">
						<form method="post" action="{$requestPageUrl}/recordDecision">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<select name="decision" {if not $allowRecommendation}disabled="disabled"{/if}>
								{html_options_translate options=$editorDecisionOptions selected=$lastDecision}
							</select>
							<input type="submit" name="submit" value="{translate key="editor.article.recordDecision"}" {if not $allowRecommendation}disabled="disabled"{/if}>
						</form>
					</div>		
				</td>
			</tr>
			<tr>
				<td class="reviewLabel">
					<span class="boldText"><a href="javascript:openComments('{$requestPageUrl}/viewEditorDecisionComments/{$submission->getArticleId()}');">{translate key="submission.editorAuthorComments"}</a></span>
				</td>
				<td colspan="5">
					{if $submission->getMostRecentEditorDecisionComment()}
						{assign var="comment" value=$submission->getMostRecentEditorDecisionComment()}
						<a href="javascript:openComments('{$requestPageUrl}/viewEditorDecisionComments/{$submission->getArticleId()}#{$comment->getCommentId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>{$comment->getDatePosted()|date_format:$dateFormatShort}
					{else}
						<translate key="common.none"}
					{/if}
				</td>
			</tr>
			<form method="post" action="{$requestPageUrl}/editorReview" enctype="multipart/form-data">
				<tr>
					<td></td>
					<td colspan="2">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
						<input type="file" name="upload">
						<input type="submit" name="submit" value="{translate key="common.upload"}">
					</td>
					<td align="center">
						<input type="submit" name="setCopyeditFile" value="Send to Copyedit" {if not $allowCopyedit}disabled="disabled"{/if}>
					</td>
					<td align="center">
						<input type="submit" name="resubmit" value="Resubmit" {if not $allowResubmit}disabled="disabled"{/if}>
					</td>
				</tr>
				{foreach from=$submission->getEditorFileRevisions($round) item=editorFile key=key}
					<tr>
						<td class="reviewLabel" valign="top">
							{if $key eq 0}
								<span class="boldText">{translate key="submission.editorVersion"}</span>
							{/if}
						</td>
						<td><nobr><a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$editorFile->getFileId()}/{$editorFile->getRevision()}" class="file">{$editorFile->getFileName()}</a></nobr></td>
						<td>{$editorFile->getDateModified()|date_format:$dateFormatShort}</td>
						<td align="center">
							<input type="radio" name="copyeditFile" value="{$editorFile->getFileId()},{$editorFile->getRevision()}" {if not $allowCopyedit}disabled="disabled"{/if}>
						</td>
						<td align="center">
							<input type="radio" name="resubmitFile" value="{$editorFile->getFileId()},{$editorFile->getRevision()}" {if not $allowResubmit}disabled="disabled"{/if}>
						</td>
					</tr>
				{foreachelse}
					<tr>
						<td class="reviewLabel" valign="top">
							<span class="boldText">{translate key="submission.editorVersion"}</span>
						</td>
						<td colspan="4">{translate key="common.none"}</td>
					</tr>
				{/foreach}
				{foreach from=$submission->getAuthorFileRevisions($round) item=authorFile key=key}
					<tr>
						<td class="reviewLabel" valign="top">
							{if $key eq 0}
								<span class="boldText">{translate key="submission.authorVersion"}</span>
							{/if}
						</td>
						<td><nobr><a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$authorFile->getFileId()}/{$authorFile->getRevision()}" class="file">{$authorFile->getFileName()}</a></nobr></td>
						<td>{$authorFile->getDateModified()|date_format:$dateFormatShort}</td>
						<td align="center">
							<input type="radio" name="copyeditFile" value="{$authorFile->getFileId()},{$authorFile->getRevision()}" {if not $allowCopyedit}disabled="disabled"{/if}>
						</td>
						<td align="center">
							<input type="radio" name="resubmitFile" value="{$authorFile->getFileId()},{$authorFile->getRevision()}" {if not $allowResubmit}disabled="disabled"{/if}>
						</td>
					</tr>
				{foreachelse}
					<tr>
						<td class="reviewLabel" valign="top">
							<span class="boldText">{translate key="submission.authorVersion"}</span>
						</td>
						<td colspan="4">{translate key="common.none"}</td>
					</tr>
				{/foreach}
			</form>
		</table>
	</td>
</tr>
</table>
</div>

{include file="common/footer.tpl"}
