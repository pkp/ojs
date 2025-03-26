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

				<li>
					{foreach from=$publication->getData('authors') item=author}
						{$author->getFullName()|escape},
					{/foreach}
					<a href="{url router=PKP\core\PKPApplication::ROUTE_PAGE journal=$currentContext->getPath() page="article" op="view" path=$submission->getBestId() urlLocaleForPage=""}">
						{$publication->getLocalizedFullTitle(null, 'html')|strip_unsafe_html}
					</a>
					{if $issue},
					<a href="{url router=PKP\core\PKPApplication::ROUTE_PAGE journal=$currentContext->getPath() page="issue" op="view" path=$issue->getBestIssueId() urlLocaleForPage=""}">
						{$currentContext->getLocalizedName()|escape}: {$issue->getIssueIdentification()|escape}
					</a>
					{/if}
				</li>
			{/foreach}
		</ul>
		<p id="articlesBySimilarityPages">
			{include
				file="frontend/components/pagination.tpl"
				prevUrl=$articlesBySimilarity->previousUrl
				nextUrl=$articlesBySimilarity->nextUrl
				showingStart=$articlesBySimilarity->start
				showingEnd=$articlesBySimilarity->end
				total=$articlesBySimilarity->total
			}
		</p>
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
