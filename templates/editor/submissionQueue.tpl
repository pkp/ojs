{**
 * submissionQueue.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Submission Queue (submissions awaiting review/editing).
 *
 * $Id$
 *}

{assign var="pageTitle" value="editor.submissionQueue"}
{assign var="currentUrl" value="$pageUrl/editor/submissionQueue"}
{include file="common/header.tpl"}

<form method="post" action="{$pageUrl}/editor/updateSubmissionQueue" onsubmit="return confirm('{translate|escape:"javascript" key="editor.submissionQueue.saveChanges"}')">
{foreach from=$queuedSubmissions item=article}
<input type="hidden" name="articleId[]" value="{$article->getArticleId()}">
{/foreach}
<span class="formLabel">{translate key="journal.section"}:</span> <select name="section" onchange="location.href='{$pageUrl}/editor/submissionQueue?section='+this.options[this.selectedIndex].value" size="1" class="selectMenu">{html_options options=$sectionOptions selected=$section}</select>

<br /><br />

<table width="100%">
<tr class="heading">
	<td>{translate key="common.id"}</td>
	<td><a href="{$pageUrl}/editor/submissionQueue?sort=date">{translate key="common.date"}</a></td>
	<td><a href="{$pageUrl}/editor/submissionQueue?sort=section">{translate key="editor.article.section"}</a></td>
	<td>{translate key="editor.article.authors"}</td>
	<td width="100%">{translate key="common.title"}</td>
	<td>{translate key="editor.article.editor"}</td>
	<td>{translate key="editor.article.notify"}</td>
</tr>
{foreach from=$queuedSubmissions item=article}
<tr class="{cycle values="row,rowAlt"}">
	<td><a href="{$pageUrl}/editor/submission/{$article->getArticleID()}">{$article->getArticleID()}</a></td>
	<td>{$article->getDateSubmitted()|date_format:$dateFormatShort}</td>
	<td>{$article->getSectionTitle()}</a></td>
	<td>
		{foreach from=$article->getAuthors() item=author}
			<div>{$author->getFullName()}</div>
		{/foreach}
	</td>
	<td><a href="{$pageUrl}/editor/submission/{$article->getArticleId()}">{$article->getTitle()}</a></td>
	<td>
		<select name="editor_{$article->getArticleId()}">
			<option value="">Select Editor</option>
			{foreach from=$sectionEditors item=sectionEditor}
				<option value="{$sectionEditor->getUserId()}" {if $sectionEditor->getUserId() EQ $article->getEditorId()}selected="selected"{/if}>{$sectionEditor->getFullName()}</option>
			{/foreach}
		</select>
	</td>
	<td><input type="checkbox" name="notify[]" value="{$article->getArticleId()}" /></td>
</tr>
{foreachelse}
<tr>
<td colspan="7" class="noResults">{translate key="editor.submissionQueue.noSubmissions"}</td>
</tr>
{/foreach}
</table>

<div align="center"><input type="submit" value="{translate key="common.saveChanges"}" class="formButton" /></div>
</form>

&#187; <a href="{$pageUrl}/editor/submissionArchive">{translate key="editor.submissionArchive"}</a>

{include file="common/footer.tpl"}
