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

{assign var="pageTitle" value="copyeditor.activeAssignments"}
{include file="common/header.tpl"}

<table width="100%">
<tr class="heading">
	<td>{translate key="common.id"}</td>
	<td><nobr>{translate key="common.dateSubmitted"}</nobr></td>
	<td width="60%">{translate key="common.title"}</td>
	<td><nobr>{translate key="common.dateRequested"}</nobr></td>
	<td><nobr>{translate key="common.dateCompleted"}</nobr></td>
</tr>
{foreach from=$submissions item=article}
<tr class="{cycle values="row,rowAlt"}">
	<td><a href="{$pageUrl}/copyeditor/submission/{$article->getArticleId()}">{$article->getArticleID()}</a></td>
	<td>{if $article->getDateSubmitted()}{$article->getDateSubmitted()|date_format:$dateFormatShort}{else}-{/if}</td>
	<td><a href="{$pageUrl}/copyeditor/submission/{$article->getArticleId()}">{$article->getArticleTitle()}</a></td>
	<td>{if $article->getDateNotified()}{$article->getDateNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
	<td>{if $article->getDateCompleted()}{$article->getDateCompleted()|date_format:$dateFormatShort}{else}-{/if}</td>
</tr>
{foreachelse}
<tr>
<td colspan="5" class="noResults">{translate key="author.submissions.noSubmissions"}</td>
</tr>
{/foreach}
</table>

{include file="common/footer.tpl"}
