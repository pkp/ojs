{**
 * layout.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the layout editing table.
 *
 * $Id$
 *}

{assign var=layoutAssignment value=$submission->getLayoutAssignment()}
{assign var=layoutFile value=$layoutAssignment->getLayoutFile()}
<a name="layout"></a>
<h3>{translate key="submission.layout"}</h3>

<div class="tableContainer">
<table width="100%">
<tr class="submissionRow">
	<td class="submissionBox">
		{if $useLayoutEditors}
		<table class="plainFormat" width="100%">
			<tr>
				<td width="40%">
					{if $layoutAssignment->getEditorId()}
						<span class="boldText">{translate key="user.role.layoutEditor"}:</span> {$layoutAssignment->getEditorFullName()}
					{else}
						<form method="post" action="{$requestPageUrl}/assignLayoutEditor/{$submission->getArticleId()}">
							<input type="submit" value="{translate key="submission.layout.assignLayoutEditor"}">
						</form>
					{/if}
				</td>
				<td width="60%">
					{if $layoutAssignment->getEditorId()}
						<form method="post" action="{$requestPageUrl}/assignLayoutEditor/{$submission->getArticleId()}">
							<input type="submit" value="{translate key="submission.layout.replaceLayoutEditor"}">
						</form>
					{/if}
				</td>
			</tr>
		</table>
		{/if}
		<table class="plainFormat" width="100%">
			<tr>
				<td width="40%">
					<span class="boldText">{translate key="submission.layout.layoutVersion"}:</span>
					{if $layoutFile}
						<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$layoutFile->getFileId()}" class="file">{$layoutFile->getFileName()}</a> {$layoutFile->getDateModified()|date_format:$dateFormatShort}
					{else}
						{translate key="common.none"}
					{/if}
				</td>
				<td width="60%">
					<form method="post" action="{$requestPageUrl}/uploadLayoutVersion" enctype="multipart/form-data">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
						<input type="file" name="layoutFile" />
						<input type="submit" name="submit" value="{translate key="common.upload"}" />
					</form>
				</td>
			</tr>
		</table>
	</td>
</tr>
<tr class="submissionRow">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td width="20%"><strong>{translate key="submission.layout.initialGalleyCreation"}</strong></td>
				<td width="20%" align="center">
					{if not $layoutAssignment->getEditorId()}
					<form>
						<input type="submit" value="{translate key="submission.request"}" disabled="disabled" />
					</form>
					{else}
					<form method="post" action="{$requestPageUrl}/notifyLayoutEditor">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
						<input type="submit" value="{translate key="submission.request"}" />
					</form>
					{/if}
				</td>
				<td width="20%" align="center"><strong>{translate key="submission.underway"}</strong></td>
				<td width="20%" align="center"><strong>{translate key="submission.complete"}</strong></td>
				<td width="20%" align="center">
					{if $layoutAssignment->getEditorId() && $layoutAssignment->getDateCompleted()}
					<form method="post" action="{$requestPageUrl}/thankLayoutEditor">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
						<input type="submit" value="{translate key="submission.thank"}" />
					</form>
					{else}
					<form>
						<input type="submit" value="{translate key="submission.thank"}" disabled="disabled" />
					</form>
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
				<td width="20%"></td>
				<td width="20%" align="center">
					{if $layoutAssignment->getDateNotified()}
						{$layoutAssignment->getDateNotified()|date_format:$dateFormatShort}
					{else}
						-
					{/if}
				</td>
				<td width="20%" align="center">
					{if $layoutAssignment->getDateUnderway()}
						{$layoutAssignment->getDateUnderway()|date_format:$dateFormatShort}
					{else}
						-
					{/if}
				</td>
				<td width="20%" align="center">
					{if $layoutAssignment->getDateCompleted()}
						{$layoutAssignment->getDateCompleted()|date_format:$dateFormatShort}
					{else}
						-
					{/if}
				</td>
				<td width="20%" align="center">
					{if $layoutAssignment->getDateAcknowledged()}
						{$layoutAssignment->getDateAcknowledged()|date_format:$dateFormatShort}
					{else}
					-
					{/if}
				</td>
			</tr>
		</table>
	</td>
</tr>
<tr class="submissionDivider">
	<td></td>
</tr>
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td>&nbsp;&nbsp;</td>
				<td width="20%"><strong>{translate key="submission.layout.galleys"}</strong></td>
				<td width="10%" align="center"><strong>{translate key="submission.layout.proof"}</strong></td>
				<td width="15%" align="center"><strong>{translate key="common.file"}</strong></td>
				<td width="25%" align="center"><strong>{translate key="common.originalFileName"}</strong></td>
				<td width="10%" align="center"><strong>{translate key="common.updated"}</strong></td>
				<td width="10%" align="center"><strong>{translate key="common.order"}</strong></td>
				<td width="10%" align="center"><strong>{translate key="common.action"}</strong></td>
			</tr>
		</table>
	</td>
</tr>
{foreach name=galleys from=$submission->getGalleys() item=galley}
<tr class="submissionRow">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td><span class="boldText">{$smarty.foreach.galleys.iteration}.</span></td>
				<td width="20%"><a href="{$requestPageUrl}/editGalley/{$submission->getArticleId()}/{$galley->getGalleyId()}">{$galley->getLabel()}</a></td>
				<td width="10%" align="center"><a href="{$requestPageUrl}/proofGalley/{$submission->getArticleId()}/{$galley->getGalleyId()}" class="file">{translate key="common.view"}</a></td>
				<td width="15%" align="center"><a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$galley->getFileId()}" class="file">{$galley->getFileName()}</a></td>
				<td width="25%" align="center">{$galley->getOriginalFileName()}</td>				
				<td width="10%" align="center">{$galley->getDateModified()|date_format:$dateFormatShort}</td>
				<td width="10%" align="center"><a href="{$requestPageUrl}/orderGalley?d=u&amp;articleId={$submission->getArticleId()}&amp;galleyId={$galley->getGalleyId()}">&uarr;</a> <a href="{$requestPageUrl}/orderGalley?d=d&amp;articleId={$submission->getArticleId()}&amp;galleyId={$galley->getGalleyId()}">&darr;</a></td>
				<td width="10%" align="center">
					{icon name="edit" url="$requestPageUrl/editGalley/`$submission->getArticleId()`/`$galley->getGalleyId()`"}&nbsp;<a href="{$requestPageUrl}/deleteGalley/{$submission->getArticleId()}/{$galley->getGalleyId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.layout.confirmDeleteGalley"}')" class="icon">{icon name="delete"}</a>
				</td>
			</tr>
			{if $galley->isHTMLGalley()}
			{assign var=galleyStyleFile value=$galley->getStyleFile()}
			<tr>
				<td></td>
				<td colspan="6">
					<span class="highlightText">{translate key="submission.layout.galleyStyle"}:</span>
					{if $galleyStyleFile}
					<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$galleyStyleFile->getFileId()}" class="file">{$galleyStyleFile->getFileName()}</a>
					{else}
					-
					{/if}
					&nbsp;&nbsp;
					<span class="highlightText">{translate key="submission.layout.galleyImages"}:</span>
				{foreach from=$galley->getImageFiles() item=galleyImageFile}
				<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$galleyImageFile->getFileId()}" class="file">{$galleyImageFile->getFileName()}</a>
				{foreachelse}
				-
				{/foreach}
				</td>
			</tr>
			{/if}
		</table>
	</td>
</tr>
{foreachelse}
<tr class="submissionRowAlt">
	<td class="submissionBox" align="center">
		<span class="boldText">{translate key="common.none"}</span>
	</td>
</tr>
{/foreach}
<tr class="submissionRowAlt">
	<td class="submissionBox" align="right">
		<table class="plainFormat" width="100%">
			<tr>
				<td width="25%" align="right">
					<span class="boldText">{translate key="submission.layout.newGalley"}:</span>
				</td>
				<td width="75%">
					<form method="post" action="{$requestPageUrl}/uploadGalley" enctype="multipart/form-data">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
						<input type="file" name="galleyFile" />
						<input type="submit" name="submit" value="{translate key="common.upload"}" />
					</form>
				</td>
			</tr>
		</table>
	</td>
</tr>
<tr class="submissionDivider">
	<td></td>
</tr>
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td>&nbsp;&nbsp;</td>
				<td width="30%"><strong>{translate key="submission.layout.supplementaryFiles"}</strong></td>
				<td width="15%" align="center"><strong>{translate key="common.file"}</strong></td>
				<td width="25%" align="center"><strong>{translate key="common.originalFileName"}</strong></td>
				<td width="10%" align="center"><strong>{translate key="common.updated"}</strong></td>
				<td width="10%" align="center"><strong>{translate key="common.order"}</strong></td>
				<td width="10%" align="center"><strong>{translate key="common.action"}</strong></td>
			</tr>
		</table>
	</td>
</tr>
{foreach name=suppFiles from=$submission->getSuppFiles() item=suppFile}
<tr class="submissionRow">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td><span class="boldText">{$smarty.foreach.suppFiles.iteration}.</span></td>
				<td width="30%"><a href="{$requestPageUrl}/editSuppFile/{$submission->getArticleId()}/{$suppFile->getSuppFileId()}">{$suppFile->getTitle()}</a></td>
				<td width="15%" align="center"><a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$suppFile->getFileId()}" class="file">{$suppFile->getFileName()}</a></td>
				<td width="25%" align="center">{$suppFile->getOriginalFileName()}</td>
				<td width="10%" align="center">{$suppFile->getDateModified()|date_format:$dateFormatShort}</td>
				<td width="10%" align="center"><a href="{$requestPageUrl}/orderSuppFile?d=u&amp;articleId={$submission->getArticleId()}&amp;suppFileId={$suppFile->getSuppFileId()}">&uarr;</a> <a href="{$requestPageUrl}/orderSuppFile?d=d&amp;articleId={$submission->getArticleId()}&amp;suppFileId={$suppFile->getSuppFileId()}">&darr;</a></td>
				<td width="10%" align="center">
					{icon name="edit" url="$requestPageUrl/editSuppFile/`$submission->getArticleId()`/`$suppFile->getSuppFileId()`"}&nbsp;<a href="{$requestPageUrl}/deleteSuppFile/{$submission->getArticleId()}/{$suppFile->getSuppFileId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.layout.confirmDeleteSupplementaryFile"}')" class="icon">{icon name="delete"}</a>
				</td>
			</tr>
		</table>
	</td>
</tr>
{foreachelse}
<tr class="submissionRowAlt">
	<td class="submissionBox" align="center">
		<span class="boldText">{translate key="common.none"}</span>
	</td>
</tr>
{/foreach}
<tr class="submissionRowAlt">
	<td class="submissionBox" align="right">
		<table class="plainFormat" width="100%">
			<tr>
				<td width="25%" align="right">
					<span class="boldText">{translate key="submission.layout.newSupplementaryFile"}:</span>
				</td>
				<td width="75%">
					<form method="post" action="{$requestPageUrl}/uploadSuppFile" enctype="multipart/form-data">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
			<input type="file" name="uploadSuppFile" />
						<input type="submit" name="submit" value="{translate key="common.upload"}" />
					</form>
				</td>
			</tr>
		</table>
	</td>
</tr>
<tr class="submissionDivider">
	<td></td>
</tr>
<tr class="submissionRow">
	<td class="submissionBox">
		<span class="boldText">{translate key="submission.layout.layoutComments"}</span>
		{if $submission->getMostRecentLayoutComment()}
			{assign var="comment" value=$submission->getMostRecentLayoutComment()}
			<a href="javascript:openComments('{$requestPageUrl}/viewLayoutComments/{$submission->getArticleId()}#{$comment->getCommentId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>{$comment->getDatePosted()|date_format:$dateFormatShort}
		{else}
			<a href="javascript:openComments('{$requestPageUrl}/viewLayoutComments/{$submission->getArticleId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>
		{/if}
	</td>
</tr>
</table>
</div>
