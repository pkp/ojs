{**
 * submission.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the status of an author's submission.
 *
 *
 * $Id$
 *}

{assign_translate var="pageTitleTranslated" key="submission.page.review" id=$submission->getArticleId()}
{assign var="pageId" value="author.submissionReview"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{$requestPageUrl}/submission/{$submission->getArticleId()}">{translate key="submission.summary"}</a></li>
	<li class="current"><a href="{$requestPageUrl}/submissionReview/{$submission->getArticleId()}">{translate key="submission.submissionReview"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEditing/{$submission->getArticleId()}">{translate key="submission.submissionEditing"}</a></li>
</ul>
<ul class="menu">
{section name="tabRounds" start=0 loop=$submission->getCurrentRound()}
	{assign var="tabRound" value=$smarty.section.tabRounds.index+1}
	<li{if $round eq $tabRound} class="current"{/if}><a href="{$requestPageUrl}/submission/{$submission->getArticleId()}/{$tabRound}">{translate key="submission.round" round=$tabRound}</a></li>
{/section}
</ul>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.authors"}</td>
		<td width="80%" class="data">{$submission->getAuthorString(false)}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.title"}</td>
		<td width="80%" class="data">{$submission->getArticleTitle()}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="section.section"}</td>
		<td width="80%" class="data">{$submission->getSectionTitle()}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.editor"}</td>
		{assign var="editor" value=$submission->getEditor()}
		<td width="80%" class="data">{$editor->getEditorFullName()}</td>
	</tr>
</table>

<br />

<table width="100%">
<tr class="heading">
	<td>{translate key="submission.submission"}</td>
</tr>
<tr>
	<td>
		<table class="plain" width="100%">
			<tr>
				<td valign="top">{translate key="article.indexingInformation"}: <a href="{$requestPageUrl}/viewMetadata/{$submission->getArticleId()}">{translate key="article.metadata"}</a></td>
				<td valign="top">{translate key="section.section"}: {$submission->getSectionTitle()}</td>
			</tr>
			<tr>
				<td colspan="2">
					{translate key="article.file"}:
					{if $submissionFile}
						<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$submissionFile->getFileId()}/{$submissionFile->getRevision()}" class="file">{$submissionFile->getFileName()}</a> {$submissionFile->getDateModified()|date_format:$dateFormatShort}</td>
					{else}
						{translate key="common.none"}
					{/if}
				</td>
			</tr>
			<tr>
				<td valign="top">
					<table class="plainFormat">
						<tr>
							<td valign="top">{translate key="article.suppFiles"}:</td>
							<td valign="top">
								{foreach from=$suppFiles item=suppFile}
									<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$suppFile->getFileId()}">{$suppFile->getTitle()}</a><br />
								{foreachelse}
									{translate key="common.none"}
								{/foreach}
							</td>
						</tr>
					</table>
				</td>
				<td>
					<form method="post" action="{$requestPageUrl}/addSuppFile/{$submission->getArticleId()}">
						<input type="submit" value="{translate key="submission.addSuppFile"}">
					</form>
				</td>
			</tr>
		</table>
	</td>
</tr>
</table>

<br />

<a name="peerReview"></a>
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.peerReview"}</td>
</tr>
{assign var="start" value="A"|ord} 
{assign var="numReviewAssignments" value=$reviewAssignments|@count}
{foreach from=$reviewAssignments item=reviewAssignment key=key}
{if $key neq "0"}
	<tr class="submissionDivider">
		<td></td>
	</tr>
{/if}
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td width="40%">
					<span class="boldText">{$key+$start|chr}.</span>
					{translate key="user.role.reviewer"}
				</td>
				<td width="60%">
					<table class="plainFormat" width="100%">
						<tr>
							<td align="center"><strong>{translate key="submission.request"}</strong></td>
							<td align="center"><strong>{translate key="submission.acceptance"}</strong></td>
							<td align="center"><strong>{translate key="submission.due"}</strong></td>
						</tr>
						<tr>
							<td align="center">{if $reviewAssignment->getDateNotified()}{$reviewAssignment->getDateNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
							<td align="center">{if $reviewAssignment->getDateConfirmed()}{$reviewAssignment->getDateConfirmed()|date_format:$dateFormatShort}{else}-{/if}</td>
							<td align="center">{if $reviewAssignment->getDateDue()}{$reviewAssignment->getDateDue()|date_format:$dateFormatShort}{else}-{/if}</td>
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
				<td class="reviewLabel" valign="top">
					<span class="boldText">{translate key="reviewer.article.uploadedFile"}</span>
				</td>
				<td>
					{assign var="atLeastOneReviewerFile" value=false}
					{foreach from=$reviewAssignment->getReviewerFileRevisions() item=reviewerFile key=key}
						{if $reviewerFile->getViewable()}
							<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$reviewerFile->getFileId()}/{$reviewerFile->getRevision()}" class="file">{$reviewerFile->getFileName()}</a> {$reviewerFile->getDateModified()|date_format:$dateFormatShort}<br />
							{assign var="atLeastOneReviewerFile" value=true}
						{/if}
						{if not $atLeastOneReviewerFile}
							{translate key="common.none"}
						{/if}
					{foreachelse}
						{translate key="common.none"}
					{/foreach}
				</td>
			</tr>
		</table>
	</td>
</tr>
{/foreach}
</table>

<br />

<a name="editorReview"></a>
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
					<span class="boldText">{translate key="submission.editorAuthorComments"}</a></span>
				</td>
				<td>
					{if $submission->getMostRecentEditorDecisionComment()}
						{assign var="comment" value=$submission->getMostRecentEditorDecisionComment()}
						<a href="javascript:openComments('{$requestPageUrl}/viewEditorDecisionComments/{$submission->getArticleId()}#{$comment->getCommentId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>{$comment->getDatePosted()|date_format:$dateFormatShort}
					{else}
						<a href="javascript:openComments('{$requestPageUrl}/viewEditorDecisionComments/{$submission->getArticleId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>
					{/if}
				</td>
			</tr>
			<tr>
				<td class="reviewLabel" valign="top">
					<span class="boldText">{translate key="editor.article.decision"}</span>
				</td>
				<td>
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
			{foreach from=$submission->getAuthorFileRevisions($round) item=authorFile key=key}
				<tr>
					<td class="reviewLabel" valign="top">
						{if $key eq 0}
							<span class="boldText">{translate key="submission.authorVersion"}</span>
						{/if}
					</td>
					<td><a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$authorFile->getFileId()}/{$authorFile->getRevision()}" class="file">{$authorFile->getFileName()}</a> {$authorFile->getDateModified()|date_format:$dateFormatShort}</td>
				</tr>
			{foreachelse}
				<tr>
					<td class="reviewLabel" valign="top">
						<span class="boldText">{translate key="submission.authorVersion"}</span>
					</td>
					<td>{translate key="common.none"}</td>
				</tr>
			{/foreach}
				<tr>
					<td></td>
					<td>
						<div class="indented">
							<form method="post" action="{$requestPageUrl}/uploadRevisedVersion" enctype="multipart/form-data">
								<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
								<input type="file" name="upload">
								<input type="submit" name="submit" value="{translate key="common.upload"}">
							</form>
						</div>
					</td>
				</tr>
		</table>
	</td>
</tr>
</table>
{include file="common/footer.tpl"}
