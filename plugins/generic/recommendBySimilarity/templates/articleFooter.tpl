{**
 * plugins/generic/recommendBySimilarity/templates/articleFooter.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * A template to be included via Templates::Article::Footer::PageFooter hook.
 *}
<div id="articlesBySimilarityList">
	{if !$articlesBySimilarity->wasEmpty()}
		<h3>{translate key="plugins.generic.recommendBySimilarity.heading"}</h3>

		<ul>
			{iterate from=articlesBySimilarity item=articleBySimilarity}
				{assign var=publishedSubmission value=$articleBySimilarity.publishedSubmission}
				{assign var=article value=$articleBySimilarity.article}
				{assign var=issue value=$articleBySimilarity.issue}
				{assign var=journal value=$articleBySimilarity.journal}
				<li>
					{foreach from=$article->getAuthors() item=author}
						{$author->getFullName()|escape},
					{/foreach}
					<a href="{url journal=$journal->getPath() page="article" op="view" path=$publishedSubmission->getBestArticleId()}">
						{$article->getLocalizedTitle()|strip_unsafe_html}
					</a>,
					<a href="{url journal=$journal->getPath() page="issue" op="view" path=$issue->getBestIssueId()}">
						{$journal->getLocalizedName()|escape}: {$issue->getIssueIdentification()|escape}
					</a>
				</li>
			{/iterate}
		</ul>
		<p id="articlesBySimilarityPages">
			{page_links anchor="articlesBySimilarity" iterator=$articlesBySimilarity name="articlesBySimilarity"}
		</p>
		<p id="articlesBySimilaritySearch">
			{capture assign="advancedSearchLink"}{strip}
				<a href="{url page="search" op="search" query=$articlesBySimilarityQuery}">
					{translate key="plugins.generic.recommendBySimilarity.advancedSearch"}
				</a>
			{/strip}{/capture}
			{translate key="plugins.generic.recommendBySimilarity.advancedSearchIntro" advancedSearchLink=$advancedSearchLink}
		</p>
	{/if}
</div>
