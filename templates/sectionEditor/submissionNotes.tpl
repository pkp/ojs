{**
 * submissionNotes.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
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
<!--
	var toggleAll = 0;
	var noteArray = new Array();
	function toggleNote(divNoteId) {
		var domStyle = getBrowserObject("note" + divNoteId,1);
		domStyle.display = (domStyle.display == "block") ? "none" : "block";
	}

	function toggleNoteAll() {
		for(var i = 0; i < noteArray.length; i++) {
			var domStyle = getBrowserObject("note" + noteArray[i],1);
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
// -->
</script>
{/literal}

<ul class="menu">
	<li><a href="{url op="submission" path=$submission->getArticleId()}">{translate key="submission.summary"}</a></li>
	{if $canReview}<li><a href="{url op="submissionReview" path=$submission->getArticleId()}">{translate key="submission.review"}</a></li>{/if}
	{if $canEdit}<li><a href="{url op="submissionEditing" path=$submission->getArticleId()}">{translate key="submission.editing"}</a></li>{/if}
	<li><a href="{url op="submissionHistory" path=$submission->getArticleId()}">{translate key="submission.history"}</a></li>
</ul>

<ul class="menu">
	<li><a href="{url op="submissionEventLog" path=$submission->getArticleId()}">{translate key="submission.history.submissionEventLog"}</a></li>
	<li><a href="{url op="submissionEmailLog" path=$submission->getArticleId()}">{translate key="submission.history.submissionEmailLog"}</a></li>
	<li class="current"><a href="{url op="submissionNotes" path=$submission->getArticleId()}">{translate key="submission.history.submissionNotes"}</a></li>
</ul>

{include file="sectionEditor/submission/summary.tpl"}

<div class="separator"></div>

<a name="submissionNotes"></a>

{if $noteViewType == "edit"}
<h3>{translate key="submission.notes"}</h3>
<form name="editNote" method="post" action="{url op="updateSubmissionNote"}" enctype="multipart/form-data">
	<input type="hidden" name="articleId" value="{$articleNote->getArticleId()}" />
	<input type="hidden" name="noteId" value="{$articleNote->getNoteId()}" />
	<input type="hidden" name="fileId" value="{$articleNote->getFileId()}" />

<table width="100%" class="data">
	<tr valign="top">
		<td class="label" width="20%">{translate key="common.dateModified"}</td>
		<td class="value" width="80%">{$articleNote->getDateModified()|date_format:$datetimeFormatShort}</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">{translate key="common.title"}</td>
		<td class="value" width="80%"><input type="text" name="title" id="title" value="{$articleNote->getTitle()}" size="50" maxlength="120" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">{translate key="common.note"}</td>
		<td class="value" width="80%"><textarea name="note" id="note" rows="10" cols="50" class="textArea">{$articleNote->getNote()|strip_unsafe_html|escape}</textarea></td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">{translate key="common.file"}</td>
		<td class="value" width="80%"><input type="file" id="upload" name="upload" class="uploadField" /></td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">{translate key="common.uploadedFile"}</td>
		<td class="value" width="80%">{if $articleNote->getFileId()}<a href="{url op="downloadFile" path=$articleId|to_array:$articleNote->getFileId()}">{$articleNote->getOriginalFileName()}</a><br /><input type="checkbox" name="removeUploadedFile" value="1" />&nbsp;{translate key="submission.notes.removeUploadedFile"}{else}&mdash;{/if}</td>
	</tr>
</table>
<br />
<input type="button" class="button" value="{translate key="submission.notes.deleteNote"}" onclick="confirmAction('{url op="removeSubmissionNote" articleId=$articleNote->getArticleId() noteId=$articleNote->getNoteId() fileId=$articleNote->getFileId()}', '{translate|escape:"jsparam" key="submission.notes.confirmDelete"}')" />&nbsp;<input type="submit" class="button defaultButton" value="{translate key="submission.notes.updateNote"}" />
	</tr>
</form>

{elseif $noteViewType == "add"}
	<h3>{translate key="submission.notes.addNewNote"}</h3>
	<form name="addNote" method="post" action="{url op="addSubmissionNote"}" enctype="multipart/form-data">
	<input type="hidden" name="articleId" value="{$articleId|escape}" />
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
	</table>
	<br/>
	<input type="submit" class="button defaultButton" value="{translate key="submission.notes.createNewNote"}" />
	</form>
{else}
<h3>{translate key="submission.notes"}</h3>

<table width="100%" class="listing">
	<tr><td colspan="6" class="headseparator">&nbsp;</td></tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.date"}</td>
		<td width="60%">{translate key="common.title"}</td>
		<td width="25%">{translate key="submission.notes.attachedFile"}</td>
		<td width="10%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr><td colspan="6" class="headseparator">&nbsp;</td></tr>
{iterate from=submissionNotes item=note}
	<tr valign="top">
		<td>
			<script type="text/javascript">
			<!--
				noteArray.push({$note->getNoteId()});
			// -->
			</script>
			{$note->getDateCreated()|date_format:$dateFormatTrunc}
		</td>
		<td><a class="action" href="javascript:toggleNote({$note->getNoteId()})">{$note->getTitle()}</a><div style="display: none" id="note{$note->getNoteId()}">{$note->getNote()|strip_unsafe_html|nl2br}</div></td>
		<td>{if $note->getFileId()}<a class="action" href="{url op="downloadFile" path=$submission->getArticleId()|to_array:$note->getFileId()}">{$note->getOriginalFileName()}</a>{else}&mdash;{/if}</td>
		<td align="right"><a href="{url op="submissionNotes" path=$submission->getArticleId()|to_array:"edit":$note->getNoteId()}" class="action">{translate key="common.view"}</a>&nbsp;|&nbsp;<a href="{url op="removeSubmissionNote" articleId=$submission->getArticleId() noteId=$note->getNoteId() fileId=$note->getFileId()}" onclick="return confirm('{translate|escape:"jsparam" key="submission.notes.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
	</tr>
	<tr valign="top">
		<td colspan="6" class="{if $submissionNotes->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $submissionNotes->wasEmpty()}
	<tr valign="top">
		<td colspan="6" class="nodata">{translate key="submission.notes.noSubmissionNotes"}</td>
	</tr>
	<tr valign="top">
		<td colspan="6" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="3" align="left">{page_info iterator=$submissionNotes}</td>
		<td colspan="3" align="right">{page_links anchor="submissionNotes" name="submissionNotes" iterator=$submissionNotes}</td>
	</tr>
{/if}
</table>

<a class="action" href="javascript:toggleNoteAll()">{translate key="submission.notes.expandNotes"} / {translate key="submission.notes.collapseNotes"}</a> | <a class="action" href="{url op="submissionNotes" path=$submission->getArticleId()|to_array:"add"}">{translate key="submission.notes.addNewNote"}</a> | <a class="action" href="{url op="clearAllSubmissionNotes" articleId=$submission->getArticleId()}" onclick="return confirm('{translate|escape:"jsparam" key="submission.notes.confirmDeleteAll"}')">{translate key="submission.notes.clearAllNotes"}</a>
{/if}

{include file="common/footer.tpl"}
