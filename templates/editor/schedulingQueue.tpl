{**
 * schedulingQueue.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Articles waiting to be scheduled for publishing.
 *
 * $Id$
 *}

{assign var="pageTitle" value="editor.schedulingQueue"}
{assign var="currentUrl" value="$pageUrl/editor/schedulingQueue"}
{include file="common/header.tpl"}

<form method="post" action="{$pageUrl}/editor/updateSchedulingQueue" onsubmit="return confirm('{translate|escape:"javascript" key="editor.schedulingQueue.saveChanges"}')">

<span class="formLabel">{translate key="journal.section"}:</span> <select name="section" onchange="location.href='{$pageUrl}/editor/schedulingQueue?section='+this.options[this.selectedIndex].value" size="1" class="selectMenu">{html_options options=$sectionOptions selected=$section}</select>

<br /><br />

<table width="100%">
<tr class="heading">
	<td>{translate key="common.id"}</td>
	<td><a href="{$pageUrl}/editor/schedulingQueue?sort=submitted">{translate key="editor.article.submitted"}</a></td>
	<td>{translate key="editor.article.authors"}</td>
	<td><a href="{$pageUrl}/editor/schedulingQueue?sort=section">{translate key="editor.article.section"}</a></td>
	<td width="100%">{translate key="common.title"}</td>
	<td>{translate key="editor.schedulingQueue.schedule"}</td>
	<td>{translate key="common.remove"}</td>
</tr>
{foreach from=$queuedArticles item=article}
<tr class="{cycle values="row,rowAlt"}">
	<td><a href="{$pageUrl}/editor/submission/{$article->getArticleID()}">{$article->getArticleID()}</a></td>
	<td>{$article->getDateSubmitted()|date_format:$dateFormatShort}</td>
	<td>{$article->getSectionName()}</a></td>
	<td>{$article->getAuthorNames()}</td>
	<td><a href="{$pageUrl}/editor/submission/{$article->getArticleID()}">{$article->getTitle()}</a></td>
	<td><select name="schedule"><option value=""></option></select></td>
	<td><input type="checkbox" name="remove[]" value="{$article->getArticleID()}" /></td>
</tr>
{foreachelse}
<tr>
<td colspan="7" class="noResults">{translate key="editor.schedulingQueue.noSubmissions"}</td>
</tr>
{/foreach}
</table>

<div align="center"><input type="submit" value="{translate key="common.saveChanges"}" class="formButton" /></div>
</form>

&#187; <a href="{$pageUrl}/editor/submissionArchive">{translate key="editor.currentIssue"}</a>

{include file="common/footer.tpl"}
