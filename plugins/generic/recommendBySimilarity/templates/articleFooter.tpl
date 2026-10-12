{**
 * plugins/generic/recommendBySimilarity/templates/articleFooter.tpl
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * A template to be included via Templates::Article::Footer::PageFooter hook.
 *}
{if !$articlesBySimilarity->submissions->isEmpty()}
	<section id="articlesBySimilarityList">
		<h2 class="label" id="articlesBySimilarity">
			{translate key="plugins.generic.recommendBySimilarity.heading"}
		</h2>
		<ul>
			{foreach from=$articlesBySimilarity->submissions item=submission}
				{assign var=publication value=$submission->getCurrentPublication()}
				{assign var=issue value=$articlesBySimilarity->issues->get($publication->getData('issueId'))}
				{capture assign="author"}{strip}
					{foreach from=$article->getCurrentPublication()->getData('authors') item="author" name="authors"}
						{$author->getFullName()|escape}{if !$smarty.foreach.authors.last}{translate key="common.commaListSeparator"}{/if}
					{/foreach}
				{/strip}{/capture}
				{capture assign="title"}{strip}
					<a href="{url router=PKP\core\PKPApplication::ROUTE_PAGE journal=$currentContext->getPath() page="article" op="view" path=$submission->getBestId() urlLocaleForPage=""}">
						{$publication->getLocalizedFullTitle(null, 'html')|strip_unsafe_html}
					</a>
				{/strip}{/capture}
				{capture assign="issue"}{strip}
					<a href="{url router=PKP\core\PKPApplication::ROUTE_PAGE journal=$currentContext->getPath() page="issue" op="view" path=$issue->getBestIssueId() urlLocaleForPage=""}">
						{$issue->getIssueIdentification()|escape}
					</a>
				{/capture}
				<li>
					{translate
						key="plugins.generic.recommendBySimilarity.publishedIn"
						author=$author
						title=$title
						issue=$issue
					}
				</li>
			{/foreach}
		</ul>
		<div id="articlesBySimilarityPages">
			{include
				file="frontend/components/pagination.tpl"
				prevUrl=$articlesBySimilarity->previousUrl
				nextUrl=$articlesBySimilarity->nextUrl
				showingStart=$articlesBySimilarity->start
				showingEnd=$articlesBySimilarity->end
				total=$articlesBySimilarity->total
			}
		</div>
		<p id="articlesBySimilaritySearch">
			{capture assign="articlesBySimilaritySearchLink"}{strip}
				<a href="{url page="search" op="search" query=$articlesBySimilarity->query}">
					{translate key="plugins.generic.recommendBySimilarity.advancedSearch"}
				</a>
			{/strip}{/capture}
			{translate key="plugins.generic.recommendBySimilarity.advancedSearchIntro" advancedSearchLink=$articlesBySimilaritySearchLink}
		</p>
	</section>
{/if}
