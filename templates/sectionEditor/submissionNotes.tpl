{**
 * submissionNotes.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show a list of submission notes.
 *
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.notes"}
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

<ul id="tabnav">
	<li><a href="{$requestPageUrl}/summary/{$articleId}">{translate key="submission.summary"}</a></li>
	<li><a href="{$requestPageUrl}/submission/{$articleId}">{translate key="submission.submission"}</a></li>
	<li><a href="{$requestPageUrl}/submissionReview/{$articleId}">{translate key="submission.submissionReview"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEditing/{$articleId}">{translate key="submission.submissionEditing"}</a></li>
	<li><a href="{$requestPageUrl}/submissionHistory/{$articleId}" class="active">{translate key="submission.submissionHistory"}</a></li>
</ul>
<ul id="subnav">
	<li><a href="{$requestPageUrl}/submissionEventLog/{$articleId}">{translate key="submission.history.submissionEventLog"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEmailLog/{$articleId}">{translate key="submission.history.submissionEmailLog"}</a></li>
	<li><a href="{$requestPageUrl}/submissionNotes/{$articleId}" class="active">{translate key="submission.history.submissionNotes"}</a></li>
</ul>

{if $noteViewType == "edit"}
	<form name="editNote" method="post" action="{$requestPageUrl}/updateSubmissionNote" enctype="multipart/form-data">
	<input type="hidden" name="articleId" value="{$articleNote->getArticleId()}" />
	<input type="hidden" name="noteId" value="{$articleNote->getNoteId()}" />
	<input type="hidden" name="fileId" value="{$articleNote->getFileId()}" />
	<div class="formSection">
	<table width="100%" class="form">
	<tr class="heading"><td colspan="2">{translate key="submission.notes.editNote"}</td></tr>
	<tr><td>&nbsp;</td></tr>
	<tr>
		<td class="formLabel">{translate key="common.dateModified"}:</td>
		<td class="formField">{$articleNote->getDateModified()}</td>
	</tr>
	<tr>
		<td class="formLabel">{translate key="common.title"}:</td>
		<td class="formField"><input type="text" name="title" value="{$articleNote->getTitle()}" size="50" maxlength="120" class="textField" /></td>
	</tr>
	<tr>
		<td class="formLabel">{translate key="common.note"}:</td>
		<td class="formField"><textarea name="note" rows="10" cols="50" class="textArea">{$articleNote->getNote()}</textarea></td>
	</tr>
	<tr>
		<td class="formLabel">{translate key="common.file"}:</td>
		<td class="formField"><input type="file" name="upload" class="textField" /></td>
	</tr>
	<tr>
		<td class="formLabel">{translate key="common.uploadedFile"}:</td>
		<td class="formField">{if $articleNote->getFileId()}<a href="{$requestPageUrl}/downloadFile/{$articleId}/{$articleNote->getFileId()}">{$articleNote->getOriginalFileName()}</a><br /><input type="checkbox" name="removeUploadedFile" value="1" />&nbsp;{translate key="submission.notes.removeUploadedFile"}{else}&mdash;{/if}</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td class="formField"><input type="button" value="{translate key="submission.notes.deleteNote"}" onclick="confirmAction('{$requestPageUrl}/removeSubmissionNote?articleId={$articleNote->getArticleId()}&amp;noteId={$articleNote->getNoteId()}&amp;fileId={$articleNote->getFileId()}', '{translate|escape:"javascript" key="submission.notes.confirmDelete"}')">&nbsp;<input type="submit" value="{translate key="submission.notes.updateNote"}" /></td>
	</tr>
	</table>
	</div>
	</form>
{elseif $noteViewType == "add"}
	<form name="addNote" method="post" action="{$requestPageUrl}/addSubmissionNote" enctype="multipart/form-data">
	<input type="hidden" name="articleId" value="{$articleId}" />
	<div class="formSection">
	<table width="100%" class="form">
	<tr class="heading"><td colspan="2">{translate key="submission.notes.addNewNote"}</td></tr>
	<tr><td>&nbsp;</td></tr>
	<tr>
		<td class="formLabel">{translate key="common.title"}:</td>
		<td class="formField"><input type="text" name="title" size="50" maxlength="120" class="textField" /></td>
	</tr>
	<tr>
		<td class="formLabel">{translate key="common.note"}:</td>
		<td class="formField"><textarea name="note" rows="10" cols="50" class="textArea"></textarea></td>
	</tr>
	<tr>
		<td class="formLabel">{translate key="common.file"}:</td>
		<td class="formField"><input type="file" name="upload" class="textField" /></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td class="formField"><input type="submit" value="{translate key="submission.notes.createNewNote"}" /></td>
	</tr>
	</table>
	</div>
	</form>
{else}
	<div class="tableContainer">
	<table width="100%">
	<tr class="heading">
		<td>{translate key="submission.notes"}</td>
	</tr>
	<tr class="subHeading">
		<td class="submissionBox">
			<table class="plainFormat" width="100%">
				<tr valign="top">
					<td width="12%">{translate key="common.date"}</td>
					<td width="60%">{translate key="common.title"}</td>
					<td width="18%">{translate key="submission.notes.attachedFile"}</td>
					<td width="10%" align="right">{translate key="common.action"}</td>
				</tr>
			</table>
		</td>
	</tr>
	{foreach from=$submissionNotes item=note}
	<tr class="{cycle values="logRow,logRowAlt"}">
		<td class="submissionBox">
			<table class="plainFormat" width="100%">
				<tr valign="top">
					<td width="12%" valign="top">{$note->getDateCreated()}</td>
					<td width="60%" valign="top"><a href="javascript:toggleNote({$note->getNoteId()})" class="tableAction">{$note->getTitle()}</a><div class="note" id="{$note->getNoteId()}" name="{$note->getNoteId()}">{$note->getNote()|nl2br}</div></td>
					<td width="18%" valign="top">{if $note->getFileId()}<a href="{$requestPageUrl}/downloadFile/{$articleId}/{$note->getFileId()}" class="file">{$note->getOriginalFileName()}</a>{else}&mdash;{/if}</td>
					<td width="10%" valign="top" align="right">{icon name="view" url="$requestPageUrl/submissionNotes/`$articleId`/edit/`$note->getNoteId()`"}<a href="{$requestPageUrl}/removeSubmissionNote?articleId={$articleId}&amp;noteId={$note->getNoteId()}&amp;fileId={$note->getFileId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.notes.confirmDelete"}')" class="icon">{icon name="delete"}</a></td>
				</tr>
			</table>
		</td>
	</tr>
	{foreachelse}
	<tr class="submissionRow">
		<td class="submissionBox" align="center"><span class="boldText">{translate key="submission.notes.noSubmissionNotes"}</span></td>
	</tr>
	{/foreach}
	<tr class="subHeading">
		<td class="submissionBox">
			<a href="javascript:toggleNoteAll()"><div id="expandNotes" class="showInline">{translate key="submission.notes.expandNotes"}</div><div id="collapseNotes" class="hideInline">{translate key="submission.notes.collapseNotes"}</div></a> | <a href="{$requestPageUrl}/submissionNotes/{$articleId}/add" class="{if $noteViewType == "add"}active{/if}">{translate key="submission.notes.addNewNote"}</a> | <a href="{$requestPageUrl}/clearAllSubmissionNotes?articleId={$articleId}" onclick="return confirm('{translate|escape:"javascript" key="submission.notes.confirmDeleteAll"}')">{translate key="submission.notes.clearAllNotes"}</a>
		</td>
	</tr>
	</table>
	</div>
{/if}

{if $showBackLink}
<br />&#187; <a href="{$requestPageUrl}/submissionNotes/{$articleId}">{translate key="submission.notes.backToSubmissionNotes"}</a>
{/if}

{include file="common/footer.tpl"}
