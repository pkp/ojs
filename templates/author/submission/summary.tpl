{**
 * summary.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the summary of an author's submission.
 *
 *
 * $Id$
 *}

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
			<a href="{$requestPageUrl}/addSuppFile/{$submission->getArticleId()}" class="action">{translate key="submission.addSuppFile"}</a>
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

