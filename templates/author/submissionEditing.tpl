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
	<li><a href="{$requestPageUrl}/submission/{$submission->getArticleId()}">{translate key="submission.submissionReview"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEditing/{$submission->getArticleId()}"  class="active">{translate key="submission.submissionEditing"}</a></li>
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
				<td valign="top">{translate key="article.indexingInformation"}: <a href="{$requestPageUrl}/viewMetadata/{$submission->getArticleId()}">{translate key="article.metadata"}</a></td>
				<td valign="top">{translate key="article.section"}: {$submission->getSectionTitle()}</td>
			</tr>
			<tr>
				<td colspan="2">
					{translate key="article.file"}:
					{if $submissionFile}
						<a href="{$requestPageUrl}/downloadFile/{$submissionFile->getFileId()}" class="file">{$submissionFile->getFileName()}</a> {$submissionFile->getDateModified()|date_format:$dateFormatShort}</td>
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
									<a href="{$requestPageUrl}/downloadFile/{$suppFile->getFileId()}">{$suppFile->getTitle()}</a><br />
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
</div>

<br />

<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.copyedit"}</td>
</tr>
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td>
					{if $submission->getCopyeditorId()}
						<span class="boldText">{translate key="user.role.copyeditor"}:</span> {$copyeditor->getFullName()}
					{else}
						<span class="boldText">{translate key="user.role.copyeditor"}:</span> {translate key="common.none"}
					{/if}
				</td>
			</tr>
		</table>
	</td>
</tr>
<tr class="submissionDivider">
	<td></td>
</tr>
<!-- START INITIAL COPYEDIT -->
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td width="20%"><span class="boldText">1. {translate key="submission.copyedit.initialCopyedit"}</td>
				<td width="20%">
					{if $submission->getCopyeditorDateCompleted() and $initialCopyeditFile}
						<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$initialCopyeditFile->getFileId()}/{$initialCopyeditFile->getRevision()}" class="file">{$initialCopyeditFile->getFileName()}</a> {$initialCopyeditFile->getDateModified()|date_format:$dateFormatShort}
					{else}
						{translate key="common.none"}
					{/if}
				</td>
				<td align="center" width="15%"><strong>{translate key="submission.request"}</strong></td>
				<td align="center" width="15%"><strong>{translate key="submission.underway"}</strong></td>
				<td align="center" width="15%"><strong>{translate key="submission.complete"}</strong></td>
			</tr>
			<tr>
				<td colspan="2" width="40%"></td>
				<td align="center" width="15%">{if $submission->getCopyeditorDateNotified()}{$submission->getCopyeditorDateNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">{if $submission->getCopyeditorDateUnderway()}{$submission->getCopyeditorDateUnderway()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">{if $submission->getCopyeditorDateCompleted()}{$submission->getCopyeditorDateCompleted()|date_format:$dateFormatShort}{else}-{/if}</td>
			</tr>
		</table>
	</td>
</tr>
<!-- END INITIAL COPYEDIT -->
<!-- START AUTHOR COPYEDIT -->
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td width="20%"><span class="boldText">2. {translate key="submission.copyedit.editorAuthorReview"}</span></td>
				<td width="20%">
					{if $submission->getCopyeditorDateAuthorNotified() and $editorAuthorCopyeditFile}
						<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$editorAuthorCopyeditFile->getFileId()}/{$editorAuthorCopyeditFile->getRevision()}" class="file">{$editorAuthorCopyeditFile->getFileName()}</a> {$editorAuthorCopyeditFile->getDateModified()|date_format:$dateFormatShort}
					{else}
						{translate key="common.none"}
					{/if}
				</td>
				<td align="center" width="15%"><strong>{translate key="submission.request"}</strong></td>
				<td align="center" width="15%"><strong>{translate key="submission.underway"}</strong></td>
				<td align="center" width="15%">
					<form method="post" action="{$requestPageUrl}/completeAuthorCopyedit">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
						<input type="submit" value="{translate key="submission.complete"}" {if not $submission->getCopyeditorDateAuthorNotified() or $submission->getCopyeditorDateAuthorCompleted()}disabled="disabled"{/if}>
					</form>
				</td>
			</tr>
			<tr>
				<td colspan="2" width="40%">
					<div class="indented">
						<form method="post" action="{$requestPageUrl}/uploadCopyeditVersion"  enctype="multipart/form-data">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="hidden" name="copyeditStage" value="author">
							<input type="file" name="upload" {if $submission->getCopyeditorDateAuthorCompleted()}disabled="disabled"{/if}>
							<input type="submit" value="{translate key="common.upload"}" {if $submission->getCopyeditorDateAuthorCompleted()}disabled="disabled"{/if}>
						</form>
					</div>			
				</td>
				<td align="center" width="15%">{if $submission->getCopyeditorDateAuthorNotified()}{$submission->getCopyeditorDateAuthorNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">{if $submission->getCopyeditorDateAuthorUnderway()}{$submission->getCopyeditorDateAuthorUnderway()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">{if $submission->getCopyeditorDateAuthorCompleted()}{$submission->getCopyeditorDateAuthorCompleted()|date_format:$dateFormatShort}{else}-{/if}</td>
			</tr>
		</table>
	</td>
</tr>
<!-- END AUTHOR COPYEDIT REVIEW -->
<!-- START FINAL COPYEDIT -->
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td width="20%"><span class="boldText">3. {translate key="submission.copyedit.finalCopyedit"}</td>
				<td width="20%">
					{if $submission->getCopyeditorDateFinalCompleted() and $finalCopyeditFile}
						<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$finalCopyeditFile->getFileId()}/{$finalCopyeditFile->getRevision()}" class="file">{$finalCopyeditFile->getFileName()}</a> {$finalCopyeditFile->getDateModified()|date_format:$dateFormatShort}
					{else}
						{translate key="common.none"}
					{/if}
				</td>
				<td align="center" width="15%"><strong>{translate key="submission.request"}</strong></td>
				<td align="center" width="15%"><strong>{translate key="submission.underway"}</strong></td>
				<td align="center" width="15%"><strong>{translate key="submission.complete"}</strong></td>
			</tr>
			<tr>
				<td colspan="2" width="40%"></td>
				<td align="center" width="15%">{if $submission->getCopyeditorDateFinalNotified()}{$submission->getCopyeditorDateFinalNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">{if $submission->getCopyeditorDateFinalUnderway()}{$submission->getCopyeditorDateFinalUnderway()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">{if $submission->getCopyeditorDateFinalCompleted()}{$submission->getCopyeditorDateFinalCompleted()|date_format:$dateFormatShort}{else}-{/if}</td>
			</tr>
		</table>
	</td>
</tr>
<!-- END FINAL COPYEDIT -->
<tr class="submissionDivider">
	<td></td>
</tr>
<tr class="submissionRow">
	<td class="submissionBox">
		<a href="javascript:openComments('{$requestPageUrl}/viewCopyeditComments/{$submission->getArticleId()}');">{translate key="submission.copyedit.copyeditComments"}</a>
		{if $submission->getMostRecentCopyeditComment()}
			{assign var="comment" value=$submission->getMostRecentCopyeditComment()}
			<a href="javascript:openComments('{$requestPageUrl}/viewCopyeditComments/{$submission->getArticleId()}#{$comment->getCommentId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>{$comment->getDatePosted()|date_format:$dateFormatShort}
		{else}
			{translate key="common.none"}
		{/if}
	</td>
</tr>
</table>
</div>
{include file="common/footer.tpl"}
