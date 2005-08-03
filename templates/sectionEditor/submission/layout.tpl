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

{if $useLayoutEditors}
<p>{translate key="user.role.layoutEditor"}:
{if $layoutAssignment->getEditorId()}&nbsp; {$layoutAssignment->getEditorFullName()|escape}{/if}
&nbsp; <a href="{$requestPageUrl}/assignLayoutEditor/{$submission->getArticleId()}" class="action">{translate key="submission.layout.assignLayoutEditor"}</a></p>
{/if}

<table width="100%" class="info">
	<tr>
		<td width="28%" colspan="2">{translate key="submission.layout.layoutVersion"}</td>
		<td width="18%" class="heading">{translate key="submission.request"}</td>
		<td width="16%" class="heading">{translate key="submission.underway"}</td>
		<td width="16%" class="heading">{translate key="submission.complete"}</td>
		<td width="22%" colspan="2" class="heading">{translate key="submission.acknowledge"}</td>
	</tr>
	<tr>
		<td colspan="2">
			{if $layoutFile}
				<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$layoutFile->getFileId()}" class="file">{$layoutFile->getFileName()|escape}</a>&nbsp;&nbsp;{$layoutFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
				{assign var=needsLayoutFileNote value=1}
			{/if}
		</td>
		<td>
			{if $useLayoutEditors}
				{if $layoutAssignment->getEditorId() && $layoutFile}
					{if $layoutAssignment->getDateUnderway()}
                                        	{assign_translate|escape:"javascript" var=confirmText key="sectionEditor.layout.confirmRenotify"}
                                        	{icon name="mail" onClick="return confirm('$confirmText')" url="$requestPageUrl/notifyLayoutEditor?articleId=`$submission->getArticleId()`"}
                                	{else}
                                        	{icon name="mail" url="$requestPageUrl/notifyLayoutEditor?articleId=`$submission->getArticleId()`"}
                                	{/if}
				{else}
					{icon name="mail" disabled="disable"}
				{/if}
				{$layoutAssignment->getDateNotified()|date_format:$dateFormatShort|default:""}
			{else}
				{translate key="common.notApplicableShort"}
			{/if}
		</td>
		<td>
			{if $useLayoutEditors}
				{$layoutAssignment->getDateUnderway()|date_format:$dateFormatShort|default:"&mdash;"}
			{else}
				{translate key="common.notApplicableShort"}
			{/if}
		</td>
		<td>
			{if $useLayoutEditors}
				{$layoutAssignment->getDateCompleted()|date_format:$dateFormatShort|default:"&mdash;"}
			{else}
				{translate key="common.notApplicableShort"}
			{/if}
		</td>
		<td colspan="2">
			{if $useLayoutEditors}
				{if $layoutAssignment->getEditorId() &&  $layoutAssignment->getDateCompleted() && !$layoutAssignment->getDateAcknowledged()}
					{icon name="mail" url="$requestPageUrl/thankLayoutEditor?articleId=`$submission->getArticleId()`"}
				{else}
					{icon name="mail" disabled="disable"}
				{/if}
				{$layoutAssignment->getDateAcknowledged()|date_format:$dateFormatShort|default:""}
			{else}
				{translate key="common.notApplicableShort"}
			{/if}
		</td>
	</tr>
	{if $needsLayoutFileNote}
		<tr valign="top">
			<td colspan="2">&nbsp;</td>
			<td colspan="4">
				{translate key="submission.layout.mustUploadFileForLayout"}
			</td>
		</tr>
	{/if}
	<tr>
		<td colspan="7" class="separator">&nbsp;</td>
	</tr>

	<tr>
		<td colspan="2">{translate key="submission.layout.galleyFormat"}</td>
		<td colspan="2" class="heading">{translate key="common.file"}</td>
		<td class="heading">{translate key="common.order"}</td>
		<td class="heading">{translate key="common.action"}</td>
		<td class="heading">{translate key="submission.views"}</td>
	</tr>
	{foreach name=galleys from=$submission->getGalleys() item=galley}
	<tr>
		<td width="2%">{$smarty.foreach.galleys.iteration}.</td>
		<td width="26%">{$galley->getLabel()|escape} &nbsp; <a href="{$requestPageUrl}/proofGalley/{$submission->getArticleId()}/{$galley->getGalleyId()}" class="action">{translate key="submission.layout.viewProof"}</td>
		<td colspan="2"><a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$galley->getFileId()}" class="file">{$galley->getFileName()|escape}</a>&nbsp;&nbsp;{$galley->getDateModified()|date_format:$dateFormatShort}</td>
		<td><a href="{$requestPageUrl}/orderGalley?d=u&amp;articleId={$submission->getArticleId()}&amp;galleyId={$galley->getGalleyId()}" class="plain">&uarr;</a> <a href="{$requestPageUrl}/orderGalley?d=d&amp;articleId={$submission->getArticleId()}&amp;galleyId={$galley->getGalleyId()}" class="plain">&darr;</a></td>
		<td>
			<a href="{$requestPageUrl}/editGalley/{$submission->getArticleId()}/{$galley->getGalleyId()}" class="action">{translate key="common.edit"}</a>
			<a href="{$requestPageUrl}/deleteGalley/{$submission->getArticleId()}/{$galley->getGalleyId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.layout.confirmDeleteGalley"}')" class="action">{translate key="common.delete"}</a>
		</td>
		<td>{$galley->getViews()|escape}</td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="7" class="nodata">{translate key="common.none"}</td>
	</tr>
	{/foreach}
	<tr>
		<td colspan="7" class="separator">&nbsp;</td>
	</tr>
	<tr>
		<td width="28%" colspan="2">{translate key="submission.supplementaryFiles"}</td>
		<td width="34%" colspan="2" class="heading">{translate key="common.file"}</td>
		<td width="16%" class="heading">{translate key="common.order"}</td>
		<td width="16%" colspan="2" class="heading">{translate key="common.action"}</td>
	</tr>
	{foreach name=suppFiles from=$submission->getSuppFiles() item=suppFile}
	<tr>
		<td width="2%">{$smarty.foreach.suppFiles.iteration}.</td>
		<td width="26%">{$suppFile->getTitle()}</td>
		<td colspan="2"><a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$suppFile->getFileId()}" class="file">{$suppFile->getFileName()|escape}</a>&nbsp;&nbsp;{$suppFile->getDateModified()|date_format:$dateFormatShort}</td>
		<td><a href="{$requestPageUrl}/orderSuppFile?d=u&amp;articleId={$submission->getArticleId()}&amp;suppFileId={$suppFile->getSuppFileId()}" class="plain">&uarr;</a> <a href="{$requestPageUrl}/orderSuppFile?d=d&amp;articleId={$submission->getArticleId()}&amp;suppFileId={$suppFile->getSuppFileId()}" class="plain">&darr;</a></td>
		<td colspan="2">
			<a href="{$requestPageUrl}/editSuppFile/{$submission->getArticleId()}/{$suppFile->getSuppFileId()}" class="action">{translate key="common.edit"}</a>
			<a href="{$requestPageUrl}/deleteSuppFile/{$submission->getArticleId()}/{$suppFile->getSuppFileId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.layout.confirmDeleteSupplementaryFile"}')" class="action">{translate key="common.delete"}</a>
		</td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="7" class="nodata">{translate key="common.none"}</td>
	</tr>
	{/foreach}
	<tr>
		<td colspan="7" class="separator">&nbsp;</td>
	</tr>
</table>

<form method="post" action="{$requestPageUrl}/uploadLayoutFile"  enctype="multipart/form-data">
	<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
	{translate key="submission.uploadFileTo"} <input type="radio" name="layoutFileType" id="layoutFileTypeSubmission" value="submission" checked="checked" /><label for="layoutFileTypeSubmission">{translate key="submission.layout.layoutVersion"}</label>, <input type="radio" name="layoutFileType" id="layoutFileTypeGalley" value="galley" /><label for="layoutFileTypeGalley">{translate key="submission.galley"}</label>, <input type="radio" name="layoutFileType" id="layoutFileTypeSupp" value="supp" /><label for="layoutFileTypeSupp">{translate key="article.suppFilesAbbrev"}</label>
	<input type="file" name="layoutFile" size="10" class="uploadField" />
	<input type="submit" value="{translate key="common.upload"}" class="button" />
</form>

<p>
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
</p>
