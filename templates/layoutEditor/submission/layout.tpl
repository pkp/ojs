{**
 * layout.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the layout editor's layout editing table.
 *
 * $Id$
 *}

<a name="layout"></a>
<h3>{translate key="submission.layout"}</h3>

<p>{translate key="user.role.layoutEditor"}:
&nbsp; {$layoutAssignment->getEditorFullName()}</p>

<table width="100%" class="info">
	<tr>
		<td width="28%" colspan="2">{translate key="submission.layout.layoutVersion"}</td>
		<td width="18%" class="heading">{translate key="submission.request"}</td>
		<td width="18%" class="heading">{translate key="submission.underway"}</td>
		<td width="18%" class="heading">{translate key="submission.complete"}</td>
		<td width="18%">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="2">
			{if $layoutFile}
				<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$layoutFile->getFileId()}" class="file">{$layoutFile->getFileName()}</a> {$layoutFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
		</td>
		<td>
			{$layoutAssignment->getDateNotified()|date_format:$dateFormatShort|default:"&mdash;"}
		</td>
		<td>
			{$layoutAssignment->getDateUnderway()|date_format:$dateFormatShort|default:"&mdash;"}
		</td>
		<td>
			{if !$layoutAssignment->getDateNotified() or $layoutAssignment->getDateCompleted()}
				{icon name="mail" disabled="disabled"}
			{else}
				{icon name="mail" url="$requestPageUrl/completeAssignment/`$submission->getArticleId()`"}
			{/if}
						{$layoutAssignment->getDateCompleted()|date_format:$dateFormatShort|default:""}
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="6" class="separator">&nbsp;</td>
	</tr>
	<tr>
		<td width="28%" colspan="2">{translate key="submission.layout.galleyFormat"}</td>
		<td width="36%" colspan="2" class="heading">{translate key="common.file"}</td>
		<td width="18%" class="heading">{translate key="common.order"}</td>
		<td width="18%" class="heading">{translate key="common.action"}</td>
	</tr>
	{foreach name=galleys from=$submission->getGalleys() item=galley}
	<tr>
		<td width="5%">{$smarty.foreach.galleys.iteration}.</td>
		<td width="23%">{$galley->getLabel()} &nbsp; <a href="{$requestPageUrl}/proofGalley/{$submission->getArticleId()}/{$galley->getGalleyId()}" class="action">{translate key="submission.layout.viewProof"}</td>
		<td colspan="2"><a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$galley->getFileId()}" class="file">{$galley->getFileName()}</a> {$galley->getDateModified()|date_format:$dateFormatShort}</td>
		<td><a href="{$requestPageUrl}/orderGalley?d=u&amp;articleId={$submission->getArticleId()}&amp;galleyId={$galley->getGalleyId()}" class="plain">&uarr;</a> <a href="{$requestPageUrl}/orderGalley?d=d&amp;articleId={$submission->getArticleId()}&amp;galleyId={$galley->getGalleyId()}" class="plain">&darr;</a></td>
		<td>
			{if $disableEdit}
				&mdash;
			{else}
			<a href="{$requestPageUrl}/editGalley/{$submission->getArticleId()}/{$galley->getGalleyId()}" class="action">{translate key="common.edit"}</a>
			<a href="{$requestPageUrl}/deleteGalley/{$submission->getArticleId()}/{$galley->getGalleyId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.layout.confirmDeleteGalley"}')" class="action">{translate key="common.delete"}</a>
			{/if}
		</td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="6" class="nodata">{translate key="common.none"}</td>
	</tr>
	{/foreach}
	<tr>
		<td>&nbsp;</td>
		<td colspan="5">
			<form method="post" action="{$requestPageUrl}/uploadGalley" enctype="multipart/form-data">
				{translate key="layoutEditor.galley.uploadGalleyFormat"}
				&nbsp;
				<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
				<input type="file" name="galleyFile"{if $disableEdit} disabled="disabled"{/if} class="uploadField" />
				<input type="submit" name="submit" value="{translate key="common.upload"}"{if $disableEdit} disabled="disabled"{/if} class="button" />
			</form>
		</td>
	</tr>
	<tr>
		<td colspan="6" class="separator">&nbsp;</td>
	</tr>
	<tr>
		<td width="28%" colspan="2">{translate key="submission.supplementaryFiles"}</td>
		<td width="36%" colspan="2" class="heading">{translate key="common.file"}</td>
		<td width="18%" class="heading">{translate key="common.order"}</td>
		<td width="18%" class="heading">{translate key="common.action"}</td>
	</tr>
	{foreach name=suppFiles from=$submission->getSuppFiles() item=suppFile}
	<tr>
		<td width="5%">{$smarty.foreach.suppFiles.iteration}.</td>
		<td width="23%">{$suppFile->getTitle()}</td>
		<td colspan="2"><a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$suppFile->getFileId()}" class="file">{$suppFile->getFileName()}</a> {$suppFile->getDateModified()|date_format:$dateFormatShort}</td>
		<td><a href="{$requestPageUrl}/orderSuppFile?d=u&amp;articleId={$submission->getArticleId()}&amp;suppFileId={$suppFile->getSuppFileId()}" class="plain">&uarr;</a> <a href="{$requestPageUrl}/orderSuppFile?d=d&amp;articleId={$submission->getArticleId()}&amp;suppFileId={$suppFile->getSuppFileId()}" class="plain">&darr;</a></td>
		<td>
			{if $disableEdit}
				&mdash;
			{else}
			<a href="{$requestPageUrl}/editSuppFile/{$submission->getArticleId()}/{$suppFile->getSuppFileId()}" class="action">{translate key="common.edit"}</a>
			<a href="{$requestPageUrl}/deleteSuppFile/{$submission->getArticleId()}/{$suppFile->getSuppFileId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.layout.confirmDeleteSupplementaryFile"}')" class="action">{translate key="common.delete"}</a>
			{/if}
		</td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="6" class="nodata">{translate key="common.none"}</td>
	</tr>
	{/foreach}
	<tr>
		<td>&nbsp;</td>
		<td colspan="5">
			<form method="post" action="{$requestPageUrl}/uploadSuppFile" enctype="multipart/form-data">
				{translate key="layoutEditor.galley.uploadSuppFile"}
				&nbsp;
				<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
				<input type="file" name="uploadSuppFile"{if $disableEdit} disabled="disabled"{/if} class="uploadField" />
				<input type="submit" name="submit" value="{translate key="common.upload"}"{if $disableEdit} disabled="disabled"{/if} class="button" />
			</form>
		</td>
	</tr>
	<tr>
		<td colspan="6" class="separator">&nbsp;</td>
	</tr>
</table>

{translate key="submission.layout.layoutComments"}
{if $submission->getMostRecentLayoutComment()}
	{assign var="comment" value=$submission->getMostRecentLayoutComment()}
	<a href="javascript:openComments('{$requestPageUrl}/viewLayoutComments/{$submission->getArticleId()}#{$comment->getCommentId()}');" class="icon">{icon name="comment"}</a>{$comment->getDatePosted()|date_format:$dateFormatShort}
{else}
	<a href="javascript:openComments('{$requestPageUrl}/viewLayoutComments/{$submission->getArticleId()}');" class="icon">{icon name="comment"}</a>
{/if}

{if $currentJournal->getSetting('layoutInstructions')}
&nbsp;&nbsp;
<a href="javascript:openHelp('{$requestPageUrl}/instructions/layout')" class="action">{translate key="submission.layout.instructions"}</a>
{/if}
