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

{assign_translate var="pageTitleTranslated" key="submission.page.history" id=$submission->getArticleId()}
{assign var="pageCrumbTitle" value="submission.history"}
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

<ul class="menu">
	<li><a href="{$requestPageUrl}/submission/{$submission->getArticleId()}">{translate key="submission.summary"}</a></li>
	<li><a href="{$requestPageUrl}/submissionReview/{$submission->getArticleId()}">{translate key="submission.review"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEditing/{$submission->getArticleId()}">{translate key="submission.editing"}</a></li>
	<li class="current"><a href="{$requestPageUrl}/submissionHistory/{$submission->getArticleId()}">{translate key="submission.history"}</a></li>
</ul>

<ul class="menu">
	<li><a href="{$requestPageUrl}/submissionEventLog/{$submission->getArticleId()}">{translate key="submission.history.submissionEventLog"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEmailLog/{$submission->getArticleId()}">{translate key="submission.history.submissionEmailLog"}</a></li>
	<li><a href="{$requestPageUrl}/submissionNotes/{$submission->getArticleId()}">{translate key="submission.history.submissionNotes"}</a></li>
</ul>

{include file="sectionEditor/submission/summary.tpl"}

<div class="separator"></div>

<h3>{translate key="submission.history.submissionEventLog"} - {translate key="submission.history.recentLogEntries"}</h3>
<table width="100%" class="listing">
	<tr><td class="headseparator" colspan="6"></td></tr>
	<tr valign="top" class="heading">
		<td width="5%">{translate key="common.date"}</td>
		<td width="5%">{translate key="submission.event.logLevel"}</td>
		<td width="5%">{translate key="common.type"}</td>
		<td width="25%">{translate key="common.user"}</td>
		<td>{translate key="common.event"}</td>
		<td width="56">{translate key="common.action"}</td>
	</tr>
	<tr><td class="headseparator" colspan="6"></td></tr>
{foreach name=eventlogentries from=$eventLogEntries item=logEntry}
	<tr valign="top">
		<td>{$logEntry->getDateLogged()|date_format:$dateFormatTrunc}</td>
		<td>{$logEntry->getLogLevel()}</td>
		<td>{$logEntry->getAssocTypeString()}</td>
		<td>{$logEntry->getUserFullName()} {icon name="mail" url="mailto:`$logEntry->getUserEmail()`"}</td>
		<td>
			<strong>{translate key=$logEntry->getEventTitle()}</strong>
			<br />
			{$logEntry->getMessage()|truncate:60:"..."}
		</td>
		<td>{if $logEntry->getAssocType()}{icon name="letter" url="$requestPageUrl/submissionEventLogType/`$submission->getArticleId()`/`$logEntry->getAssocType()`/`$logEntry->getAssocId()`"}&nbsp;{/if}{icon name="view" url="$requestPageUrl/submissionEventLog/`$submission->getArticleId()`/`$logEntry->getLogId()`"}{if $isEditor}&nbsp;<a href="{$requestPageUrl}/clearSubmissionEventLog/{$submission->getArticleId()}/{$logEntry->getLogId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.event.confirmDeleteLogEntry"}')" class="icon">{icon name="delete"}</a>{/if}</td>
	</tr>
	<tr valign="top">
		<td colspan="6" class="{if $smarty.foreach.eventlogentries.last}end{/if}separator"></td>
	</tr>
{foreachelse}
	<tr valign="top">
		<td colspan="6" class="nodata">{translate key="submission.history.noLogEntries"}</td>
	</tr>
	<tr valign="top">
		<td colspan="6" class="{if $smarty.foreach.eventlogentries.last}end{/if}separator"></td>
	</tr>
{/foreach}
	<tr valign="top">
		<td colspan="6">
			<a href="{$requestPageUrl}/submissionEventLog/{$submission->getArticleId()}" class="action">{translate key="submission.history.viewLog"}</a>{if $isEditor} | <a href="{$requestPageUrl}/clearSubmissionEventLog/{$submission->getArticleId()}" class="action" onclick="return confirm('{translate|escape:"javascript" key="submission.event.confirmClearLog"}')">{translate key="submission.history.clearLog"}</a>{/if}
		</td>
	</tr>
</table>

<div class="separator"></div>
<h3>{translate key="submission.history.submissionEmailLog"} - {translate key="submission.history.recentLogEntries"}</h3>

<table width="100%" class="listing">
	<tr><td class="headseparator" colspan="6"></td></tr>
	<tr valign="top" class="heading">
		<td width="5%">{translate key="common.date"}</td>
		<td width="5%">{translate key="common.type"}</td>
		<td width="25%">{translate key="email.sender"}</td>
		<td width="20%">{translate key="email.recipients"}</td>
		<td>{translate key="common.subject"}</td>
		<td width="60" align="right">{translate key="common.action"}</td>
	</tr>
	<tr><td class="headseparator" colspan="6"></td></tr>
{foreach name=emaillogentries from=$emailLogEntries item=logEntry}
	<tr valign="top">
		<td>{$logEntry->getDateSent()|date_format:$dateFormatTrunc}</td>
		<td>{$logEntry->getAssocTypeString()}</td>
		<td>{$logEntry->getFrom()|truncate:40:"..."|escape}</td>
		<td>{$logEntry->getRecipients()|truncate:40:"..."|escape}</td>
		<td><strong>{$logEntry->getSubject()|truncate:60:"..."}</strong></td>
		<td>{if $logEntry->getAssocType()}{icon name="letter" url="$requestPageUrl/submissionEmailLogType/`$submission->getArticleId()`/`$logEntry->getAssocType()`/`$logEntry->getAssocId()`"}&nbsp;{/if}{icon name="view" url="$requestPageUrl/submissionEmailLog/`$submission->getArticleId()`/`$logEntry->getLogId()`"}</a>{if $isEditor}&nbsp;<a href="{$requestPageUrl}/clearSubmissionEmailLog/{$submission->getArticleId()}/{$logEntry->getLogId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.email.confirmDeleteLogEntry"}')" class="icon">{icon name="delete"}</a>{/if}</td>
	</tr>
	<tr valign="top">
		<td colspan="6" class="{if $smarty.foreach.emaillogentries.last}end{/if}separator"></td>
	</tr>
{foreachelse}
	<tr valign="top">
		<td colspan="6" class="nodata">{translate key="submission.history.noLogEntries"}</td>
	</tr>
	<tr valign="top">
		<td colspan="6" class="{if $smarty.foreach.emaillogentries.last}end{/if}separator"></td>
	</tr>
{/foreach}
	<tr valign="top">
		<td colspan="6">
			<a class="action" href="{$requestPageUrl}/submissionEmailLog/{$submission->getArticleId()}">{translate key="submission.history.viewLog"}</a>{if $isEditor} | <a class="action" href="{$requestPageUrl}/clearSubmissionEmailLog/{$submission->getArticleId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.email.confirmClearLog"}')">{translate key="submission.history.clearLog"}</a>{/if}
		</td>
	</tr>
</table>

<div class="separator"></div>

<h3>{translate key="submission.notes"}</h3>

<table width="100%" class="listing">
	<tr><td colspan="6" class="headseparator"></td></tr>
	<tr class="heading">
		<td width="5%">{translate key="common.date"}</td>
		<td width="60%">{translate key="common.title"}</td>
		<td width="25%">{translate key="submission.notes.attachedFile"}</td>
		<td width="10%">{translate key="common.action"}</td>
	</tr>
	<tr><td colspan="6" class="headseparator"></td></tr>
{foreach name=submissionnotes from=$submissionNotes item=note}
	<tr valign="top">
		<td>{$note->getDateCreated()|date_format:$dateFormatTrunc}</td>
		<td><a class="action" href="javascript:toggleNote({$note->getNoteId()})">{$note->getTitle()}</a><div class="note" id="{$note->getNoteId()}" name="{$note->getNoteId()}">{$note->getNote()|nl2br}</div></td>
		<td>{if $note->getFileId()}<a class="action" href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$note->getFileId()}">{$note->getOriginalFileName()}</a>{else}&mdash;{/if}</td>
		<td>{icon name="view" url="$requestPageUrl/submissionNotes/`$submission->getArticleId()`/edit/`$note->getNoteId()`"}&nbsp;<a href="{$requestPageUrl}/removeSubmissionNote?articleId={$submission->getArticleId()}&amp;noteId={$note->getNoteId()}&amp;fileId={$note->getFileId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.notes.confirmDelete"}')" class="icon">{icon name="delete"}</a></td>
	</tr>
	<tr valign="top">
		<td colspan="6" class="{if $smarty.foreach.submissionnotes.last}end{/if}separator"></td>
	</tr>
{foreachelse}
	<tr valign="top">
		<td colspan="6" class="nodata">{translate key="submission.notes.noSubmissionNotes"}</td>
	</tr>
	<tr valign="top">
		<td colspan="6" class="{if $smarty.foreach.submissionnotes.last}end{/if}separator"></td>
	</tr>
{/foreach}
	<tr valign="top">
		<td colspan="6">
			<a class="action" href="{$requestPageUrl}/submissionNotes/{$submission->getArticleId()}">{translate key="submission.notes.viewNotes"}</a> | <a class="action" href="javascript:toggleNoteAll()">{translate key="submission.notes.expandNotes"} / {translate key="submission.notes.collapseNotes"}</a> | <a class="action" href="{$requestPageUrl}/submissionNotes/{$submission->getArticleId()}/add">{translate key="submission.notes.addNewNote"}</a> | <a class="action" href="{$requestPageUrl}/clearAllSubmissionNotes?articleId={$submission->getArticleId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.notes.confirmDeleteAll"}')">{translate key="submission.notes.clearAllNotes"}</a>
		</td>
	</tr>
</table>

{include file="common/footer.tpl"}
