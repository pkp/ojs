{**
 * layout.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Layout editor's view of submission layout details.
 *
 * $Id$
 *}

<a name="layout"></a>

<h3>{translate key="submission.layout"}</h3>

<table class="data" width="100%">
	{if $useLayoutEditors}
		<tr valign="top">
			<td width="20%" class="label">{translate key="user.role.layoutEditor"}</td>
			{if $layoutAssignment->getEditorId()}
				<td width="30%" class="value">
					{$layoutAssignment->getEditorFullName()}
				</td>
				<td width="50%"
					<a href="{$requestPageUrl}/assignLayoutEditor/{$submission->getArticleId()}" class="action">{translate key="submission.layout.replaceLayoutEditor"}</a>
				</td>
			{else}
				<td colspan="2" class="value" width="80%">
					<a href="{$requestPageUrl}/assignLayoutEditor/{$submission->getArticleId()}" class="action">{translate key="submission.layout.assignLayoutEditor"}</a>
				</td>
			{/if}
		</tr>
	{/if}

	<tr valign="top">
		<td width="20%" class="label">{translate key="submission.layout.layoutVersion"}</td>
		<td width="80%" colspan="2" class="value">
			{if $layoutFile}
				<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$layoutFile->getFileId()}" class="file">{$layoutFile->getFileName()}</a> {$layoutFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
		</td>
	</tr>
</table>

<table class="info" width="100%">
	<tr valign="top">
		<td width="20%">{translate key="submission.layout.initialGalleyCreation"}</td>
		<td class="heading" width="20%">{translate key="submission.request"}</td>
		<td class="heading" width="20%">{translate key="submission.underway"}</td>
		<td class="heading" width="20%">
			{if !$disableEdit && !$layoutAssignment->getDateCompleted()}
				<a href="{$requestPageUrl}/completeAssignment/{$submission->getArticleId()}">{translate key="layoutEditor.article.complete"}</a>
			{else}
				{translate key="submission.complete"}
			{/if}
		</td>
		<td class="heading" width="20%">{translate key="submission.thank"}</td>
	</tr>
	<tr valign="top">
		<td width="20%"></td>
		<td width="20%">
			{if $layoutAssignment->getDateNotified()}
				{$layoutAssignment->getDateNotified()|date_format:$dateFormatShort}
			{else}
				&mdash;
			{/if}
		</td>
		<td width="20%">
			{if $layoutAssignment->getDateUnderway()}
				{$layoutAssignment->getDateUnderway()|date_format:$dateFormatShort}
			{else}
				&mdash;
			{/if}
		</td>
		<td width="20%">
			{if $layoutAssignment->getDateCompleted()}
				{$layoutAssignment->getDateCompleted()|date_format:$dateFormatShort}
			{else}
				&mdash;
			{/if}
		</td>
		<td width="20%">
			{if $layoutAssignment->getDateAcknowledged()}
				{$layoutAssignment->getDateAcknowledged()|date_format:$dateFormatShort}
			{else}
				&mdash;
			{/if}
		</td>
	</tr>
</table>

<table width="100%" class="info" border="0">
	<tr valign="top">
		<td colspan="3" class="heading" width="40%">{translate key="submission.layout.galleyFormat"}</td>
		<td colspan="2" class="heading" width="20%">{translate key="common.file"}</td>
		<td class="heading" width="20%">{translate key="common.order"}</td>
		<td class="heading" width="20%">{translate key="common.action"}</td>
	</tr>
{foreach name=galleys from=$submission->getGalleys() item=galley}
	<tr valign="top">
		<td>{$smarty.foreach.galleys.iteration}.</td>
		<td>
			{if $galley->isHTMLGalley()}{translate key="layoutEditor.galley.html"}
			{else}{translate key="layoutEditor.galley.pdf"}
			{/if}
		</td>
		<td><a href="{$requestPageUrl}/proofGalley/{$submission->getArticleId()}/{$galley->getGalleyId()}" class="action">{translate key="submission.layout.viewProof"}</a></td>
		<td>
			<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$galley->getFileId()}" class="action">{$galley->getFileName()}</a>
		</td>
		<td>{$galley->getDateModified()|date_format:$dateFormatShort}</td>
		<td>{if $disableEdit}&uarr;{else}<a href="{$requestPageUrl}/orderGalley?d=u&amp;articleId={$submission->getArticleId()}&amp;galleyId={$galley->getGalleyId()}">&uarr;</a>{/if} {if $disableEdit}&darr;{else}<a href="{$requestPageUrl}/orderGalley?d=d&amp;articleId={$submission->getArticleId()}&amp;galleyId={$galley->getGalleyId()}">&darr;</a>{/if}</td>
		<td>
			{icon name="edit" disabled="$disableEdit" url="$requestPageUrl/editGalley/`$submission->getArticleId()`/`$galley->getGalleyId()`"}&nbsp;{if $disableEdit}{icon name="delete" disabled="true"}{else}<a href="{$requestPageUrl}/deleteGalley/{$submission->getArticleId()}/{$galley->getGalleyId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.layout.confirmDeleteGalley"}')" class="icon">{icon name="delete"}</a>{/if}
		</td>
	</tr>
	{if $galley->isHTMLGalley()}
		{assign var=galleyStyleFile value=$galley->getStyleFile()}
		<tr>
			<td colspan="7">
				<strong>{translate key="submission.layout.galleyStyle"}:</strong>
				{if $galleyStyleFile}
					<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$galleyStyleFile->getFileId()}" class="file">{$galleyStyleFile->getFileName()}</a>
				{else}
					&mdash;
				{/if}
				&nbsp;&nbsp;
				<strong>{translate key="submission.layout.galleyImages"}:</strong>
				{foreach from=$galley->getImageFiles() item=galleyImageFile}
					<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$galleyImageFile->getFileId()}" class="file">{$galleyImageFile->getFileName()}</a>&nbsp;
				{foreachelse}
					&mdash;
				{/foreach}
			</td>
		</tr>
	{/if}
{foreachelse}
	<tr valign="top">
		<td colspan="7" class="nodata">
			{translate key="common.none"}
		</td>
	</tr>
{/foreach}
	<tr>
		<td colspan="7">
			<form method="post" action="{$requestPageUrl}/uploadGalley" enctype="multipart/form-data">
				{translate key="layoutEditor.galley.uploadGalleyFormat"}&nbsp;
				<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
				<input type="file" name="galleyFile"{if $disableEdit} disabled="disabled"{/if} class="button" />
				<input type="submit" name="submit" value="{translate key="common.upload"}"{if $disableEdit} disabled="disabled"{/if} class="button" />
			</form>
		</td>
	</tr>

	<tr valign="top">
		<td class="heading" colspan="3">{translate key="submission.supplementaryFiles"}</td>
		<td class="heading" colspan="2">{translate key="common.file"}</td>
		<td class="heading">{translate key="common.order"}</td>
		<td class="heading">{translate key="common.action"}</td>
	</tr>
{foreach name=suppFiles from=$submission->getSuppFiles() item=suppFile}
	<tr valign="top">
		<td>{$smarty.foreach.suppFiles.iteration}.</td>
		<td>{$suppFile->getTitle()}</td>
		<td><a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$suppFile->getFileId()}" class="file">{$suppFile->getFileName()}</a></td>
		<td>{$suppFile->getDateModified()|date_format:$dateFormatShort}</td>
		<td>{if $disableEdit}&uarr;{else}<a href="{$requestPageUrl}/orderSuppFile?d=u&amp;articleId={$submission->getArticleId()}&amp;suppFileId={$suppFile->getSuppFileId()}">&uarr;</a>{/if} {if $disableEdit}&darr;{else}<a href="{$requestPageUrl}/orderSuppFile?d=d&amp;articleId={$submission->getArticleId()}&amp;suppFileId={$suppFile->getSuppFileId()}">&darr;</a>{/if}</td>
		<td width="15%" align="center">
			{icon name="edit" disabled="$disableEdit" url="$requestPageUrl/editSuppFile/`$submission->getArticleId()`/`$suppFile->getSuppFileId()`"}&nbsp;{if $disableEdit}{icon name="delete" disabled="true"}{else}<a href="{$requestPageUrl}/deleteSuppFile/{$submission->getArticleId()}/{$suppFile->getSuppFileId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.layout.confirmDeleteSupplementaryFile"}')" class="icon">{icon name="delete"}</a>{/if}
		</td>
	</tr>
{foreachelse}
	<tr valign="top">
		<td colspan="7" class="nodata">
			{translate key="common.none"}
		</td>
	</tr>
{/foreach}
	<tr valign="top">
		<td colspan="7">
			<form method="post" action="{$requestPageUrl}/uploadSuppFile" enctype="multipart/form-data">
				{translate key="submission.addSuppFile"}&nbsp;
				<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
				<input class="button" type="file" name="uploadSuppFile"{if $disableEdit} disabled="disabled"{/if} />
				<input class="button" type="submit" name="submit" value="{translate key="common.upload"}"{if $disableEdit} disabled="disabled"{/if} />
			</form>
		</td>
	</tr>
</table>

<a href="javascript:openComments('{$requestPageUrl}/viewLayoutComments/{$submission->getArticleId()}');">{translate key="submission.layout.layoutComments"}</a>:&nbsp;&nbsp;
{if $submission->getMostRecentLayoutComment()}
	{assign var="comment" value=$submission->getMostRecentLayoutComment()}
	<a href="javascript:openComments('{$requestPageUrl}/viewLayoutComments/{$submission->getArticleId()}#{$comment->getCommentId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>{$comment->getDatePosted()|date_format:$dateFormatShort}
{else}
	{translate key="common.none"}
{/if}
</table>

