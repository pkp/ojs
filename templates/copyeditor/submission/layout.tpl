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
{if $layoutAssignment->getEditorId()}&nbsp; {$layoutAssignment->getEditorFullName()}{/if}
{/if}

<table width="100%" class="info">
	{if $useLayoutEditors}
	<tr>
		<td width="28%" colspan="2">{translate key="submission.layout.layoutVersion"}</td>
		<td width="18%" class="heading">{translate key="submission.request"}</td>
		<td width="18%" class="heading">{translate key="submission.underway"}</td>
		<td width="18%" class="heading">{translate key="submission.complete"}</td>
		<td width="18%" class="heading">{translate key="submission.acknowledge"}</td>
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
			{$layoutAssignment->getDateNotified()|date_format:$dateFormatShort|default:""}
		</td>
		<td>
			{$layoutAssignment->getDateUnderway()|date_format:$dateFormatShort|default:"&mdash;"}
		</td>
		<td>
			{$layoutAssignment->getDateCompleted()|date_format:$dateFormatShort|default:"&mdash;"}
		</td>
		<td>
			{$layoutAssignment->getDateAcknowledged()|date_format:$dateFormatShort|default:""}
		</td>
	</tr>
	<tr>
		<td colspan="6" class="separator"></td>
	</tr>
	{/if}
	<tr>
		<td width="28%" colspan="2">{translate key="submission.layout.galleyFormat"}</td>
		<td width="36%" colspan="2" class="heading">{translate key="common.file"}</td>
		<td width="18%" class="heading">{translate key="common.order"}</td>
	</tr>
	{foreach name=galleys from=$submission->getGalleys() item=galley}
	<tr>
		<td width="5%">{$smarty.foreach.galleys.iteration}.</td>
		<td width="23%">{$galley->getLabel()} &nbsp; <a href="{$requestPageUrl}/proofGalley/{$submission->getArticleId()}/{$galley->getGalleyId()}" class="action">{translate key="submission.layout.viewProof"}</td>
		<td colspan="2"><a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$galley->getFileId()}" class="file">{$galley->getFileName()}</a> {$galley->getDateModified()|date_format:$dateFormatShort}</td>
		<td><a href="{$requestPageUrl}/orderGalley?d=u&amp;articleId={$submission->getArticleId()}&amp;galleyId={$galley->getGalleyId()}" class="plain">&uarr;</a> <a href="{$requestPageUrl}/orderGalley?d=d&amp;articleId={$submission->getArticleId()}&amp;galleyId={$galley->getGalleyId()}" class="plain">&darr;</a></td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="5" class="nodata">{translate key="common.none"}</td>
	</tr>
	{/foreach}
	<tr>
		<td colspan="5" class="separator"></td>
	</tr>
	<tr>
		<td colspan="2">{translate key="submission.supplementaryFiles"}</td>
		<td colspan="3" class="heading">{translate key="common.file"}</td>
	</tr>
	{foreach name=suppFiles from=$submission->getSuppFiles() item=suppFile}
	<tr>
		<td width="5%">{$smarty.foreach.suppFiles.iteration}.</td>
		<td width="23%">{$suppFile->getTitle()}</td>
		<td colspan="3"><a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$suppFile->getFileId()}" class="file">{$suppFile->getFileName()}</a> {$suppFile->getDateModified()|date_format:$dateFormatShort}</td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="5" class="nodata">{translate key="common.none"}</td>
	</tr>
	{/foreach}
	<tr>
		<td colspan="5" class="separator"></td>
	</tr>
</table>

<p>{translate key="submission.layout.layoutComments"}
{if $submission->getMostRecentLayoutComment()}
	{assign var="comment" value=$submission->getMostRecentLayoutComment()}
	<a href="javascript:openComments('{$requestPageUrl}/viewLayoutComments/{$submission->getArticleId()}#{$comment->getCommentId()}');" class="icon">{icon name="comment"}</a>{$comment->getDatePosted()|date_format:$dateFormatShort}
{else}
	<a href="javascript:openComments('{$requestPageUrl}/viewLayoutComments/{$submission->getArticleId()}');" class="icon">{icon name="comment"}</a>
{/if}</p>
