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
		<td width="80%" colspan="2" class="value">
			{assign var=urlEscaped value=$currentUrl|escape:"url"}
			{$submission->getAuthorString()} {icon name="mail" url="`$pageUrl`/user/email?redirectUrl=$urlEscaped&authorsArticleId=`$submission->getArticleId()`"}
		</td>
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
		<td colspan="2" class="value">
			{foreach name="suppFiles" from=$suppFiles item=suppFile}
				<a href="{$requestPageUrl}/editSuppFile/{$submission->getArticleId()}/{$suppFile->getSuppFileId()}" class="file">{$suppFile->getFileName()}</a>&nbsp;&nbsp;{$suppFile->getDateModified()|date_format:$dateFormatShort}&nbsp;&nbsp;<a href="{$requestPageUrl}/editSuppFile/{$submission->getArticleId()}/{$suppFile->getSuppFileId()}" class="action">{translate key="common.edit"}</a>&nbsp;&nbsp;&nbsp;&nbsp;{if !$notFirst}&nbsp;&nbsp;&nbsp;&nbsp;<a href="{$requestPageUrl}/addSuppFile/{$submission->getArticleId()}" class="action">{translate key="submission.addSuppFile"}</a>{/if}<br />
				{assign var=notFirst value=1}
			{foreachelse}
				{translate key="common.none"}&nbsp;&nbsp;&nbsp;&nbsp;<a href="{$requestPageUrl}/addSuppFile/{$submission->getArticleId()}" class="action">{translate key="submission.addSuppFile"}</a>
			{/foreach}
		</td>
	</tr>
	<tr>
		<td class="label">{translate key="submission.submitter"}</td>
		<td colspan="2" class="value">
			{assign var="submitter" value=$submission->getUser()}
			{assign var=emailString value="`$submitter->getFullName()` <`$submitter->getEmail()`>"}
			{assign var=emailStringEscaped value=$emailString|escape:"url"}
			{assign var=urlEscaped value=$currentUrl|escape:"url"}
			{assign var=subjectEscaped value=$submission->getArticleTitle()|escape:"url"}
			{$submitter->getFullName()} {icon name="mail" url="`$pageUrl`/user/email?to[]=$emailStringEscaped&redirectUrl=$urlEscaped&subject=$subjectEscaped"}
		</td>
	</tr>
	<tr>
		<td class="label">{translate key="section.section"}</td>
		<td class="value">{$submission->getSectionTitle()}</td>
		<td class="value"><form action="{$requestPageUrl}/updateSection/{$submission->getArticleId()}" method="post">{translate key="submission.changeSection"} <select name="section" size="1" class="selectMenu">{html_options options=$sections selected=$submission->getSectionId()}</select> <input type="submit" value="{translate key="common.record"}" class="button" /></form></td>
	</tr>
	<tr>
		<td class="label">{translate key="user.role.editor"}</td>
		<td class="value">
			{if $editor}
				{assign var=emailString value="`$editor->getEditorFullName()` <`$editor->getEditorEmail()`>"}
				{assign var=emailStringEscaped value=$emailString|escape:"url"}
				{assign var=urlEscaped value=$currentUrl|escape:"url"}
				{assign var=subjectEscaped value=$submission->getArticleTitle()|escape:"url"}
				{$editor->getEditorFullName()} {icon name="mail" url="`$pageUrl`/user/email?to[]=$emailStringEscaped&redirectUrl=$urlEscaped&subject=$subjectEscaped"}
			{else}
				{translate key="common.noneAssigned"}
			{/if}
		</td>
		<td class="value">{if $isEditor}<a href="{$pageUrl}/editor/assignEditor?articleId={$submission->getArticleId()}" class="action">{translate key="editor.article.assignEditor"}</a>{/if}</td>
	</tr>
	{if $submission->getCommentsToEditor()}
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.commentsToEditor"}</td>
		<td width="80%" colspan="2" class="data">{$submission->getCommentsToEditor()|nl2br}</td>
	</tr>
	{/if}
</table>
