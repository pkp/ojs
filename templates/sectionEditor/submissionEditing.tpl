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
	<li><a href="{$pageUrl}/sectionEditor/summary/{$submission->getArticleId()}">{translate key="submission.summary"}</a></li>
	<li><a href="{$pageUrl}/sectionEditor/submission/{$submission->getArticleId()}">{translate key="submission.submission"}</a></li>
	<li><a href="{$pageUrl}/sectionEditor/submissionReview/{$submission->getArticleId()}">{translate key="submission.submissionReview"}</a></li>
	<li><a href="{$pageUrl}/sectionEditor/submissionEditing/{$submission->getArticleId()}" class="active">{translate key="submission.submissionEditing"}</a></li>
	<li><a href="{$pageUrl}/sectionEditor/submissionHistory/{$submission->getArticleId()}">{translate key="submission.submissionHistory"}</a></li>
</ul>
<ul id="subnav">
	<li><a href="#copyedit">{translate key="submission.copyedit"}</a></li>
	<li><a href="#layout">{translate key="submission.layout"}</a></li>
	<li><a href="#proofread">{translate key="submission.proofread"}</a></li>
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
				<td width="50%" valign="top">
					{translate key="editor.article.originalFile"}:
					{if $submissionFile}
						<a href="{$pageUrl}/sectionEditor/downloadFile/{$submissionFile->getFileId()}">{$submissionFile->getFileName()}</a> {$submissionFile->getDateModified()|date_format:$dateFormatShort}
					{else}
						{translate key="common.none"}
					{/if}
					<div class="indented">
						{if not $reviewFile}
							<form method="post" action="{$pageUrl}/sectionEditor/designateReviewVersion">
								<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
								<input type="checkbox" name="designate" value="1"> Designate as Review Version
								<input type="submit" value="{translate key="common.record"}">
							</form>
						{/if}
					</div>
				</td>
				<td width="50%" valign="top">
					{translate key="editor.article.reviewVersion"}:
					{if $reviewFile}
						<a href="{$pageUrl}/sectionEditor/downloadFile/{$reviewFile->getFileId()}">{$reviewFile->getFileName()}</a> {$reviewFile->getDateModified()|date_format:$dateFormatShort}
					{else}
						{translate key="common.none"}
					{/if}
					<div class="indented">
						<form method="post" action="{$pageUrl}/sectionEditor/uploadReviewVersion" enctype="multipart/form-data">
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

<br />

<a name="copyedit"></a>
<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.copyedit"}</td>
</tr>
<tr>
	<td>
		<table class="plain" width="100%">
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
	</td>
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
