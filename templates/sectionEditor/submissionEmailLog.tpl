{**
 * submissionEmailLog.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show submission email log page.
 *
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.emailLog"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{$requestPageUrl}/submission/{$submission->getArticleId()}">{translate key="submission.summary"}</a></li>
	<li><a href="{$requestPageUrl}/submissionReview/{$submission->getArticleId()}">{translate key="submission.review"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEditing/{$submission->getArticleId()}">{translate key="submission.editing"}</a></li>
	<li><a href="{$requestPageUrl}/submissionHistory/{$submission->getArticleId()}">{translate key="submission.history"}</a></li>
</ul>

<ul class="menu">
	<li><a href="{$requestPageUrl}/submissionEventLog/{$submission->getArticleId()}">{translate key="submission.history.submissionEventLog"}</a></li>
	<li class="current"><a href="{$requestPageUrl}/submissionEmailLog/{$submission->getArticleId()}">{translate key="submission.history.submissionEmailLog"}</a></li>
	<li><a href="{$requestPageUrl}/submissionNotes/{$submission->getArticleId()}">{translate key="submission.history.submissionNotes"}</a></li>
</ul>

{include file="sectionEditor/submission/summary.tpl"}

<div class="separator"></div>

<h3>{translate key="submission.history.submissionEmailLog"}</h3>

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
		<td>{if $logEntry->getAssocType()}<a href="{$requestPageUrl}/submissionEmailLogType/{$submission->getArticleId()}/{$logEntry->getAssocType()}/{$logEntry->getAssocId()}" class="action">{translate key="common.view"}</a>&nbsp;{/if}<a href="{$requestPageUrl}/submissionEmailLog/{$submission->getArticleId()}/{$logEntry->getLogId()}" class="action">{translate key="common.details"}</a>{if $isEditor}&nbsp;<a href="{$requestPageUrl}/clearSubmissionEmailLog/{$submission->getArticleId()}/{$logEntry->getLogId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.email.confirmDeleteLogEntry"}')" class="action">{translate key="common.delete"}</a>{/if}</td>
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
	{if $isEditor}
		<tr valign="top">
			<td colspan="6">
				<a class="action" href="{$requestPageUrl}/clearSubmissionEmailLog/{$submission->getArticleId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.email.confirmClearLog"}')">{translate key="submission.history.clearLog"}</a>
			</td>
		</tr>
	{/if}
</table>

{include file="common/footer.tpl"}
