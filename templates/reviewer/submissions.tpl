{**
 * submissions.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show list of an author's submissions.
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.submission"}
{include file="common/header.tpl"}

<table width="100%">
<tr class="heading">
	<td>{translate key="common.id"}</td>
	<td width="60%">{translate key="common.title"}</td>
	<td><nobr>{translate key="common.dateSubmitted"}</nobr></td>
	<td>{translate key="article.section"}</td>
	<td colspan="2">{translate key="common.status"}</td>
</tr>
{foreach from=$submissions item=article}
<tr class="{cycle values="row,rowAlt"}">
	<td><a href="{$pageUrl}/reviewer/submission/{$article->getArticleId()}" class="tableAction">{$article->getArticleID()}</a></td>
	<td>{$article->getTitle()}</td>
	<td>{if $article->getDateSubmitted()}{$article->getDateSubmitted()|date_format:$dateFormatShort}{else}-{/if}</td>
	<td>{$article->getSectionTitle()}</td>
	<td colspan="2"><a href="{$pageUrl}/reviewer/submission/{$article->getArticleId()}" class="tableAction">{translate key="author.submissions.viewStatus"}</a></td>
</tr>
{foreachelse}
<tr>
<td colspan="5" class="noResults">{translate key="author.submissions.noSubmissions"}</td>
</tr>
{/foreach}
</table>

{include file="common/footer.tpl"}
