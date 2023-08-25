{**
 * plugins/generic/recommendBySimilarity/templates/articleFooter.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * A template to be included via Templates::Article::Footer::PageFooter hook.
 *}
<div id="articlesBySimilarityList">
	{if !$articlesBySimilarity->submissions->isEmpty()}
		<h3>
			<a name="articlesBySimilarity">{translate key="plugins.generic.recommendBySimilarity.heading"}</a>
		</h3>
		<ul>
			{foreach from=$articlesBySimilarity->submissions item=submission}
				{assign var=publication value=$submission->getCurrentPublication()}
				{assign var=issue value=$articlesBySimilarity->issues->get($publication->getData('issueId'))}

				<li>
					{foreach from=$publication->getData('authors') item=author}
						{$author->getFullName()|escape},
					{/foreach}
					<a href="{url journal=$currentContext->getPath() page="article" op="view" path=$submission->getBestId()}">
						{$submission->getLocalizedTitle()|strip_unsafe_html}
					</a>
					{if $issue},
					<a href="{url journal=$currentContext->getPath() page="issue" op="view" path=$issue->getBestIssueId()}">
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
	{/if}
</div>
