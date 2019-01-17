{**
 * templates/copyeditor/submission/layout.tpl
 *
 * Copyright (c) 2013-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the layout editing table.
 *}
{assign var=layoutSignoff value=$submission->getSignoff('SIGNOFF_LAYOUT')}
{assign var=layoutFile value=$submission->getFileBySignoffType('SIGNOFF_LAYOUT')}
{assign var=layoutEditor value=$submission->getUserBySignoffType('SIGNOFF_LAYOUT')}

<div id="layout">
<h3>{translate key="submission.layout"}</h3>

{if $useLayoutEditors}
<div id="layoutEditors">
<table width="100%" class="data">
	<tr>
		<td class="label" width="20%">{translate key="user.role.layoutEditor"}</td>
		<td class="value" width="80%">{if $layoutSignoff->getUserId()}{$layoutEditor->getFullName()|escape}{else}{translate key="common.none"}{/if}</td>
	</tr>
</table>
</div>
{/if}

<table width="100%" class="info">
	<tr>
		<td width="40%" colspan="2">{translate key="submission.layout.galleyFormat"}</td>
		<td width="60%" class="heading">{translate key="common.file"}</td>
	</tr>
	{foreach name=galleys from=$submission->getGalleys() item=galley}
	<tr>
		<td width="5%">{$smarty.foreach.galleys.iteration}.</td>
		<td width="35%">{$galley->getGalleyLabel()|escape}{if !$galley->getRemoteURL()} &nbsp; <a href="{url op="proofGalley" path=$submission->getId()|to_array:$galley->getId()}" class="action">{translate key="submission.layout.viewProof"}{/if}</td>
		<td>{if $galley->getFileId() > 0}<a href="{url op="downloadFile" path=$submission->getId()|to_array:$galley->getFileId()}" class="file">{$galley->getFileName()|escape}</a> {$galley->getDateModified()|date_format:$dateFormatShort}{elseif $galley->getRemoteURL() != ''}<a href="{$galley->getRemoteURL()|escape}" target="_blank">{$galley->getRemoteURL()|truncate:20:"..."|escape}</a>{/if}</td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="3" class="nodata">{translate key="common.none"}</td>
	</tr>
	{/foreach}
	<tr>
		<td colspan="3" class="separator">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="2">{translate key="submission.supplementaryFiles"}</td>
		<td class="heading">{translate key="common.file"}</td>
	</tr>
	{foreach name=suppFiles from=$submission->getSuppFiles() item=suppFile}
	<tr>
		<td width="5%">{$smarty.foreach.suppFiles.iteration}.</td>
		<td width="35%">{$suppFile->getSuppFileTitle()|escape}</td>
		<td>{if $suppFile->getFileId() > 0}<a href="{url op="downloadFile" path=$submission->getId()|to_array:$suppFile->getFileId()}" class="file">{$suppFile->getFileName()|escape}</a> {$suppFile->getDateModified()|date_format:$dateFormatShort}{elseif $suppFile->getRemoteURL() != ''}<a href="{$suppFile->getRemoteURL()|escape}" target="_blank">{$suppFile->getRemoteURL()|truncate:20:"..."|escape}</a>{/if}</td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="3" class="nodata">{translate key="common.none"}</td>
	</tr>
	{/foreach}
	<tr>
		<td colspan="3" class="separator">&nbsp;</td>
	</tr>
</table>

<div id="layoutComments">
{translate key="submission.layout.layoutComments"}
{if $submission->getMostRecentLayoutComment()}
	{assign var="comment" value=$submission->getMostRecentLayoutComment()}
	<a href="javascript:openComments('{url op="viewLayoutComments" path=$submission->getId() anchor=$comment->getId()}');" class="icon">{icon name="comment"}</a>{$comment->getDatePosted()|date_format:$dateFormatShort}
{else}
	<a href="javascript:openComments('{url op="viewLayoutComments" path=$submission->getId()}');" class="icon">{icon name="comment"}</a>{translate key="common.noComments"}
{/if}
</div>
</div>
