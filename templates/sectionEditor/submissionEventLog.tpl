{**
 * submissionHistory.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show a list of event log entries.
 *
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.submission"}
{include file="common/header.tpl"}

<ul id="tabnav">
	<li><a href="{$requestPageUrl}/summary/{$submission->getArticleId()}">{translate key="submission.summary"}</a></li>
	<li><a href="{$requestPageUrl}/submission/{$submission->getArticleId()}">{translate key="submission.submission"}</a></li>
	<li><a href="{$requestPageUrl}/submissionReview/{$submission->getArticleId()}">{translate key="submission.submissionReview"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEditing/{$submission->getArticleId()}">{translate key="submission.submissionEditing"}</a></li>
	<li><a href="{$requestPageUrl}/submissionHistory/{$submission->getArticleId()}" class="active">{translate key="submission.submissionHistory"}</a></li>
</ul>
<ul id="subnav">
	<li><a href="{$requestPageUrl}/submissionEventLog/{$submission->getArticleId()}" class="active">{translate key="submission.history.submissionEventLog"}</a></li>
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
	<td>{translate key="submission.history.submissionEventLog"}</td>
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
				<td width="56" align="right">{if $logEntry->getAssocType()}<a href="{$requestPageUrl}/submissionEventLogType/{$submission->getArticleId()}/{$logEntry->getAssocType()}/{$logEntry->getAssocId()}" class="icon"><img src="{$baseUrl}/templates/images/letter.gif" width="16" height="12" border="0" alt="" /></a> {/if}<a href="{$requestPageUrl}/submissionEventLog/{$submission->getArticleId()}/{$logEntry->getLogId()}" class="icon"><img src="{$baseUrl}/templates/images/view.gif" width="16" height="16" border="0" alt="" /></a>{if $isEditor} <a href="#" onclick="confirmAction('{$requestPageUrl}/clearSubmissionEventLog/{$submission->getArticleId()}/{$logEntry->getLogId()}', '{translate|escape:"javascript" key="submission.event.confirmDeleteLogEntry"}')" class="icon"><img src="{$baseUrl}/templates/images/delete.gif" width="16" height="16" border="0" alt="" /></a>{/if}</td>
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
		{if $isEditor}<a href="#" onclick="confirmAction('{$requestPageUrl}/clearSubmissionEventLog/{$submission->getArticleId()}', '{translate|escape:"javascript" key="submission.event.confirmClearLog"}')">{translate key="submission.history.clearLog"}</a>{/if}
	</td>
</tr>
</table>
</div>

{if $showBackLink}
&#187; <a href="{$requestPageUrl}/submissionEventLog/{$submission->getArticleId()}">{translate key="submission.event.backToEventLog"}</a>
{/if}

{include file="common/footer.tpl"}
