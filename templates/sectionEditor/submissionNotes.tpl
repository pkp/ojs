{**
 * submissionNotes.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show submission notes page.
 *
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.notes"}
{assign var="pageCrumbTitle" value="submission.notes.breadcrumb"}
{include file="common/header.tpl"}

{literal}
<script type="text/javascript">
{/literal}
	var toggleAll = 0;
	var noteArray = new Array();
	{foreach from=$submissionNotes item=note}
	noteArray.push({$note->getNoteId()});
	{/foreach}
{literal}
	function toggleNote(divNoteId) {
		var domStyle = getBrowserObject(divNoteId,1);
		domStyle.display = (domStyle.display == "block") ? "none" : "block";
	}

	function toggleNoteAll() {
		for(var i = 0; i < noteArray.length; i++) {
			var domStyle = getBrowserObject(noteArray[i],1);
			domStyle.display = toggleAll ? "none" : "block";
		}
		toggleAll = toggleAll ? 0 : 1;

		var collapse = getBrowserObject("collapseNotes",1);
		var expand = getBrowserObject("expandNotes",1);
		if (collapse.display == "inline") {
			collapse.display = "none";
			expand.display = "inline";
		} else {
			collapse.display = "inline";
			expand.display = "none";
		}
	}
</script>
{/literal}

<ul class="menu">
	<li><a href="{$requestPageUrl}/submission/{$submission->getArticleId()}">{translate key="submission.summary"}</a></li>
	<li><a href="{$requestPageUrl}/submissionReview/{$submission->getArticleId()}">{translate key="submission.review"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEditing/{$submission->getArticleId()}">{translate key="submission.editing"}</a></li>
	<li><a href="{$requestPageUrl}/submissionHistory/{$submission->getArticleId()}">{translate key="submission.history"}</a></li>
</ul>

<ul class="menu">
	<li><a href="{$requestPageUrl}/submissionEventLog/{$submission->getArticleId()}">{translate key="submission.history.submissionEventLog"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEmailLog/{$submission->getArticleId()}">{translate key="submission.history.submissionEmailLog"}</a></li>
	<li class="current"><a href="{$requestPageUrl}/submissionNotes/{$submission->getArticleId()}">{translate key="submission.history.submissionNotes"}</a></li>
</ul>

{include file="sectionEditor/submission/summary.tpl"}

<div class="separator"></div>


{if $noteViewType == "edit"}
<h3>{translate key="submission.notes"}</h3>
<form name="editNote" method="post" action="{$requestPageUrl}/updateSubmissionNote" enctype="multipart/form-data">
	<input type="hidden" name="articleId" value="{$articleNote->getArticleId()}" />
	<input type="hidden" name="noteId" value="{$articleNote->getNoteId()}" />
	<input type="hidden" name="fileId" value="{$articleNote->getFileId()}" />

<table width="100%" class="data">
	<tr valign="top">
		<td class="label" width="20%">{translate key="common.dateModified"}</td>
		<td class="value" width="80%">{$articleNote->getDateModified()|date_format:$dateFormatShort}</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">{translate key="common.title"}</td>
		<td class="value" width="80%"><input type="text" name="title" id="title" value="{$articleNote->getTitle()}" size="50" maxlength="120" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">{translate key="common.note"}</td>
		<td class="value" width="80%"><textarea name="note" id="note" rows="10" cols="50" class="textArea">{$articleNote->getNote()}</textarea></td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">{translate key="common.file"}</td>
		<td class="value" width="80%"><input type="file" id="upload" name="upload" class="uploadField" /></td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">{translate key="common.uploadedFile"}</td>
		<td class="value" width="80%">{if $articleNote->getFileId()}<a href="{$requestPageUrl}/downloadFile/{$articleId}/{$articleNote->getFileId()}">{$articleNote->getOriginalFileName()}</a><br /><input type="checkbox" name="removeUploadedFile" value="1" />&nbsp;{translate key="submission.notes.removeUploadedFile"}{else}&mdash;{/if}</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">&nbsp;</td>
		<td class="value" width="80%"><input type="button" class="button" value="{translate key="submission.notes.deleteNote"}" onclick="confirmAction('{$requestPageUrl}/removeSubmissionNote?articleId={$articleNote->getArticleId()}&amp;noteId={$articleNote->getNoteId()}&amp;fileId={$articleNote->getFileId()}', '{translate|escape:"javascript" key="submission.notes.confirmDelete"}')">&nbsp;<input type="submit" class="button" value="{translate key="submission.notes.updateNote"}" /></td>
	</tr>
</table>

{elseif $noteViewType == "add"}
	<h3>{translate key="submission.notes.addNewNote"}</h3>
	<form name="addNote" method="post" action="{$requestPageUrl}/addSubmissionNote" enctype="multipart/form-data">
	<input type="hidden" name="articleId" value="{$articleId}" />
	<table width="100%" class="data">
	<tr valign="top">
		<td class="label" width="20%">{translate key="common.title"}</td>
		<td class="value" width="80%"><input type="text" id="title" name="title" size="50" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.note"}</td>
		<td class="value"><textarea name="note" id="note" rows="10" cols="50" class="textArea"></textarea></td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.file"}</td>
		<td class="value"><input type="file" name="upload" class="uploadField" /></td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="formField"><input type="submit" class="button defaultButton" value="{translate key="submission.notes.createNewNote"}" /></td>
	</tr>
	</table>
	</form>
{else}
<h3>{translate key="submission.notes"}</h3>

<table width="100%" class="listing">
	<tr><td colspan="6" class="headseparator">&nbsp;</td></tr>
	<tr class="heading">
		<td width="5%">{translate key="common.date"}</td>
		<td width="60%">{translate key="common.title"}</td>
		<td width="25%">{translate key="submission.notes.attachedFile"}</td>
		<td width="10%">{translate key="common.action"}</td>
	</tr>
	<tr><td colspan="6" class="headseparator">&nbsp;</td></tr>
{foreach name=submissionnotes from=$submissionNotes item=note}
	<tr valign="top">
		<td>{$note->getDateCreated()|date_format:$dateFormatTrunc}</td>
		<td><a class="action" href="javascript:toggleNote({$note->getNoteId()})">{$note->getTitle()}</a><div class="note" id="{$note->getNoteId()}" name="{$note->getNoteId()}">{$note->getNote()|nl2br}</div></td>
		<td>{if $note->getFileId()}<a class="action" href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$note->getFileId()}">{$note->getOriginalFileName()}</a>{else}&mdash;{/if}</td>
		<td><a href="{$requestPageUrl}/submissionNotes/{$submission->getArticleId()}/edit/{$note->getNoteId()}" class="action">{translate key="common.view"}</a>&nbsp;<a href="{$requestPageUrl}/removeSubmissionNote?articleId={$submission->getArticleId()}&amp;noteId={$note->getNoteId()}&amp;fileId={$note->getFileId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.notes.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
	</tr>
	<tr valign="top">
		<td colspan="6" class="{if $smarty.foreach.submissionnotes.last}end{/if}separator">&nbsp;</td>
	</tr>
{foreachelse}
	<tr valign="top">
		<td colspan="6" class="nodata">{translate key="submission.notes.noSubmissionNotes"}</td>
	</tr>
	<tr valign="top">
		<td colspan="6" class="{if $smarty.foreach.submissionnotes.last}end{/if}separator">&nbsp;</td>
	</tr>
{/foreach}
	<tr valign="top">
		<td colspan="6">
			<a class="action" href="javascript:toggleNoteAll()">{translate key="submission.notes.expandNotes"} / {translate key="submission.notes.collapseNotes"}</a> | <a class="action" href="{$requestPageUrl}/submissionNotes/{$submission->getArticleId()}/add">{translate key="submission.notes.addNewNote"}</a> | <a class="action" href="{$requestPageUrl}/clearAllSubmissionNotes?articleId={$submission->getArticleId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.notes.confirmDeleteAll"}')">{translate key="submission.notes.clearAllNotes"}</a>
		</td>
	</tr>
</table>
{/if}

{include file="common/footer.tpl"}
