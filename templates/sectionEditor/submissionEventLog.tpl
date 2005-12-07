{**
 * submissionEventLog.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show submission event log page.
 *
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.eventLog"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{url op="submission" path=$submission->getArticleId()}">{translate key="submission.summary"}</a></li>
	{if $canReview}<li><a href="{url op="submissionReview" path=$submission->getArticleId()}">{translate key="submission.review"}</a></li>{/if}
	{if $canEdit}<li><a href="{url op="submissionEditing" path=$submission->getArticleId()}">{translate key="submission.editing"}</a></li>{/if}
	<li><a href="{url op="submissionHistory" path=$submission->getArticleId()}">{translate key="submission.history"}</a></li>
</ul>

<ul class="menu">
	<li class="current"><a href="{url op="submissionEventLog" path=$submission->getArticleId()}">{translate key="submission.history.submissionEventLog"}</a></li>
	<li><a href="{url op="submissionEmailLog" path=$submission->getArticleId()}">{translate key="submission.history.submissionEmailLog"}</a></li>
	<li><a href="{url op="submissionNotes" path=$submission->getArticleId()}">{translate key="submission.history.submissionNotes"}</a></li>
</ul>

{include file="sectionEditor/submission/summary.tpl"}

<div class="separator"></div>

<h3>{translate key="submission.history.submissionEventLog"}</h3>
<table width="100%" class="listing">
	<tr><td class="headseparator" colspan="6">&nbsp;</td></tr>
	<tr valign="top" class="heading">
		<td width="5%">{translate key="common.date"}</td>
		<td width="5%">{translate key="submission.event.logLevel"}</td>
		<td width="5%">{translate key="common.type"}</td>
		<td width="25%">{translate key="common.user"}</td>
		<td>{translate key="common.event"}</td>
		<td width="56" align="right">{translate key="common.action"}</td>
	</tr>
	<tr><td class="headseparator" colspan="6">&nbsp;</td></tr>
{iterate from=eventLogEntries item=logEntry}
	<tr valign="top">
		<td>{$logEntry->getDateLogged()|date_format:$dateFormatTrunc}</td>
		<td>{$logEntry->getLogLevel()|escape}</td>
		<td>{$logEntry->getAssocTypeString()}</td>
		<td>
			{assign var=emailString value="`$logEntry->getUserFullName()` <`$logEntry->getUserEmail()`>"}
			{url|assign:"url" page="user" op="email" to=$emailString|to_array redirectUrl=$currentUrl subject=$logEntry->getEventTitle()}
			{$logEntry->getUserFullName()|escape} {icon name="mail" url=$url}
		</td>
		<td>
			<strong>{translate key=$logEntry->getEventTitle()|escape}</strong>
			<br />
			{$logEntry->getMessage()|strip_unsafe_html|truncate:60:"..."|escape}
		</td>
		<td align="right">{if $logEntry->getAssocType()}<a href="{url op="submissionEventLogType" path=$submission->getArticleId()|to_array:$logEntry->getAssocType():$logEntry->getAssocId()}" class="action">{translate key="common.related"}</a>&nbsp;|&nbsp;{/if}<a href="{url op="submissionEventLog" path=$submission->getArticleId()|to_array:$logEntry->getLogId()}" class="action">{translate key="common.view"}</a>{if $isEditor}&nbsp;|&nbsp;<a href="{url op="clearSubmissionEventLog" path=$submission->getArticleId()|to_array:$logEntry->getLogId()}" class="action" onclick="return confirm('{translate|escape:"javascript" key="submission.event.confirmDeleteLogEntry"}')" class="icon">{translate key="common.delete"}</a>{/if}</td>
	</tr>
	<tr valign="top">
		<td colspan="6" class="{if $eventLogEntries->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $eventLogEntries->wasEmpty()}
	<tr valign="top">
		<td colspan="6" class="nodata">{translate key="submission.history.noLogEntries"}</td>
	</tr>
	<tr valign="top">
		<td colspan="6" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="3" align="left">{page_info iterator=$eventLogEntries}</td>
		<td colspan="3" align="right">{page_links name="eventLogEntries" iterator=$eventLogEntries}</td>
	</tr>
{/if}
</table>

{if $isEditor}
<a href="{url op="clearSubmissionEventLog" path=$submission->getArticleId()}" class="action" onclick="return confirm('{translate|escape:"javascript" key="submission.event.confirmClearLog"}')">{translate key="submission.history.clearLog"}</a>
{/if}

{include file="common/footer.tpl"}
