{**
 * submission.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the status of an author's submission.
 *
 *
 * $Id$
 *}

{assign_translate var="pageTitleTranslated" key="submission.page.summary" id=$submission->getArticleId()}
{assign var="pageId" value="author.submission"}
{include file="common/header.tpl"}

<ul class="menu">
	<li class="current"><a href="{$requestPageUrl}/submission/{$submission->getArticleId()}">{translate key="submission.summary"}</a></li>
	<li><a href="{$requestPageUrl}/submissionReview/{$submission->getArticleId()}">{translate key="submission.submissionReview"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEditing/{$submission->getArticleId()}">{translate key="submission.submissionEditing"}</a></li>
</ul>

<h3>{translate key="submission.submission"}</h3>
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.authors"}</td>
		<td width="80%" class="data">{$submission->getAuthorString(false)}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.title"}</td>
		<td width="80%" class="data">{$submission->getArticleTitle()}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="editor.article.originalFile"}</td>
		<td width="80%" class="data">
			{if $submissionFile}
				<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$submissionFile->getFileId()}/{$submissionFile->getRevision()}" class="file">{$submissionFile->getFileName()}</a> {$submissionFile->getDateModified()|date_format:$dateFormatShort}</td>
			{else}
				{translate key="common.none"}
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.suppFilesAbbrev"}</td>
		<td width="80%" class="data">
			{foreach from=$submission->getSuppFiles() item=suppFile}
			<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$suppFile->getFileId()}">{$suppFile->getTitle()}</a><br />
			{foreachelse}
				{translate key="common.none"}<br />
			{/foreach}
			<a href="{$requestPageUrl}/addSuppFile/{$submission->getArticleId()}" class="action">{translate key="submission.addSuppFile"} -- FIXME</a>
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="submission.submitter"}</td>
		<td width="80%" class="data">{assign var="submitter" value=$submission->getUser()}{$submitter->getFullName()}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="section.section"}</td>
		<td width="80%" class="data">{$submission->getSectionTitle()}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.editor"}</td>
		{assign var="editor" value=$submission->getEditor()}
		<td width="80%" class="data">{if ($editor !== null)}{$editor->getEditorFullName()}{else}{translate key="common.none"}{/if}</td>
	</tr>
</table>

<div class="separator"></div>

<h3>{translate key="common.status}</h3>
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="common.currentStatus"}</td>
		<td width="80%" class="data">
			{assign var="status" value=$submission->getStatus()}
			{if $status == ARCHIVED}
				{translate key="submissions.archived"}
			{elseif $status==QUEUED_UNASSIGNED}
				{translate key="submissions.queuedUnassigned"}
			{elseif $status==QUEUED_EDITING}
				{translate key="submissions.queuedEditing"}
			{elseif $status==QUEUED_REVIEW}
				{translate key="submissions.queuedReview"}
			{elseif $status==SCHEDULED}
				{translate key="submissions.scheduled"}
			{elseif $status==PUBLISHED}
				{translate key="submissions.published"}
			{elseif $status==DECLINED}
				{translate key="submissions.declined"}
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="submission.initiated"}</td>
		<td width="80%" class="data">{$submission->getSubmissionStatus()}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="submission.lastModified"}</td>
		<td width="80%" class="data">{$submission->getDateStatusModified()}</td>
	</tr>
</table>

<div class="separator"></div>

<h3>{translate key="submission.metadata"}</h3>
<h4>{translate key="article.authors"}</h4>
<table width="100%" class="data">
{foreach name=authors from=$submission->getAuthors() key=authorIndex item=author}
	<tr valign="top">
		<td width="20%" class="label">{translate key="user.firstName"}</td>
		<td width="80%" class="data">{$author->getFirstName()|escape}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="user.middleName"}</td>
		<td width="80%" class="data">{$author->getMiddleName()|escape}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="user.lastName"}</td>
		<td width="80%" class="data">{$author->getLastName()|escape}</td>
	</tr>
	{if $author->getPrimaryContact()}
	<tr valign="top">
		<td colspan="2" class="data">
			{translate key="author.submit.selectPrincipalContact"}
		</td>
	</tr>
	{/if}
	{if !$smarty.foreach.authors.last}
		<tr><td colspan="2">&nbsp;</td></tr>
	{/if}
{/foreach}
</table>

<div class="separator"></div>
<h4>{translate key="submission.titleAndAbstract"}</h4>
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.title"}</td>
		<td width="80%" class="value">{$submission->getArticleTitle()}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.abstract"}</td>
		<td width="80%" class="value">{$submission->getArticleAbstract()}</td>
	</tr>
</table>

{include file="common/footer.tpl"}
