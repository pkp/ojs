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
{assign var="pageId" value="sectionEditor.submissionEditing"}
{include file="common/header.tpl"}

<ul id="tabnav">
	<li><a href="{$requestPageUrl}/summary/{$submission->getArticleId()}">{translate key="submission.summary"}</a></li>
	<li><a href="{$requestPageUrl}/submission/{$submission->getArticleId()}">{translate key="submission.submission"}</a></li>
	<li><a href="{$requestPageUrl}/submissionReview/{$submission->getArticleId()}">{translate key="submission.submissionReview"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEditing/{$submission->getArticleId()}" class="active">{translate key="submission.submissionEditing"}</a></li>
	<li><a href="{$requestPageUrl}/submissionHistory/{$submission->getArticleId()}">{translate key="submission.submissionHistory"}</a></li>
</ul>
<ul id="subnav">
	<li><a href="#copyedit">{translate key="submission.copyedit"}</a></li>
	<li><a href="#layout">{translate key="submission.layout"}</a></li>
	<li><a href="#proofread">{translate key="submission.proofread"}</a></li>
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

<a name="copyedit"></a>
<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.copyedit"}</td>
</tr>
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td width="40%">
					{if $useCopyeditors}
						{if $submission->getCopyeditorId()}
							<span class="boldText">{translate key="user.role.copyeditor"}:</span> {$copyeditor->getFullName()}
						{else}
							<form method="post" action="{$requestPageUrl}/selectCopyeditor/{$submission->getArticleId()}">
								<input type="submit" value="{translate key="editor.article.selectCopyeditor"}">
							</form>
						{/if}
					{/if}
				</td>
				<td width="30%">
					{if $submission->getCopyeditorId()}
						<form method="post" action="{$requestPageUrl}/replaceCopyeditor/{$submission->getArticleId()}">
							<input type="submit" value="{translate key="editor.article.replaceCopyeditor"}">
						</form>
					{/if}
				</td>
				<td width="30%">
					{translate key="editor.article.reviewSubmission"} <a href="{$requestPageUrl}/viewMetadata/{$submission->getArticleId()}">{translate key="article.metadata"}</a>
				</td>
			</tr>
		</table>
	</td>
</tr>
<tr class="reviewDivider">
	<td></td>
</tr>
<!-- START INITIAL COPYEDIT -->
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td width="20%"><span class="boldText">1. {translate key="submission.initialCopyedit"}</td>
				<td width="20%">
					{if $initialCopyeditFile}
						<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$initialCopyeditFile->getFileId()}/{$initialCopyeditFile->getRevision()}" class="file">{$initialCopyeditFile->getFileName()}</a> {$initialCopyeditFile->getDateModified()|date_format:$dateFormatShort}
					{else}
						{translate key="common.none"}
					{/if}
				</td>
				<td align="center" width="15%">
					{if $useCopyeditors}
						<form method="post" action="{$requestPageUrl}/notifyCopyeditor">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="submit" value="{translate key="submission.request"}" {if not $submission->getCopyeditorId() or $submission->getCopyeditorDateCompleted()}disabled="disabled"{/if}>
						</form>
					{else}
						<form method="post" action="{$requestPageUrl}/initiateCopyedit">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="submit" value="{translate key="editor.article.initiate"}" {if $submission->getCopyeditorDateCompleted()}disabled="disabled"{/if}>
						</form>
					{/if}
				</td>
				<td align="center" width="15%"><strong>{translate key="submission.underway"}</strong></td>
				<td align="center" width="15%"><strong>{translate key="submission.complete"}</strong></td>
				<td align="center" width="15%">
					<form method="post" action="{$requestPageUrl}/thankCopyeditor">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
						<input type="submit" value="{translate key="submission.thank"}" {if not $submission->getCopyeditorId() or not $useCopyeditors or not $submission->getCopyeditorDateNotified() or $submission->getCopyeditorDateAcknowledged()}disabled="disabled"{/if}>
					</form>
				</td>
			</tr>
			<tr>
				<td colspan="2" width="40%">
					<div class="indented">
						<form method="post" action="{$requestPageUrl}/uploadCopyeditVersion"  enctype="multipart/form-data">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="hidden" name="copyeditStage" value="initial">
							<input type="file" name="upload" {if ($useCopyeditors and not $submission->getCopyeditorId()) or $submission->getCopyeditorDateCompleted()}disabled="disabled"{/if}>
							<input type="submit" value="{translate key="common.upload"}" {if ($useCopyeditors and not $submission->getCopyeditorId()) or $submission->getCopyeditorDateCompleted()}disabled="disabled"{/if}>
						</form>
					</div>			
				</td>
				<td align="center" width="15%">{if $submission->getCopyeditorDateNotified()}{$submission->getCopyeditorDateNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">
					{if $useCopyeditors}
						{if $submission->getCopyeditorDateUnderway()}{$submission->getCopyeditorDateUnderway()|date_format:$dateFormatShort}{else}-{/if}
					{else}
						{translate key="common.notApplicableShort"}
					{/if}
				</td>
				<td align="center" width="15%">{if $submission->getCopyeditorDateCompleted()}{$submission->getCopyeditorDateCompleted()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">
					{if $useCopyeditors}
						{if $submission->getCopyeditorDateAcknowledged()}{$submission->getCopyeditorDateAcknowledged()|date_format:$dateFormatShort}{else}-{/if}
					{else}
						{translate key="common.notApplicableShort"}
					{/if}
				</td>
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
				<td width="20%"><span class="boldText">2. {translate key="submission.editorAuthorReview"}</span></td>
				<td width="20%">
					{if $editorAuthorCopyeditFile}
						<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$editorAuthorCopyeditFile->getFileId()}/{$editorAuthorCopyeditFile->getRevision()}" class="file">{$editorAuthorCopyeditFile->getFileName()}</a> {$editorAuthorCopyeditFile->getDateModified()|date_format:$dateFormatShort}
					{else}
						{translate key="common.none"}
					{/if}
				</td>
				<td align="center" width="15%">
					<form method="post" action="{$requestPageUrl}/notifyAuthorCopyedit">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
						<input type="submit" value="{translate key="submission.request"}" {if not $submission->getCopyeditorId() or not $submission->getCopyeditorDateCompleted() or $submission->getCopyeditorDateAuthorCompleted()}disabled="disabled"{/if}>
					</form>
				</td>
				<td align="center" width="15%"><strong>{translate key="submission.underway"}</strong></td>
				<td align="center" width="15%"><strong>{translate key="submission.complete"}</strong></td>
				<td align="center" width="15%">
					<form method="post" action="{$requestPageUrl}/thankAuthorCopyedit">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
						<input type="submit" value="{translate key="submission.thank"}" {if not $submission->getCopyeditorId() or not $submission->getCopyeditorDateAuthorNotified() or $submission->getCopyeditorDateAuthorAcknowledged()}disabled="disabled"{/if}>
					</form>
				</td>
			</tr>
			<tr>
				<td colspan="2" width="40%">
					<div class="indented">
						<form method="post" action="{$requestPageUrl}/uploadCopyeditVersion"  enctype="multipart/form-data">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="hidden" name="copyeditStage" value="author">
							<input type="file" name="upload" {if ($useCopyeditors and not $submission->getCopyeditorId()) or not $submission->getCopyeditorDateCompleted() or $submission->getCopyeditorDateAuthorCompleted()}disabled="disabled"{/if}>
							<input type="submit" value="{translate key="common.upload"}" {if ($useCopyeditors and not $submission->getCopyeditorId()) or not $submission->getCopyeditorDateCompleted() or $submission->getCopyeditorDateAuthorCompleted()}disabled="disabled"{/if}>
						</form>
					</div>			
				</td>
				<td align="center" width="15%">{if $submission->getCopyeditorDateAuthorNotified()}{$submission->getCopyeditorDateAuthorNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">{if $submission->getCopyeditorDateAuthorUnderway()}{$submission->getCopyeditorDateAuthorUnderway()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">{if $submission->getCopyeditorDateAuthorCompleted()}{$submission->getCopyeditorDateAuthorCompleted()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">{if $submission->getCopyeditorDateAuthorAcknowledged()}{$submission->getCopyeditorDateAuthorAcknowledged()|date_format:$dateFormatShort}{else}-{/if}</td>
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
				<td width="20%"><span class="boldText">3. {translate key="submission.finalCopyedit"}</td>
				<td width="20%">
					{if $finalCopyeditFile}
						<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$finalCopyeditFile->getFileId()}/{$finalCopyeditFile->getRevision()}" class="file">{$finalCopyeditFile->getFileName()}</a> {$finalCopyeditFile->getDateModified()|date_format:$dateFormatShort}
					{else}
						{translate key="common.none"}
					{/if}
				</td>
				<td align="center" width="15%">
					{if $useCopyeditors}
						<form method="post" action="{$requestPageUrl}/notifyFinalCopyedit">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="submit" value="{translate key="submission.request"}" {if not $submission->getCopyeditorId() or not $submission->getCopyeditorDateAuthorCompleted()}disabled="disabled"{/if}>
						</form>
					{else}
						<form method="post" action="{$requestPageUrl}/initiateFinalCopyedit">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="submit" value="{translate key="editor.article.initiate"}" {if not $submission->getCopyeditorDateAuthorCompleted()}disabled="disabled"{/if}>
						</form>
					{/if}
				</td>
				<td align="center" width="15%"><strong>{translate key="submission.underway"}</strong></td>
				<td align="center" width="15%"><strong>{translate key="submission.complete"}</strong></td>
				<td align="center" width="15%">
					<form method="post" action="{$requestPageUrl}/thankFinalCopyedit">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
						<input type="submit" value="{translate key="submission.thank"}" {if not $submission->getCopyeditorId() or not $useCopyeditors or not $submission->getCopyeditorDateFinalNotified() or $submission->getCopyeditorDateFinalAcknowledged()}disabled="disabled"{/if}>
					</form>
				</td>
			</tr>
			<tr>
				<td colspan="2" width="40%">
					<div class="indented">
						<form method="post" action="{$requestPageUrl}/uploadCopyeditVersion"  enctype="multipart/form-data">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="hidden" name="copyeditStage" value="final">
							<input type="file" name="upload" {if ($useCopyeditors and not $submission->getCopyeditorId()) or not $submission->getCopyeditorDateAuthorCompleted()}disabled="disabled"{/if}>
							<input type="submit" value="{translate key="common.upload"}" {if ($useCopyeditors and not $submission->getCopyeditorId()) or not $submission->getCopyeditorDateAuthorCompleted()}disabled="disabled"{/if}>
						</form>
					</div>			
				</td>
				<td align="center" width="15%">{if $submission->getCopyeditorDateFinalNotified()}{$submission->getCopyeditorDateFinalNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">
					{if $useCopyeditors}
						{if $submission->getCopyeditorDateFinalUnderway()}{$submission->getCopyeditorDateFinalUnderway()|date_format:$dateFormatShort}{else}-{/if}
					{else}
						{translate key="common.notApplicableShort"}
					{/if}
				</td>
				<td align="center" width="15%">{if $submission->getCopyeditorDateFinalCompleted()}{$submission->getCopyeditorDateFinalCompleted()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">
					{if $useCopyeditors}
						{if $submission->getCopyeditorDateFinalAcknowledged()}{$submission->getCopyeditorDateFinalAcknowledged()|date_format:$dateFormatShort}{else}-{/if}			
					{else}
						{translate key="common.notApplicableShort"}
					{/if}
				</td>
			</tr>
		</table>
	</td>
</tr>
<!-- END FINAL COPYEDIT -->
<tr class="reviewDivider">
	<td></td>
</tr>
</table>
</div>

<br />

<a name="layout"></a>
<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.layout"}</td>
</tr>
<tr>
	<td>
		<table class="plain" width="100%">
			<tr>
				<td>{translate key="submission.supplementaryFiles"}:</td>
				<td>{translate key="common.none"}</td>
			</tr>
			<tr>
				<td>{translate key="submission.uploadGalleys"}:</td>
				<td>
					<form method="post" action="">
						<input type="file" name="upload">
						<input type="submit" value="{translate key="common.upload"}">
					</form>
				</td>
			</tr>
		</table>
	</td>
</tr>
</table>
</div>

<br />

<a name="proofread"></a>
<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.proofread"}</td>
</tr>
<tr>
	<td>
		<table class="plain" width="100%">
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
	</td>
</tr>
</table>
</div>
{include file="common/footer.tpl"}
