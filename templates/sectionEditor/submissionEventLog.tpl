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
	<li><a href="{$requestPageUrl}/submission/{$submission->getArticleId()}">{translate key="submission.summary"}</a></li>
	<li><a href="{$requestPageUrl}/submissionReview/{$submission->getArticleId()}">{translate key="submission.review"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEditing/{$submission->getArticleId()}">{translate key="submission.editing"}</a></li>
	<li><a href="{$requestPageUrl}/submissionHistory/{$submission->getArticleId()}">{translate key="submission.history"}</a></li>
</ul>

<ul class="menu">
	<li class="current"><a href="{$requestPageUrl}/submissionEventLog/{$submission->getArticleId()}">{translate key="submission.history.submissionEventLog"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEmailLog/{$submission->getArticleId()}">{translate key="submission.history.submissionEmailLog"}</a></li>
	<li><a href="{$requestPageUrl}/submissionNotes/{$submission->getArticleId()}">{translate key="submission.history.submissionNotes"}</a></li>
</ul>

{include file="sectionEditor/submission/summary.tpl"}

<div class="separator"></div>

<h3>{translate key="submission.history.submissionEventLog"}</h3>
<table width="100%" class="listing">
	<tr>
		<td colspan="3" align="left">{page_info iterator=$eventLogEntries}</td>
		<td colspan="3" align="right">{page_links name="eventLogEntries" iterator=$eventLogEntries}</td>
	</tr>
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
		<td>{$logEntry->getLogLevel()}</td>
		<td>{$logEntry->getAssocTypeString()}</td>
		<td>
			{assign var=emailString value="`$logEntry->getUserFullName()` <`$logEntry->getUserEmail()`>"}
			{assign var=emailStringEscaped value=$emailString|escape:"url"}
			{assign var=urlEscaped value=$currentUrl|escape:"url"}
			{assign var=subjectEscaped value=$logEntry->getEventTitle()|escape:"url"}
			{$logEntry->getUserFullName()} {icon name="mail" url="`$pageUrl`/user/email?to[]=$emailStringEscaped&redirectUrl=$urlEscaped&subject=$subjectEscaped"}
		</td>
		<td>
			<strong>{translate key=$logEntry->getEventTitle()}</strong>
			<br />
			{$logEntry->getMessage()|truncate:60:"..."}
		</td>
		<td align="right">{if $logEntry->getAssocType()}<a href="{$requestPageUrl}/submissionEventLogType/{$submission->getArticleId()}/{$logEntry->getAssocType()}/{$logEntry->getAssocId()}" class="action">{translate key="common.related"}</a>&nbsp;{/if}<a href="{$requestPageUrl}/submissionEventLog/{$submission->getArticleId()}/{$logEntry->getLogId()}" class="action">{translate key="common.view"}</a>{if $isEditor}&nbsp;<a href="{$requestPageUrl}/clearSubmissionEventLog/{$submission->getArticleId()}/{$logEntry->getLogId()}" class="action" onclick="return confirm('{translate|escape:"javascript" key="submission.event.confirmDeleteLogEntry"}')" class="icon">{translate key="common.delete"}</a>{/if}</td>
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
<a href="{$requestPageUrl}/clearSubmissionEventLog/{$submission->getArticleId()}" class="action" onclick="return confirm('{translate|escape:"javascript" key="submission.event.confirmClearLog"}')">{translate key="submission.history.clearLog"}</a>
{/if}

{include file="common/footer.tpl"}
