{**
 * templates/frontend/pages/searchAuthorDetails.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Index of published submissions by author.
 *
 *}
{strip}
{assign var="pageTitle" value="search.authorDetails"}
{include file="frontend/components/header.tpl"}
{/strip}
<div id="authorDetails">
<h3>{$authorName|escape}{if $affiliation}, {$affiliation|escape}{/if}{if $country}, {$country|escape}{/if}</h3>
<ul>
{foreach from=$publishedSubmissions item=article}
	{assign var=issueId value=$article->getIssueId()}
	{assign var=issue value=$issues[$issueId]}
	{assign var=issueUnavailable value=$issuesUnavailable.$issueId}
	{assign var=sectionId value=$article->getSectionId()}
	{assign var=journalId value=$article->getJournalId()}
	{assign var=journal value=$journals[$journalId]}
	{assign var=section value=$sections[$sectionId]}
	{if $issue->getPublished() && $section && $journal}
	<li>

		<em><a href="{url journal=$journal->getPath() page="issue" op="view" path=$issue->getBestIssueId()}">{$journal->getLocalizedName()|escape} {$issue->getIssueIdentification()|strip_unsafe_html|nl2br}</a> - {$section->getLocalizedTitle()|escape}</em><br />
		{$article->getLocalizedTitle()|strip_unsafe_html}<br/>
		<a href="{url journal=$journal->getPath() page="article" op="view" path=$article->getBestArticleId()}" class="file">{if $article->getLocalizedAbstract()}{translate key="article.abstract"}{else}{translate key="article.details"}{/if}</a>
		{if (!$issueUnavailable || $article->getAccessStatus() == $smarty.const.ARTICLE_ACCESS_OPEN)}
		{foreach from=$article->getGalleys() item=galley name=galleyList}
			&nbsp;<a href="{url journal=$journal->getPath() page="article" op="view" path=$article->getBestArticleId()|to_array:$galley->getBestGalleyId()}" class="file">{$galley->getGalleyLabel()|escape}</a>
		{/foreach}
		{/if}
	</li>
	{/if}
{/foreach}
</ul>
</div>
{include file="frontend/components/footer.tpl"}

