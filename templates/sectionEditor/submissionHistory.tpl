{**
 * submissionHistory.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show submission history page.
 *
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.submissionHistory"}
{assign var="pageId" value="sectionEditor.submissionHistory"}
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
	<li><a href="{$requestPageUrl}/summary/{$submission->getArticleId()}">{translate key="submission.summary"}</a></li>
	<li><a href="{$requestPageUrl}/submission/{$submission->getArticleId()}">{translate key="submission.submission"}</a></li>
	<li><a href="{$requestPageUrl}/submissionReview/{$submission->getArticleId()}">{translate key="submission.submissionReview"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEditing/{$submission->getArticleId()}">{translate key="submission.submissionEditing"}</a></li>
	<li><a href="{$requestPageUrl}/submissionHistory/{$submission->getArticleId()}" class="active">{translate key="submission.submissionHistory"}</a></li>
</ul>
<ul id="subnav">
	<li><a href="{$requestPageUrl}/submissionEventLog/{$submission->getArticleId()}">{translate key="submission.history.submissionEventLog"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEmailLog/{$submission->getArticleId()}">{translate key="submission.history.submissionEmailLog"}</a></li>
	<li><a href="{$requestPageUrl}/submissionNotes/{$submission->getArticleId()}">{translate key="submission.history.submissionNotes"}</a></li>
</ul>

<div class="tableContainer">
<table width="100%">
<tr class="submissionRow">
	<td class="submissionBox">
		<div class="leftAligned">
			<div>{foreach from=$submission->getAuthors() item=author key=authorKey}{if $authorKey neq 0},{/if} {$author->getFullName()}{/foreach}</div>
			<div class="submissionTitle">{$submission->getArticleTitle()}</div>
		</div>
		<div class="submissionId">{$submission->getArticleId()}</div>
	</td>
</tr>
</table>
</div>

<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.history.submissionEventLog"} - {translate key="submission.history.recentLogEntries"}</td>
</tr>
<tr class="subHeading">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr valign="top">
				<td width="10%">{translate key="common.date"}</td>
				<td width="5%">{translate key="submission.event.logLevel"}</td>
				<td width="5%">{translate key="common.type"}</td>
				<td width="15%">{translate key="common.user"}</td>
				<td>{translate key="common.event"}</td>
				<td width="56" align="right">{translate key="common.action"}</td>
			</tr>
		</table>
	</td>
</tr>
{foreach from=$eventLogEntries item=logEntry}
<tr class="{if $logEntry->getLogLevel() eq 'W'}{cycle values="logRow,logRowAlt" print=false}logRowWarning{elseif $logEntry->getLogLevel() eq 'E'}{cycle values="logRow,logRowAlt" print=false}logRowError{else}{cycle values="logRow,logRowAlt"}{/if}">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr valign="top">
				<td width="10%">{$logEntry->getDateLogged()}</td>
				<td width="5%">{$logEntry->getLogLevel()}</td>
				<td width="5%">{$logEntry->getAssocTypeString()}</td>
				<td width="15%"><a href="mailto:{$logEntry->getUserEmail()}">{$logEntry->getUserFullName()}</a></td>
				<td>
					<span class="boldText">{translate key=$logEntry->getEventTitle()}</span>
					<br />
					{$logEntry->getMessage()|truncate:60:"..."}
				</td>
				<td width="56" align="right">{if $logEntry->getAssocType()}{icon name="letter" url="$requestPageUrl/submissionEventLogType/`$submission->getArticleId()`/`$logEntry->getAssocType()`/`$logEntry->getAssocId()`"}&nbsp;{/if}{icon name="view" url="$requestPageUrl/submissionEventLog/`$submission->getArticleId()`/`$logEntry->getLogId()`"}{if $isEditor}&nbsp;<a href="{$requestPageUrl}/clearSubmissionEventLog/{$submission->getArticleId()}/{$logEntry->getLogId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.event.confirmDeleteLogEntry"}')" class="icon">{icon name="delete"}</a>{/if}</td>
			</tr>
		</table>
	</td>
</tr>
{foreachelse}
<tr class="submissionRow">
	<td class="submissionBox" align="center"><span class="boldText">{translate key="submission.history.noLogEntries"}</span></td>
</tr>
{/foreach}
<tr class="subHeading">
	<td class="submissionBox">
		<a href="{$requestPageUrl}/submissionEventLog/{$submission->getArticleId()}">{translate key="submission.history.viewLog"}</a>{if $isEditor} | <a href="{$requestPageUrl}/clearSubmissionEventLog/{$submission->getArticleId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.event.confirmClearLog"}')">{translate key="submission.history.clearLog"}</a>{/if}
	</td>
</tr>
</table>
</div>

<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.history.submissionEmailLog"} - {translate key="submission.history.recentLogEntries"}</td>
</tr>
<tr class="subHeading">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr valign="top">
				<td width="10%">{translate key="common.date"}</td>
				<td width="5%">{translate key="common.type"}</td>
				<td width="20%">{translate key="email.sender"}</td>
				<td width="20%">{translate key="email.recipients"}</td>
				<td>{translate key="common.subject"}</td>
				<td width="56" align="right">{translate key="common.action"}</td>
			</tr>
		</table>
	</td>
</tr>
{foreach from=$emailLogEntries item=logEntry}
<tr class="{cycle values="logRow,logRowAlt"}">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr valign="top">
				<td width="10%">{$logEntry->getDateSent()}</td>
				<td width="5%">{$logEntry->getAssocTypeString()}</td>
				<td width="20%">{$logEntry->getFrom()|truncate:40:"..."|escape}</td>
				<td width="20%">{$logEntry->getRecipients()|truncate:40:"..."|escape}</td>
				<td><span class="boldText">{$logEntry->getSubject()|truncate:60:"..."}</span></td>
				<td width="56" align="right">{if $logEntry->getAssocType()}{icon name="letter" url="$requestPageUrl/submissionEmailLogType/`$submission->getArticleId()`/`$logEntry->getAssocType()`/`$logEntry->getAssocId()`"}&nbsp;{/if}{icon name="view" url="$requestPageUrl/submissionEmailLog/`$submission->getArticleId()`/`$logEntry->getLogId()`"}</a>{if $isEditor}&nbsp;<a href="{$requestPageUrl}/clearSubmissionEmailLog/{$submission->getArticleId()}/{$logEntry->getLogId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.email.confirmDeleteLogEntry"}')" class="icon">{icon name="delete"}</a>{/if}</td>
			</tr>
		</table>
	</td>
</tr>
{foreachelse}
<tr class="submissionRow">
	<td class="submissionBox" align="center"><span class="boldText">{translate key="submission.history.noLogEntries"}</span></td>
</tr>
{/foreach}
<tr class="subHeading">
	<td class="submissionBox">
		<a href="{$requestPageUrl}/submissionEmailLog/{$submission->getArticleId()}">{translate key="submission.history.viewLog"}</a>{if $isEditor} | <a href="{$requestPageUrl}/clearSubmissionEmailLog/{$submission->getArticleId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.email.confirmClearLog"}')">{translate key="submission.history.clearLog"}</a>{/if}
	</td>
</tr>
</table>
</div>

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
				<td width="18%" valign="top">{if $note->getFileId()}{assign var="currentFileId" value=$note->getFileId()}<a href="{$pageUrl}/sectionEditor/downloadFile/{$submission->getArticleId()}/{$currentFileId}" class="file">{$submissionNotesFiles[$currentFileId]}</a>{else}&mdash;{/if}</td>
				<td width="10%" valign="top" align="right">{icon name="view" url="$requestPageUrl/submissionNotes/`$submission->getArticleId()`/edit/`$note->getNoteId()`"}&nbsp;<a href="{$pageUrl}/sectionEditor/removeSubmissionNote?articleId={$submission->getArticleId()}&amp;noteId={$note->getNoteId()}&amp;fileId={$note->getFileId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.notes.confirmDelete"}')" class="icon">{icon name="delete"}</a></td>
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
		<a href="{$requestPageUrl}/submissionNotes/{$submission->getArticleId()}">{translate key="submission.notes.viewNotes"}</a> | <a href="javascript:toggleNoteAll()"><div id="expandNotes" class="showInline">{translate key="submission.notes.expandNotes"}</div><div id="collapseNotes" class="hideInline">{translate key="submission.notes.collapseNotes"}</div></a> | <a href="{$pageUrl}/sectionEditor/submissionNotes/{$submission->getArticleId()}/add" class="{if $noteViewType == "add"}active{/if}">{translate key="submission.notes.addNewNote"}</a> | <a href="{$pageUrl}/sectionEditor/clearAllSubmissionNotes?articleId={$submission->getArticleId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.notes.confirmDeleteAll"}')">{translate key="submission.notes.clearAllNotes"}</a>
	</td>
</tr>
</table>
</div>

{include file="common/footer.tpl"}
