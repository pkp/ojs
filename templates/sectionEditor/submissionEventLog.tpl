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
{assign var="pageId" value="sectionEditor.submissionHistory"}
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
			{if $isEditor}<a href="{$requestPageUrl}/clearSubmissionEventLog/{$submission->getArticleId()}" class="action" onclick="return confirm('{translate|escape:"javascript" key="submission.event.confirmClearLog"}')">{translate key="submission.history.clearLog"}</a>{/if}
		</td>
	</tr>
</table>

{include file="common/footer.tpl"}
