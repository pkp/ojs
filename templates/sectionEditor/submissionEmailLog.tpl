{**
 * submissionEmailLog.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show a list of email log entries.
 *
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.emailLog"}
{include file="common/header.tpl"}

<ul id="tabnav">
	<li><a href="{$requestPageUrl}/summary/{$submission->getArticleId()}">{translate key="submission.summary"}</a></li>
	<li><a href="{$requestPageUrl}/submission/{$submission->getArticleId()}">{translate key="submission.submission"}</a></li>
	<li><a href="{$requestPageUrl}/submissionReview/{$submission->getArticleId()}">{translate key="submission.submissionReview"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEditing/{$submission->getArticleId()}">{translate key="submission.submissionEditing"}</a></li>
	<li><a href="{$requestPageUrl}/submissionHistory/{$submission->getArticleId()}" class="active">{translate key="submission.submissionHistory"}</a></li>
</ul>
<ul id="subnav">
	<li><a href="{$requestPageUrl}/submissionEventLog/{$submission->getArticleId()}">{translate key="submission.history.submissionEventLog"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEmailLog/{$submission->getArticleId()}" class="active">{translate key="submission.history.submissionEmailLog"}</a></li>
	<li><a href="{$requestPageUrl}/submissionNotes/{$submission->getArticleId()}">{translate key="submission.history.submissionNotes"}</a></li>
</ul>

<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.history.submissionEmailLog"}</td>
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
				<td width="56" align="right">{if $logEntry->getAssocType()}{icon name="letter" url="$requestPageUrl/submissionEmailLogType/`$submission->getArticleId()`/`$logEntry->getAssocType()`/`$logEntry->getAssocId()`"}&nbsp;{/if}{icon name="view" url="$requestPageUrl/submissionEmailLog/`$submission->getArticleId()`/`$logEntry->getLogId()`"}{if $isEditor}&nbsp;<a href="{$requestPageUrl}/clearSubmissionEmailLog/{$submission->getArticleId()}/{$logEntry->getLogId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.email.confirmDeleteLogEntry"}')" class="icon">{icon name="delete"}</a>{/if}</td>
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
		{if $isEditor}<a href="{$requestPageUrl}/clearSubmissionEmailLog/{$submission->getArticleId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.email.confirmClearLog"}')">{translate key="submission.history.clearLog"}</a>{/if}
	</td>
</tr>
</table>
</div>

{if $showBackLink}
&#187; <a href="{$requestPageUrl}/submissionEmailLog/{$submission->getArticleId()}">{translate key="submission.email.backToEmailLog"}</a>
{/if}

{include file="common/footer.tpl"}
