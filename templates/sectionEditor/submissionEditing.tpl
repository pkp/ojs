{**
 * submissionEditing.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the details of a submission.
 *
 * FIXME: The tabbed navigation does NOT use nested lists. This might want to be addressed later.
 *
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.submission"}
{include file="common/header.tpl"}

<ul id="tabnav">
	<li><a href="{$pageUrl}/sectionEditor/submission/{$submission->getArticleId()}">{translate key="submission.submissionReview"}</a></li>
	<li><a href="{$pageUrl}/sectionEditor/submissionEditing/{$submission->getArticleId()}" class="active">{translate key="submission.submissionEditing"}</a></li>
</ul>
<ul id="subnav">
	<li><a href="#copyedit">{translate key="submission.copyedit"}</a></li>
	<li><a href="#layout">{translate key="submission.layout"}</a></li>
	<li><a href="#proofread">{translate key="submission.proofread"}</a></li>
</ul>

<div class="formSectionTitle">{translate key="submission.submission"}</div>
<div class="formSection">
<table class="form" width="100%">
<tr>
	<td class="formLabel">{translate key="article.title"}:</td>
	<td>{$submission->getTitle()}</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">{translate key="article.authors"}:</td>
	<td>
		{foreach from=$submission->getAuthors() item=author}
			<div>{$author->getFullName()}</div>
		{/foreach}
	</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">{translate key="article.indexingInformation"}:</td>
	<td>[<a href="{$pageUrl}/sectionEditor/viewMetadata/{$submission->getArticleId()}">{translate key="article.metadata"}</a>]</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">{translate key="article.section"}:</td>
	<td>
		<form method="post" action="{$pageUrl}/sectionEditor/changeSection">
		<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
		<select name="sectionId">
		{foreach from=$sections item=section}
			<option value="{$section->getSectionId()}" {if $section->getTitle() eq $submission->getSectionTitle()}selected="selected"{/if}>{$section->getTitle()}</option>
		{/foreach}
		</select>
		<input type="submit" value="{translate key="editor.article.changeSection"}">
		</form>
	</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">{translate key="article.file"}:</td>
	<td>
		{if $submissionFile}
			<a href="{$pageUrl}/sectionEditor/downloadFile?fileId={$submissionFile->getFileId()}">{$submissionFile->getFileName()}</a> {$submissionFile->getDateModified()|date_format:$dateFormatShort}</td>
		{/if}
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">{translate key="article.suppFiles"}:</td>
	<td>
		{foreach from=$suppFiles item=suppFile}
			<div><a href="{$pageUrl}/author/downloadFile/{$suppFile->getFileId()}">{$suppFile->getTitle()}</a></div>
		{foreachelse}
			<div>{translate key="common.none"}</div>
		{/foreach}
	</td>
	<td align="right">[<a href="{$pageUrl}/sectionEditor/addSuppFile/{$submission->getArticleId()}">{translate key="submission.addSuppFile"}</a>]</td>
</tr>
</table>
</div>

<br />
<br />

<a name="copyedit"></a>
<div class="formSectionTitle">{translate key="submission.copyedit"}</div>
<div class="formSection">
<table class="form" width="100%">
<tr>
	<td width="5%"></td>
	<td width="25%"></td>
	<td width="25%"></td>
	<td width="15%" class="label">{translate key="submission.request"}</td>
	<td width="15%" class="label">{translate key="submission.complete"}</td>
	<td width="15%" class="label">{translate key="submission.thank"}</td>
</tr>
<tr>
	<td width="5%">1.</td>
	<td width="25%">
		{if $useCopyeditors}
			{if $submission->getCopyeditorId()}
				<a href="mailto:{$copyeditor->getEmail()}">{$copyeditor->getFullName()}</a>
			{else}
				<form method="post" action="{$pageUrl}/sectionEditor/selectCopyeditor/{$submission->getArticleId()}">
					<input type="submit" value="{translate key="submission.selectCopyeditor"}">
				</form>
			{/if}
		{else}
			{translate key="submission.editorsCopyedit"}
		{/if}
	</td>
	<td width="25%" align="right">
		<table class="plainFormat">
			<tr>
				{if $useCopyeditors and $submission->getCopyeditorId()}
					{if not $submission->getCopyeditorDateCompleted()}
						<td>
							<form method="post" action="">
								<input type="submit" value="{translate key="editor.article.replace"}">
							</form>
						</td>
						<td>
							<form method="post" action="{$pageUrl}/sectionEditor/notifyCopyeditor">
								<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
								<input type="submit" value="{translate key="editor.article.notify"}">
							</form>
						</td>
					{elseif $submission->getCopyeditorDateCompleted() and not $submission->getCopyeditorDateAcknowledged()}
						<td>
							<form method="post" action="{$pageUrl}/sectionEditor/thankCopyeditor">
								<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
								<input type="submit" value="{translate key="editor.article.thank"}">
							</form>
						</td>
					{/if}
				{else}
					<td>
						<form method="post" action="">
							<input type="submit" value="{translate key="editor.article.initiate"}">
						</form>
					</td>
				{/if}
			</tr>
		</table>
	</td>
	<td width="15%">{$submission->getCopyeditorDateNotified()|date_format:$dateFormatShort}</td>
	<td width="15%">{$submission->getCopyeditorDateCompleted()|date_format:$dateFormatShort}</td>
	<td width="15%">
		{if $useCopyeditors and $submission->getCopyeditorId()}
			{$submission->getCopyeditorDateAcknowledged()|date_format:$dateFormatShort}
		{else}
			{translate key="common.notApplicableShort"}
		{/if}
	</td>
</tr>
<tr>
	<td width="5%">2.</td>
	<td width="25%">{translate key="submission.editorAuthorReview"}</td>
	<td width="25%" align="right">
		<table class="plainFormat">
			<tr>
				{if not $submission->getCopyeditorDateAuthorCompleted()}
					<td>
						<form method="post" action="{$pageUrl}/sectionEditor/notifyAuthorCopyedit">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="submit" value="{translate key="editor.article.notify"}">
						</form>
					</td>
				{elseif not $submission->getCopyeditorDateAuthorAcknowledged()}
					<td>
						<form method="post" action="{$pageUrl}/sectionEditor/thankAuthorCopyedit">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="submit" value="{translate key="editor.article.thank"}">
						</form>
					</td>
				{/if}
			</tr>
		</table>
	</td>
	<td width="15%">{$submission->getCopyeditorDateAuthorNotified()|date_format:$dateFormatShort}</td>
	<td width="15%">{$submission->getCopyeditorDateAuthorCompleted()|date_format:$dateFormatShort}</td>
	<td width="15%">{$submission->getCopyeditorDateAuthorAcknowledged()|date_format:$dateFormatShort}</td>
</tr>
<tr>
	<td width="5%">3.</td>
	<td width="25%">{translate key="submission.finalCopyedit"}</td>
	<td width="25%" align="right">
		<table class="plainFormat">
			<tr>
				{if $useCopyeditors and $submission->getCopyeditorId()}
					{if not $submission->getCopyeditorDateFinalCompleted()}
						<td>
							<form method="post" action="{$pageUrl}/sectionEditor/notifyFinalCopyedit">
								<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
								<input type="submit" value="{translate key="editor.article.notify"}">
							</form>
						</td>
					{elseif $submission->getCopyeditorDateFinalCompleted() and not $submission->getCopyeditorDateFinalAcknowledged()}
						<td>
							<form method="post" action="{$pageUrl}/sectionEditor/thankFinalCopyedit">
								<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
								<input type="submit" value="{translate key="editor.article.thank"}">
							</form>
						</td>
					{/if}
				{else}
					<td>
						<form method="post" action="{$pageUrl}/sectionEditor/initiateFinalCopyedit">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="submit" value="{translate key="editor.article.initiate"}">
						</form>
					</td>
				{/if}
			</tr>
		</table>
	</td>
	<td width="15%">{$submission->getCopyeditorDateFinalNotified()|date_format:$dateFormatShort}</td>
	<td width="15%">{$submission->getCopyeditorDateFinalCompleted()|date_format:$dateFormatShort}</td>
	<td width="15%">
		{if $useCopyeditors}
			{$submission->getCopyeditorDateFinalAcknowledged()|date_format:$dateFormatShort}			
		{else}
			{translate key="common.notApplicableShort"}
		{/if}
	</td>
</tr>
<tr>
	<td colspan="3">{translate key="submission.copyeditVersion"}:</td>
	<td colspan="3">
		<form method="post" action="">
			<input type="file" name="upload">
			<input type="submit" value="{translate key="common.upload"}">
		</form>
	</td>
</tr>
</table>
</div>

<br />
<br />

<a name="layout"></a>
<div class="formSectionTitle">{translate key="submission.layout"}</div>
<div class="formSection">
<table class="form">
<tr>
	<td align="right">{translate key="submission.supplementaryFiles"}:</td>
	<td>{translate key="common.none"}</td>
</tr>
<tr>
	<td align="right">{translate key="submission.uploadGalleys"}:</td>
	<td>
		<form method="post" action="">
			<input type="file" name="upload">
			<input type="submit" value="{translate key="common.upload"}">
		</form>
	</td>
</tr>
</table>
</div>

<br />
<br />

<a name="proofread"></a>
<div class="formSectionTitle">{translate key="submission.proofread"}</div>
<div class="formSection">
<table class="form" width="100%">
<tr>
	<td width="55%" colspan="3"><a href="">{translate key="submission.proofreadingComments"}</a></td>
	<td width="15%" class="label">{translate key="submission.request"}</td>
	<td width="15%" class="label">{translate key="submission.complete"}</td>
	<td width="15%" class="label">{translate key="submission.thank"}</td>
</tr>
<tr>
	<td width="5%">A.</td>
	<td width="25%">{translate key="user.role.author"}</td>
	<td width="25%" align="right">
		<form method="post" action="">
			<input type="submit" value="{translate key="editor.article.notify"}">
		</form>
	</td>
	<td width="15%"></td>
	<td width="15%"></td>
	<td width="15%"></td>
</tr>
<tr>
	<td width="5%">B.</td>
	<td width="25%">{translate key="user.role.editor"}</td>
	<td width="25%" align="right">
		<form method="post" action="">
			<input type="submit" value="{translate key="editor.article.initiate"}">
		</form>
	</td>
	<td width="15%"></td>
	<td width="15%"></td>
	<td width="15%">{translate key="common.notApplicableShort"}</td>
</tr>
<tr>
	<td colspan="6" align="right">
		<table class="plainFormat">
			<tr>
				<td>
					<form method="post" action="">
						<input type="submit" value="{translate key="submission.queueForScheduling"}">
					</form>
				</td>
				<td>
					<form method="post" action="">
						<input type="submit" value="{translate key="submission.archiveSubmission"}">
					</form>
				</td>
			</tr>
		</table>
	</td>
</tr>
</table>
</div>
{include file="common/footer.tpl"}
