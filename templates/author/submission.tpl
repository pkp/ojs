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
{assign var="pageId" value="author.submission"}
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
	<td>{translate key="submission.submission"}</td>
</tr>
<tr>
	<td>
		<table class="plain" width="100%">
			<tr>
				<td valign="top">{translate key="article.indexingInformation"}: <a href="{$pageUrl}/author/viewMetadata/{$submission->getArticleId()}">{translate key="article.metadata"}</a></td>
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
					{foreach from=$reviewAssignment->getReviewerFileRevisions() item=reviewerFile key=key}
						{if $reviewerFile->getViewable()}
							<a href="{$pageUrl}/sectionEditor/downloadFile/{$reviewerFile->getFileId()}" class="file">{$reviewerFile->getFileName()}</a> {$reviewerFile->getDateModified()|date_format:$dateFormatShort}<br />
						{/if}
					{/foreach}
				</td>
			</tr>
		</table>
	</td>
</tr>
{/foreach}
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
					<span class="boldText">{translate key="editor.article.comments"}</span>
				</td>
				<td>
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
					<td><a href="{$pageUrl}/sectionEditor/downloadFile/{$authorFile->getFileId()}" class="file">{$authorFile->getFileName()}</a> {$authorFile->getDateModified()|date_format:$dateFormatShort}</td>
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
							<form method="post" action="{$pageUrl}/author/uploadRevisedVersion" enctype="multipart/form-data">
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
</div>
{include file="common/footer.tpl"}
