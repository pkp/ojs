{**
 * authorDetails.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Index of published articles by author.
 *
 * $Id$
 *}
{assign var="pageTitle" value="search.authorDetails"}
{include file="common/header.tpl"}

<h3>{$lastName|escape}, {$firstName|escape}{if $middleName} {$middleName|escape}{/if}{if $affiliation} {$affiliation|escape}{/if}</h3>
<ul>
{foreach from=$publishedArticles item=article}
	{assign var=issueId value=$article->getIssueId()}
	{assign var=issue value=$issues[$issueId]}
	{assign var=issueUnavailable value=$issuesUnavailable.$issueId}
	{assign var=sectionId value=$article->getSectionId()}
	{assign var=journalId value=$article->getJournalId()}
	{assign var=journal value=$journals[$journalId]}
	{assign var=section value=$sections[$sectionId]}
	{if $issue->getPublished() && $section && $journal}
	<li>

		<em><a href="{url journal=$journal->getPath() page="issue" op="view" path=$issue->getBestIssueId()}">{$issue->getIssueIdentification()|escape}</a> - {$section->getSectionTitle()|escape}</em><br />
		{$article->getArticleTitle()|strip_unsafe_html}<br/>
		<a href="{url journal=$journal->getPath() page="article" op="view" path=$article->getBestArticleId()}" class="file">{if $section->getAbstractsDisabled()}{translate key="article.details"}{else}{translate key="article.abstract"}{/if}</a>
		{if (!$issueUnavailable || $article->getAccessStatus())}
		{foreach from=$article->getLocalizedGalleys() item=galley name=galleyList}
			&nbsp;<a href="{url journal=$journal->getPath() page="article" op="view" path=$article->getBestArticleId()|to_array:$galley->getBestGalleyId($journal)}" class="file">{$galley->getGalleyLabel()|escape}</a>
		{/foreach}
		{/if}
	</li>
	{/if}
{/foreach}
</ul>

{include file="common/footer.tpl"}
