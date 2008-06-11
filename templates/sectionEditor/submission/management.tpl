{**
 * management.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the submission management table.
 *
 * $Id$
 *}
<a name="submission"></a>
<h3>{translate key="article.submission"}</h3>

{assign var="submissionFile" value=$submission->getSubmissionFile()}
{assign var="suppFiles" value=$submission->getSuppFiles()}

<table width="100%" class="data">
	<tr>
		<td width="20%" class="label">{translate key="article.authors"}</td>
		<td width="80%" colspan="2" class="value">
			{url|assign:"url" page="user" op="email" redirectUrl=$currentUrl authorsArticleId=$submission->getArticleId() articleId=$submission->getArticleId()}
			{$submission->getAuthorString()|escape} {icon name="mail" url=$url}
		</td>
	</tr>
	<tr>
		<td class="label">{translate key="article.title"}</td>
		<td colspan="2" class="value">{$submission->getArticleTitle()|strip_unsafe_html}</td>
	</tr>
	<tr>
		<td class="label">{translate key="submission.originalFile"}</td>
		<td colspan="2" class="value">
			{if $submissionFile}
				<a href="{url op="downloadFile" path=$submission->getArticleId()|to_array:$submissionFile->getFileId()}" class="file">{$submissionFile->getFileName()|escape}</a>&nbsp;&nbsp;{$submissionFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="article.suppFilesAbbrev"}</td>
		<td colspan="2" class="value">
			{foreach name="suppFiles" from=$suppFiles item=suppFile}
				<a href="{url op="downloadFile" path=$submission->getArticleId()|to_array:$suppFile->getFileId()}" class="file">{$suppFile->getFileName()|escape}</a>&nbsp;&nbsp;{$suppFile->getDateModified()|date_format:$dateFormatShort}&nbsp;&nbsp;<a href="{url op="editSuppFile" from="submission" path=$submission->getArticleId()|to_array:$suppFile->getSuppFileId()}" class="action">{translate key="common.edit"}</a>&nbsp;&nbsp;&nbsp;&nbsp;{if !$notFirst}&nbsp;&nbsp;&nbsp;&nbsp;<a href="{url op="addSuppFile" from="submission" path=$submission->getArticleId()}" class="action">{translate key="submission.addSuppFile"}</a>{/if}<br />
				{assign var=notFirst value=1}
			{foreachelse}
				{translate key="common.none"}&nbsp;&nbsp;&nbsp;&nbsp;<a href="{url op="addSuppFile" from="submission" path=$submission->getArticleId()}" class="action">{translate key="submission.addSuppFile"}</a>
			{/foreach}
		</td>
	</tr>
	<tr>
		<td class="label">{translate key="submission.submitter"}</td>
		<td colspan="2" class="value">
			{assign var="submitter" value=$submission->getUser()}
			{assign var=emailString value="`$submitter->getFullName()` <`$submitter->getEmail()`>"}
			{url|assign:"url" page="user" op="email" redirectUrl=$currentUrl to=$emailString|to_array subject=$submission->getArticleTitle|strip_tags articleId=$submission->getArticleId()}
			{$submitter->getFullName()|escape} {icon name="mail" url=$url}
		</td>
	</tr>
	<tr>
		<td class="label">{translate key="common.dateSubmitted"}</td>
		<td>{$submission->getDateSubmitted()|date_format:$dateFormatShort}</td>
	</tr>
	<tr>
		<td class="label">{translate key="section.section"}</td>
		<td class="value">{$submission->getSectionTitle()|escape}</td>
		<td class="value"><form action="{url op="updateSection" path=$submission->getArticleId()}" method="post">{translate key="submission.changeSection"} <select name="section" size="1" class="selectMenu">{html_options options=$sections selected=$submission->getSectionId()}</select> <input type="submit" value="{translate key="common.record"}" class="button" /></form></td>
	</tr>
	{if $submission->getCommentsToEditor()}
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.commentsToEditor"}</td>
		<td width="80%" colspan="2" class="data">{$submission->getCommentsToEditor()|strip_unsafe_html|nl2br}</td>
	</tr>
	{/if}
	{if $publishedArticle}
	<tr>
		<td class="label">{translate key="submission.abstractViews"}</td>
		<td>{$publishedArticle->getViews()}</td>
	</tr>
	{/if}
</table>
