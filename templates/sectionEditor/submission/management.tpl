{**
 * management.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the submission management table.
 *
 * $Id$
 *}

<a name="submission"></a>
<h3>{translate key="submission.submission"}</h3>

{assign var="editor" value=$submission->getEditor()}
{assign var="submissionFile" value=$submission->getSubmissionFile()}
{assign var="suppFiles" value=$submission->getSuppFiles()}

<table width="100%" class="data">
	<tr>
		<td width="20%" class="label">{translate key="article.authors"}</td>
		<td width="80%" colspan="2" class="value">{$submission->getAuthorString()} {icon name="mail" url="FIXME"}</td>
	</tr>
	<tr>
		<td class="label">{translate key="article.title"}</td>
		<td colspan="2" class="value">{$submission->getArticleTitle()}</td>
	</tr>
	<tr>
		<td class="label">{translate key="submission.originalFile"}</td>
		<td colspan="2" class="value">
			{if $submissionFile}
				<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$submissionFile->getFileId()}" class="file">{$submissionFile->getFileName()}</a> {$submissionFile->getDateModified()|date_format:$dateFormatShort}
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
	<tr>
		<td class="label">{translate key="section.section"}</td>
		<td class="value">{$submission->getSectionTitle()}</td>
		<td class="value"><form action="{$requestPageUrl}/updateSection/{$submission->getArticleId()}" method="post">{translate key="submission.changeSection"} <select name="section" size="1" class="selectMenu">{html_options options=$sections selected=$submission->getSectionId()}</select> <input type="submit" value="{translate key="common.record"}" class="button" /></form></td>
	</tr>
	<tr>
		<td class="label">{translate key="editor.article.editor"}</td>
		<td class="value">{if $editor}{$editor->getEditorFullName()} {icon name="mail" url="FIXME"}{else}{translate key="common.noneAssigned"}{/if}</td>
		<td class="value">{if $isEditor}<a href="{$pageUrl}/editor/assignEditor/{$submission->getArticleId()}" class="action">{translate key="editor.article.assignEditor"}</a>{/if}</td>
	</tr>
</table>
