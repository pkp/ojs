{**
 * submissionEditing.tpl
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
{assign var="pageId" value="author.submissionEditing"}
{include file="common/header.tpl"}

<ul id="tabnav">
	<li><a href="{$pageUrl}/author/submission/{$submission->getArticleId()}">{translate key="submission.submissionReview"}</a></li>
	<li><a href="{$pageUrl}/author/submissionEditing/{$submission->getArticleId()}"  class="active">{translate key="submission.submissionEditing"}</a></li>
</ul>
<ul id="subnav">
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

<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.copyedit"}</td>
</tr>
<tr class="submissionRowAlt">
	<td>
		<table class="plain" width="100%">
			<tr>
				<td width="30%"></td>
				<td width="40%"></td>
				<td width="15%" align="center"><strong>{translate key="submission.request"}</strong></td>
				<td width="15%" align="center"><strong>{translate key="submission.complete"}</strong></td>
			</tr>
			<tr>
				<td>
					<span class="boldText">1.</span>
					{translate key="submission.initialCopyedit"}
				</td>
				<td></td>
				<td align="center">{$submission->getCopyeditorDateNotified()|date_format:$dateFormatShort}</td>
				<td align="center">{$submission->getCopyeditorDateCompleted()|date_format:$dateFormatShort}</td>
			</tr>
			<tr>
				<td>
					<span class="boldText">2.</span>
					{translate key="submission.editorAuthorReview"}
				</td>
				<td align="right">
					{if $submission->getCopyeditorDateCompleted() and $submission->getCopyeditorDateAuthorNotified() and not $submission->getCopyeditorDateAuthorCompleted()}
						<form method="post" action="{$pageUrl}/author/completeAuthorCopyedit">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="submit" value="{translate key="author.article.complete"}">
						</form>
					{/if}
				</td>
				<td align="center">{$submission->getCopyeditorDateAuthorNotified()|date_format:$dateFormatShort}</td>
				<td align="center">{$submission->getCopyeditorDateAuthorCompleted()|date_format:$dateFormatShort}</td>
			</tr>
			<tr>
				<td>
					<span class="boldText">3.</span>
					{translate key="submission.finalCopyedit"}
				</td>
				<td></td>
				<td align="center">{$submission->getCopyeditorDateFinalNotified()|date_format:$dateFormatShort}</td>
				<td align="center">{$submission->getCopyeditorDateFinalCompleted()|date_format:$dateFormatShort}</td>
			</tr>
		</table>
	</td>
</tr>
<tr class="submissionRow">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td>
					{translate key="submission.copyeditVersion"}:
					{if $editorAuthorRevisionFile}
						<a href="{$pageUrl}/copyeditor/downloadFile/{$editorAuthorRevisionFile->getFileId()}/{$editorAuthorRevisionFile->getRevision()}" class="file">{$editorAuthorRevisionFile->getFileName()}</a> {$editorAuthorRevisionFile->getDateModified()|date_format:$dateFormatShort}
					{else}
						{translate key="common.none"}
					{/if}
				</td>
				<td>
					<form method="post" action="{$pageUrl}/author/uploadCopyeditVersion" enctype="multipart/form-data">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
						<input type="file" name="upload">
						<input type="submit" name="submit" value="{translate key="common.upload"}">
					</form>
				</td>
			</tr>
		</table>
	</td>
</tr>
</table>
</div>
{include file="common/footer.tpl"}
