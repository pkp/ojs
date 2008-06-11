{**
 * galleyView.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Read-only view of galley information.
 *
 * $Id$
 *}
{assign var="pageTitle" value="submission.galley"}
{include file="common/header.tpl"}

<p>{translate key="submission.layout.galleyFileData"}</p>

<table class="data">
<tr valign="top">
	<td width="20%" class="label">{translate key="submission.layout.galleyLabel"}</td>
	<td width="80%" class="value">{$galley->getGalleyLabel()|escape}</td>
</tr>

{if $galley->getPublicGalleyId()}
	<tr valign="top">
		<td class="label">{translate key="submission.layout.publicGalleyId"}</td>
		<td class="value">{$galley->getPublicGalleyId()|escape}</td>
	</tr>
{/if}

<tr valign="top">
	<td class="label">{translate key="common.fileName"}</td>
	<td class="value"><a class="action" href="{url op="downloadFile" path=$articleId|to_array:$galley->getFileId()}">{$galley->getFileName()|escape}</a></td>
</tr>
<tr valign="top">
	<td class="label">{translate key="common.fileType"}</td>
	<td class="value">{$galley->getFileType()|escape}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="common.fileSize"}</td>
	<td class="value">{$galley->getNiceFileSize()}</td>
</tr>
</table>

{if $galley->isHTMLGalley()}

<h3>{translate key="submission.layout.galleyHTMLData"}</h3>
{assign var=styleFile value=$galley->getStyleFile()}

<table class="data" width="100%">
<tr valign="top">
	<td colspan="2" class="label"><strong>{translate key="submission.layout.galleyStylesheet"}</strong></td>
</tr>
{if $styleFile}
	<tr valign="top>
		<td class="label">{translate key="common.fileName"}</td>
		<td class="value"><a href="{url op="downloadFile" path=$articleId|to_array:$styleFile->getFileId()}" class="action">{$styleFile->getFileName()|escape}</a></td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.fileSize"}</td>
		<td class="value">{$styleFile->getNiceFileSize()}</td>
	</tr>
	<tr>
		<td class="label">{translate key="common.dateUploaded"}</td>
		<td class="value">{$styleFile->getDateUploaded()|date_format:$datetimeFormatShort}</td>
	</tr>
{else}
<tr valign="top">
	<td colspan="2" class="nodata">{translate key="submission.layout.noStyleFile"}</td>
</tr>
{/if}
</table>

<strong>{translate key="submission.layout.galleyImages"}</strong>

<table class="listing" width="100%">
<tr><td colspan="4" class="headseparator">&nbsp;</td></tr>
<tr class="heading" valign="bottom">
	<td width="40%">{translate key="common.fileName"}</td>
	<td width="20%">{translate key="common.originalFileName"}</td>
	<td width="20%">{translate key="common.fileSize"}</td>
	<td width="20%">{translate key="common.dateUploaded"}</td>
</tr>
<tr><td colspan="4" class="headseparator">&nbsp;</td></tr>
{foreach name="images" from=$galley->getImageFiles() item=imageFile}
<tr valign="top">
	<td><a class="action" href="{url op="downloadFile" path=$articleId|to_array:$imageFile->getFileId()}">{$imageFile->getFileName()|escape}</a></td>
	<td>{$imageFile->getOriginalFileName()|escape}</td>
	<td>{$imageFile->getNiceFileSize()}</td>
	<td>{$imageFile->getDateUploaded()|date_format:$dateFormatShort}</td>
</tr>
<tr valign="top">
	<td colspan="4" class="{if $smarty.foreach.submissions.last}end{/if}separator">&nbsp;</td>
</tr>
{foreachelse}
<tr valign="top">
	<td colspan="4" class="nodata">{translate key="submission.layout.galleyNoImages"}</td>
</tr>
<tr valign="top">
	<td colspan="4" class="endseparator">&nbsp;</td>
</tr>
{/foreach}
</table>
{/if}

</form>

{include file="common/footer.tpl"}
