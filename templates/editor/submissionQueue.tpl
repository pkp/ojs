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
{assign var="pageId" value="editor.submissionQueue"}
{include file="common/header.tpl"}

<span class="formLabel">{translate key="journal.section"}:</span> <select name="section" onchange="location.href='{$pageUrl}/editor/submissionQueue?section='+this.options[this.selectedIndex].value" size="1" class="selectMenu">{html_options options=$sectionOptions selected=$section}</select>

<br /><br />

<table width="100%">
<tr class="heading">
	<td>{translate key="common.id"}</td>
	<td><a href="{$pageUrl}/editor/submissionQueue?sort=submitted&amp;order={$order}{if $section}&amp;section={$section}{/if}">{translate key="common.date"}</a></td>
	<td><a href="{$pageUrl}/editor/submissionQueue?sort=section&amp;order={$order}{if $section}&amp;section={$section}{/if}">{translate key="editor.article.section"}</a></td>
	<td>{translate key="article.authors"}</td>
	<td>{translate key="article.title"}</td>
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
	<td><a href="{$pageUrl}/editor/submission/{$article->getArticleId()}">{$article->getArticleTitle()}</a></td>
</tr>
{foreachelse}
<tr>
<td colspan="5" class="noResults">{translate key="editor.submissionQueue.noSubmissions"}</td>
</tr>
{/foreach}
</table>

<a href="{$pageUrl}/editor/submissionArchive">{translate key="editor.submissionArchive"}</a>

{include file="common/footer.tpl"}
