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

{assign var="pageTitle" value="submission.submission"}
{include file="common/header.tpl"}

<ul id="tabnav">
	<li><a href="{$pageUrl}/author/submission/{$submission->getArticleId()}" class="active">{translate key="submission.submissionReview"}</a></li>
	<li><a href="{$pageUrl}/author/submissionEditing/{$submission->getArticleId()}">{translate key="submission.submissionEditing"}</a></li>
</ul>
<ul id="subnav">
{section name="tabRounds" start=0 loop=$submission->getCurrentRound()}
	{assign var="tabRound" value=$smarty.section.tabRounds.index+1}
	<li><a href="{$pageUrl}/author/submission/{$submission->getArticleId()}/{$tabRound}" {if $round eq $tabRound}class="active"{/if}>{translate key="submission.round" round=$tabRound}</a></li>
{/section}
</ul>

<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.submission"}</td>
</tr>
<tr>
	<td>
		<table class="plain" width="100%">
			<tr>
				<td colspan="2">
					{translate key="article.title"}: <strong>{$submission->getArticleTitle()}</strong> <br />
					{translate key="article.authors"}: {foreach from=$submission->getAuthors() item=author key=key}{if $key neq 0},{/if} {$author->getFullName()}{/foreach}
				</td>
			</tr>
			<tr>
				<td valign="top">{translate key="article.indexingInformation"}: <a href="{$pageUrl}/sectionEditor/viewMetadata/{$submission->getArticleId()}">{translate key="article.metadata"}</a></td>
				<td valign="top">{translate key="article.section"}: {$submission->getSectionTitle()}</td>
			</tr>
			<tr>
				<td colspan="2">
					{translate key="article.file"}:
					{if $submissionFile}
						<a href="{$pageUrl}/author/downloadFile/{$submissionFile->getFileId()}">{$submissionFile->getFileName()}</a> {$submissionFile->getDateModified()|date_format:$dateFormatShort}</td>
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
									<a href="{$pageUrl}/author/downloadFile/{$suppFile->getFileId()}">{$suppFile->getTitle()}</a><br />
								{foreachelse}
									{translate key="common.none"}
								{/foreach}
							</td>
						</tr>
					</table>
				</td>
				<td>
					<form method="post" action="{$pageUrl}/author/addSuppFile/{$submission->getArticleId()}">
						<input type="submit" value="{translate key="submission.addSuppFile"}">
					</form>
				</td>
			</tr>
		</table>
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
<tr>
	<td>
		<table class="plain" width="100%">
			<tr>
				<td class="label" width="5%">&nbsp;</td>
				<td class="label" width="50%">&nbsp;</td>
				<td class="label" width="15%">{translate key="submission.request"}</td>
				<td class="label" width="15%">{translate key="submission.acceptance"}</td>
				<td class="label" width="15%">{translate key="submission.due"}</td>
			</tr>
			{foreach from=$reviewAssignments item=reviewAssignment key=key}
				<tr class="{cycle values="row,rowAlt"}">
					<td width="5%" valign="top">{$key+$start|chr}.</td>
					<td width="50%">
						{translate key="user.role.reviewer"}<br />
						{if $reviewAssignment->getReviewerFileId() and $reviewAssignment->getReviewerFileViewable()}
							{assign var="reviewerFile" value=$reviewAssignment->getReviewerFile()}
							{translate key="submission.reviewersVersion"}:
							<a href="{$pageUrl}/author/downloadFile/{$reviewerFile->getFileId()}">{$reviewerFile->getFileName()}</a> {$reviewerFile->getDateModified()|date_format:$dateFormatShort}</td>
						{/if}
					</td>
					<td width="15%">{$reviewAssignment->getDateNotified()|date_format:$dateFormatShort}</td>
					<td width="15%">{$reviewAssignment->getDateConfirmed()|date_format:$dateFormatShort}</td>
					<td width="15%">{$reviewAssignment->getDateDue()|date_format:$dateFormatShort}</td>
				</tr>
			{foreachelse}
				<tr>
					<td colspan="5">{translate key="submission.noReviewAssignments"}</td>
				</tr>
			{/foreach}
		</table>
	</td>
</tr>
</table>
</div>

<br />

<a name="editorReview"></a>
<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.editorReview"}</td>
</tr>
<tr>
	<td>
		<table class="plain" width="100%">
			<tr>
				<td>
					{translate key="user.role.editor"}:
					{if $editor}
						<a href="mailto:{$editor->getEditorEmail()}">{$editor->getEditorFullName()}</a>
					{else}
						{translate key="common.none"}
					{/if}
				</td>
			</tr>
			<tr>
				<td>[<a href="">{translate key="submission.editorAuthorComments"}</a>]</td>
			</tr>
			<tr>
				<td>
					{translate key="editor.article.decision"}:
					{foreach from=$submission->getDecisions($round) item=editorDecision key=decisionKey}
						{if $decisionKey neq 0} | {/if}
						{assign var="decision" value=$editorDecision.decision}
						{translate key=$editorDecisionOptions.$decision}
						{$editorDecision.dateDecided|date_format:$dateFormatShort}
					{foreachelse}
						{translate key="common.none"}
					{/foreach}
				</td>
			</tr>
			<tr>
				<td>{translate key="submission.postReviewVersion"}:
					{if $postReviewFile}
						{$postReviewFile->getFileName()}
					{else}
						{translate key="common.none"}
					{/if}
				</td>
			</tr>
			{if $revisedFile}
			<tr>
				<td>
					{translate key="submission.authorsRevisedVersion"}:
					<a href="{$pageUrl}/author/downloadFile/{$revisedFile->getFileId()}">{$revisedFile->getFileName()}</a> {$revisedFile->getDateModified()|date_format:$dateFormatShort}
				</td>
			</tr>
			{/if}
			<tr>
				<td>
					<div class="indented">
						<form method="post" action="{$pageUrl}/author/uploadRevisedArticle" enctype="multipart/form-data">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
							<input type="file" name="upload" />
							<input type="submit" name="submit" value="{translate key="common.upload"}" />
						</form>
					</div>
				</td>
			</tr>
		</table>
	</td>
</tr>	
</table>
</div>
{include file="common/footer.tpl"}
