{**
 * templates/author/submission/layout.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the layout editing table.
 *
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
	{if $useLayoutEditors}
	<tr>
		<td width="40%" colspan="2">{translate key="submission.layout.layoutVersion"}</td>
		<td width="15%" class="heading">{translate key="submission.request"}</td>
		<td width="15%" class="heading">{translate key="submission.underway"}</td>
		<td width="15%" class="heading">{translate key="submission.complete"}</td>
		<td class="heading">{translate key="submission.views"}</td>
	</tr>
	<tr>
		<td colspan="2">
			{if $layoutFile}
				<a href="{url op="downloadFile" path=$submission->getId()|to_array:$layoutFile->getFileId()}" class="file">{$layoutFile->getFileName()|escape}</a>&nbsp;&nbsp;{$layoutFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
		</td>
		<td>
			{$layoutSignoff->getDateNotified()|date_format:$dateFormatShort|default:"&mdash;"}
		</td>
		<td>
			{$layoutSignoff->getDateUnderway()|date_format:$dateFormatShort|default:"&mdash;"}
		</td>
		<td colspan="2">
			{$layoutSignoff->getDateCompleted()|date_format:$dateFormatShort|default:"&mdash;"}
		</td>
	</tr>
	<tr>
		<td colspan="6" class="separator">&nbsp;</td>
	</tr>
	{/if}
	<tr>
		<td width="40%" colspan="2">{translate key="submission.layout.galleyFormat"}</td>
		<td width="40%" colspan="2" class="heading">{translate key="common.file"}</td>
		<td colspan="2">&nbsp;</td>
	</tr>
	{foreach name=galleys from=$submission->getGalleys() item=galley}
	<tr>
		<td width="5%">{$smarty.foreach.galleys.iteration}.</td>
		<td width="35%">{$galley->getGalleyLabel()|escape} &nbsp; <a href="{url op="proofGalley" path=$submission->getId()|to_array:$galley->getId()}" class="action">{translate key="submission.layout.viewProof"}</td>
		<td colspan="3"><a href="{url op="downloadFile" path=$submission->getId()|to_array:$galley->getFileId()}" class="file">{$galley->getFileName()|escape}</a>&nbsp;&nbsp;{$galley->getDateModified()|date_format:$dateFormatShort}</td>
		<td>{$galley->getViews()}</td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="6" class="nodata">{translate key="common.none"}</td>
	</tr>
	{/foreach}
	<tr>
		<td colspan="6" class="separator">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="2">{translate key="submission.supplementaryFiles"}</td>
		<td colspan="4" class="heading">{translate key="common.file"}</td>
	</tr>
	{foreach name=suppFiles from=$submission->getSuppFiles() item=suppFile}
	<tr>
		<td width="5%">{$smarty.foreach.suppFiles.iteration}.</td>
		<td width="35%">{$suppFile->getSuppFileTitle()|escape}</td>
		<td colspan="4"><a href="{url op="downloadFile" path=$submission->getId()|to_array:$suppFile->getFileId()}" class="file">{$suppFile->getFileName()|escape}</a>&nbsp;&nbsp;{$suppFile->getDateModified()|date_format:$dateFormatShort}</td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="6" class="nodata">{translate key="common.none"}</td>
	</tr>
	{/foreach}
	<tr>
		<td colspan="6" class="separator">&nbsp;</td>
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
