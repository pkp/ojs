{**
 * templates/editor/issues/issueGalleys.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for uploading and editing issue galleys
 *}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#issueGalleysForm').pkpHandler('$.pkp.controllers.form.FormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="issueGalleysForm" method="post" action="{url op="uploadIssueGalley" issueId=$issueId}" enctype="multipart/form-data">
{include file="common/formErrors.tpl"}
<div id="issueId">
<p>{translate key="editor.issues.issueGalleysDescription"}</p>

<table class="info">
	<tr>
		<td colspan="6" class="separator">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="2" class="heading">{translate key="submission.layout.galleyFormat"}</td>
		<td class="heading">{translate key="common.file"}</td>
		<td class="heading">{translate key="common.order"}</td>
		<td class="heading">{translate key="common.action"}</td>
		<td class="heading">{translate key="submission.views"}</td>
	</tr>
	{foreach name=galleys from=$issueGalleys item=galley}
	<tr>
		<td width="2%">{$smarty.foreach.galleys.iteration}.</td>
		<td>{$galley->getGalleyLabel()|escape} &nbsp; <a href="{url op="proofIssueGalley" issueId=$issue->getId() galleyId=$galley->getId()}" class="action">{translate key="submission.layout.viewProof"}</a></td>
		<td><a href="{url op="downloadIssueFile" issueId=$issue->getId() fileId=$galley->getFileId()}" class="file">{$galley->getFileName()|escape}</a>&nbsp;&nbsp;{$galley->getDateModified()|date_format:$dateFormatShort}</td>
		<td><a href="{url op="orderIssueGalley" d=u issueId=$issue->getId() galleyId=$galley->getId()}" class="plain">&uarr;</a> <a href="{url op="orderIssueGalley" d=d issueId=$issue->getId() galleyId=$galley->getId()}" class="plain">&darr;</a></td>
		<td>
			<a href="{url op="editIssueGalley" issueId=$issue->getId() galleyId=$galley->getId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="deleteIssueGalley" issueId=$issue->getId() galleyId=$galley->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="editor.issues.confirmDeleteGalley"}')" class="action">{translate key="common.delete"}</a>
		</td>
		<td>{$galley->getViews()|escape}</td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="6" class="nodata">{translate key="editor.issues.noneIssueGalleys"}</td>
	</tr>
	{/foreach}
	<tr>
		<td colspan="6" class="separator">&nbsp;</td>
	</tr>
</table>
	<br />
	<input type="file" name="galleyFile" id="galleyFile" size="10" class="uploadField" />
	<input type="submit" value="{translate key="common.upload"}" class="button" />
</div>
</form>
