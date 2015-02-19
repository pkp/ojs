{**
 * templates/layoutEditor/submission/layout.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the layout editor's layout editing table.
 *
 *}
{assign var=layoutEditor value=$submission->getUserBySignoffType('SIGNOFF_LAYOUT')}
{assign var=layoutSignoff value=$submission->getSignoff('SIGNOFF_LAYOUT')}
{assign var=layoutFile value=$submission->getFileBySignoffType('SIGNOFF_LAYOUT')}

<div id="layout">
<div id="layoutEditors">
<h3>{translate key="submission.layout"}</h3>

<table class="data" width="100%">
	<tr>
		<td class="label" width="20%">{translate key="user.role.layoutEditor"}</td>
		<td class="value" width="80%">{$layoutEditor->getFullName()|escape}</td>
	</tr>
</table>
</div>

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
				<a href="{url op="downloadFile" path=$submission->getId()|to_array:$layoutFile->getFileId()}" class="file">{$layoutFile->getFileName()|escape}</a> {$layoutFile->getDateModified()|date_format:$dateFormatShort}
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
		<td>
			{if !$layoutSignoff->getDateNotified() or $layoutSignoff->getDateCompleted()}
				{icon name="mail" disabled="disabled"}
			{else}
				{url|assign:"url" op="completeAssignment" articleId=$submission->getId()}
				{translate|assign:"confirmMessage" key="common.confirmComplete"}
				{icon name="mail" onclick="return confirm('$confirmMessage')" url=$url}
			{/if}
						{$layoutSignoff->getDateCompleted()|date_format:$dateFormatShort|default:""}
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
		<td width="23%">{$galley->getGalleyLabel()|escape} &nbsp; <a href="{url op="proofGalley" path=$submission->getId()|to_array:$galley->getId()}" class="action">{translate key="submission.layout.viewProof"}</td>
		<td colspan="2"><a href="{url op="downloadFile" path=$submission->getId()|to_array:$galley->getFileId()}" class="file">{$galley->getFileName()|escape}</a> {$galley->getDateModified()|date_format:$dateFormatShort}</td>
		<td>
			{if $disableEdit}
				&mdash;
			{else}
			<a href="{url op="orderGalley" d=u articleId=$submission->getId() galleyId=$galley->getId()}" class="plain">&uarr;</a> <a href="{url op="orderGalley" d=d articleId=$submission->getId() galleyId=$galley->getId()}" class="plain">&darr;</a>
			{/if}
		</td>
		<td>
			{if $disableEdit}
				&mdash;
			{else}
			<a href="{url op="editGalley" path=$submission->getId()|to_array:$galley->getId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="deleteGalley" path=$submission->getId()|to_array:$galley->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="submission.layout.confirmDeleteGalley"}')" class="action">{translate key="common.delete"}</a>
			{/if}
		</td>
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
		<td width="28%" colspan="2">{translate key="submission.supplementaryFiles"}</td>
		<td width="36%" colspan="2" class="heading">{translate key="common.file"}</td>
		<td width="18%" class="heading">{translate key="common.order"}</td>
		<td width="18%" class="heading">{translate key="common.action"}</td>
	</tr>
	{foreach name=suppFiles from=$submission->getSuppFiles() item=suppFile}
	<tr>
		<td width="5%">{$smarty.foreach.suppFiles.iteration}.</td>
		<td width="23%">{$suppFile->getSuppFileTitle()|escape}</td>
		<td colspan="2"><a href="{url op="downloadFile" path=$submission->getId()|to_array:$suppFile->getFileId()}" class="file">{$suppFile->getFileName()|escape}</a> {$suppFile->getDateModified()|date_format:$dateFormatShort}</td>
		<td>
			{if $disableEdit}
				&mdash;
			{else}
			<a href="{url op="orderSuppFile" d=u articleId=$submission->getId() suppFileId=$suppFile->getId()}" class="plain">&uarr;</a> <a href="{url op="orderSuppFile" d=d articleId=$submission->getId() suppFileId=$suppFile->getId()}" class="plain">&darr;</a>
			{/if}
		</td>
		<td>
			{if $disableEdit}
				&mdash;
			{else}
			<a href="{url op="editSuppFile" path=$submission->getId()|to_array:$suppFile->getId()}" class="action">{translate key="common.edit"}</a>
			<a href="{url op="deleteSuppFile" path=$submission->getId()|to_array:$suppFile->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="submission.layout.confirmDeleteSupplementaryFile"}')" class="action">{translate key="common.delete"}</a>
			{/if}
		</td>
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

<form method="post" action="{url op="uploadLayoutFile"}"  enctype="multipart/form-data">
	<input type="hidden" name="articleId" value="{$submission->getId()}" />
	{translate key="submission.uploadFileTo"} <input type="radio" {if $disableEdit}disabled="disabled" {/if}name="layoutFileType" id="layoutFileTypeSubmission" value="submission" checked="checked" /><label for="layoutFileTypeSubmission">{translate key="submission.layout.layoutVersion"}</label>, <input type="radio" {if $disableEdit}disabled="disabled" {/if}name="layoutFileType" id="layoutFileTypeGalley" value="galley" /><label for="layoutFileTypeGalley">{translate key="submission.galley"}</label>, <input type="radio" {if $disableEdit}disabled="disabled" {/if}name="layoutFileType" id="layoutFileTypeSupp" value="supp" /><label for="layoutFileTypeSupp">{translate key="article.suppFilesAbbrev"}</label>
	<input type="file" name="layoutFile" size="10" class="uploadField" />
	<input type="submit" {if $disableEdit}disabled="disabled" {/if}value="{translate key="common.upload"}" class="button" />
</form>
<div id="layoutComments">
{translate key="submission.layout.layoutComments"}
{if $submission->getMostRecentLayoutComment()}
	{assign var="comment" value=$submission->getMostRecentLayoutComment()}
	<a href="javascript:openComments('{url op="viewLayoutComments" path=$submission->getId() anchor=$comment->getId()}');" class="icon">{icon name="comment"}</a>{$comment->getDatePosted()|date_format:$dateFormatShort}
{else}
	<a href="javascript:openComments('{url op="viewLayoutComments" path=$submission->getId()}');" class="icon">{icon name="comment"}</a>{translate key="common.noComments"}
{/if}

{if $currentJournal->getLocalizedSetting('layoutInstructions')}
&nbsp;&nbsp;
<a href="javascript:openHelp('{url op="instructions" path="layout"}')" class="action">{translate key="submission.layout.instructions"}</a>
{/if}
{if $currentJournal->getSetting('provideRefLinkInstructions')}
&nbsp;&nbsp;
<a href="javascript:openHelp('{url op="instructions" path="referenceLinking"}')" class="action">{translate key="submission.layout.referenceLinking"}</a>
{/if}
{foreach name=templates from=$templates key=templateId item=template}
&nbsp;&nbsp;&nbsp;&nbsp;<a href="{url op="downloadLayoutTemplate" path=$templateId}" class="action">{$template.title|escape}</a>
{/foreach}
</div>
</div>

