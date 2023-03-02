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
	{if !$articlesBySimilarity->wasEmpty()}
		<h3>
			<a name="articlesBySimilarity">{translate key="plugins.generic.recommendBySimilarity.heading"}</a>
		</h3>
		<ul>
			{iterate from=articlesBySimilarity item=submission}
				{assign var=publication value=$submission->getCurrentPublication()}
				{assign var=issue value=$plugin->getIssue((int) $publication->getData('issueId'))}

				<li>
					{foreach from=$publication->getData('authors') item=author}
						{$author->getFullName()|escape},
					{/foreach}
					<a href="{url journal=$journal->getPath() page="article" op="view" path=$submission->getBestId()}">
						{$submission->getLocalizedTitle()|strip_unsafe_html}
					</a>
					{if $issue},
					<a href="{url journal=$journal->getPath() page="issue" op="view" path=$issue->getBestIssueId()}">
						{$journal->getLocalizedName()|escape}: {$issue->getIssueIdentification()|escape}
					</a>
					{/if}
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
