{**
 * management.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the author's submission management table.
 *
 * $Id$
 *}

<a name="submission"></a>
<h3>{translate key="submission.submission"}</h3>
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.authors"}</td>
		<td width="80%" colspan="2" class="data">{$submission->getAuthorString(false)}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.title"}</td>
		<td width="80%" colspan="2" class="data">{$submission->getArticleTitle()}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="editor.article.originalFile"}</td>
		<td width="80%" colspan="2" class="data">
			{if $submissionFile}
				<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$submissionFile->getFileId()}/{$submissionFile->getRevision()}" class="file">{$submissionFile->getFileName()}</a> {$submissionFile->getDateModified()|date_format:$dateFormatShort}</td>
			{else}
				{translate key="common.none"}
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="article.suppFilesAbbrev"}</td>
		<td width="30%" class="value">
			{foreach name="suppFiles" from=$suppFiles item=suppFile}
				<a href="{$requestPageUrl}/editSuppFile/{$submission->getArticleId()}/{$suppFile->getSuppFileId()}" class="file">{$suppFile->getFileName()}</a> {$suppFile->getDateModified()|date_format:$dateFormatShort}<br />
			{foreachelse}
				{translate key="common.none"}
			{/foreach}
		</td>
		<td width="50%" class="value"><a href="{$requestPageUrl}/addSuppFile/{$submission->getArticleId()}" class="action">{translate key="submission.addSuppFile"}</a></td>
	</tr>
	<tr>
		<td class="label">{translate key="submission.submitter"}</td>
		<td colspan="2" class="value">{assign var="submitter" value=$submission->getUser()}{$submitter->getFullName()} {icon name="mail" url="FIXME"}</td>
	</tr>

	<tr valign="top">
		<td width="20%" class="label">{translate key="section.section"}</td>
		<td width="80%" colspan="2" class="data">{$submission->getSectionTitle()}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.editor"}</td>
		{assign var="editor" value=$submission->getEditor()}
		<td width="80%" colspan="2" class="data">{if ($editor !== null)}{$editor->getEditorFullName()}{else}{translate key="common.none"}{/if}</td>
	</tr>
	{if $submission->getCommentsToEditor()}
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.commentsToEditor"}</td>
		<td width="80%" colspan="2" class="data">{$submission->getCommentsToEditor()|nl2br}</td>
	</tr>
	{/if}
</table>

