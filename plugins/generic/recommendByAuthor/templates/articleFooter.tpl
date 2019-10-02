{**
 * plugins/generic/recommendByAuthor/templates/articleFooter.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * A template to be included via Templates::Article::Footer::PageFooter hook.
 *}
<div id="articlesBySameAuthorList">
	{if $noMetricSelected}
		<h3>{translate key="plugins.generic.recommendByAuthor.heading"}</h3>
		{translate key="plugins.generic.recommendByAuthor.noMetric"}
	{else}
		{if !$articlesBySameAuthor->wasEmpty()}
			<h3>{translate key="plugins.generic.recommendByAuthor.heading"}</h3>

			<ul>
				{iterate from=articlesBySameAuthor item=articleBySameAuthor}
					{assign var=submission value=$articleBySameAuthor.publishedSubmission}
					{assign var=article value=$articleBySameAuthor.article}
					{assign var=issue value=$articleBySameAuthor.issue}
					{assign var=journal value=$articleBySameAuthor.journal}
					<li>
						{foreach from=$article->getAuthors() item=author}
							{$author->getFullName()|escape},
						{/foreach}
						<a href="{url journal=$journal->getPath() page="article" op="view" path=$submission->getBestId()}">
							{$article->getLocalizedTitle()|strip_unsafe_html}
						</a>,
						<a href="{url journal=$journal->getPath() page="issue" op="view" path=$issue->getBestIssueId()}">
							{$journal->getLocalizedName()|escape}: {$issue->getIssueIdentification()|escape}
						</a>
					</li>
				{/iterate}
			</ul>
			<div id="articlesBySameAuthorPages">
				{page_links anchor="articlesBySameAuthor" iterator=$articlesBySameAuthor name="articlesBySameAuthor"}
			</div>
		{/if}
	{/if}
</div>
