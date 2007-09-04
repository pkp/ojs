{**
 * submissionEventLogEntry.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show a single event log entry.
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
	<li><a href="{url op="submissionEventLog" path=$submission->getArticleId()}">{translate key="submission.history.submissionEventLog"}</a></li>
	<li><a href="{url op="submissionEmailLog" path=$submission->getArticleId()}">{translate key="submission.history.submissionEmailLog"}</a></li>
	<li><a href="{url op="submissionNotes" path=$submission->getArticleId()}">{translate key="submission.history.submissionNotes"}</a></li>
</ul>

{include file="sectionEditor/submission/summary.tpl"}

<div class="separator"></div>

<h3>{translate key="submission.history.submissionEventLog"}</h3>
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="common.id"}</td>
		<td width="80%" class="value">{$logEntry->getLogID()}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.date"}</td>
		<td class="value">{$logEntry->getDateLogged()|date_format:$datetimeFormatLong}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="submission.event.logLevel"}</td>
		<td class="value">{translate key=`$logEntry->getLogLevelString()`}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.type"}</td>
		<td class="value">{translate key=$logEntry->getAssocTypeLongString()}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.user"}</td>
		<td class="value">
			{assign var=emailString value="`$logEntry->getUserFullName()` <`$logEntry->getUserEmail()`>"}
			{url|assign:"url" page="user" op="email" to=$emailString|to_array redirectUrl=$currentUrl subject=$logEntry->getEventTitle()|translate articleId=$submission->getArticleId()}
			{$logEntry->getUserFullName()|escape} {icon name="mail" url=$url}
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.event"}</td>
		<td class="value">
			<strong>{translate key=$logEntry->getEventTitle()}</strong>
			<br /><br />
			{$logEntry->getMessage()|strip_unsafe_html|nl2br}
		</td>
	</tr>
</table>
{if $isEditor}
	<a href="{url op="clearSubmissionEventLog" path=$submission->getArticleId()|to_array:$logEntry->getLogId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.event.confirmDeleteLogEntry"}')" class="action">{translate key="submission.event.deleteLogEntry"}</a><br/>
{/if}

<a class="action" href="{url op="submissionEventLog" path=$submission->getArticleId()}">{translate key="submission.event.backToEventLog"}</a>

{include file="common/footer.tpl"}
