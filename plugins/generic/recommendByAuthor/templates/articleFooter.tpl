{**
 * plugins/generic/recommendByAuthor/templates/articleFooter.tpl
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * A template to be included via Templates::Article::Footer::PageFooter hook.
 *}
{if !$articlesBySameAuthor->wasEmpty()}
	<section id="articlesBySameAuthorList">
		<h2>{translate key="plugins.generic.recommendByAuthor.heading"}</h2>
		<ul>
			{iterate from=articlesBySameAuthor item=articleBySameAuthor}
				{assign var=submission value=$articleBySameAuthor.publishedSubmission}
				{assign var=article value=$articleBySameAuthor.article}
				{assign var=issue value=$articleBySameAuthor.issue}
				{assign var=journal value=$articleBySameAuthor.journal}
				{assign var=publication value=$article->getCurrentPublication()}
				<li>
					{foreach from=$article->getCurrentPublication()->getData('authors') item=author}
						{$author->getFullName()|escape},
					{/foreach}
					<a href="{url router=PKP\core\PKPApplication::ROUTE_PAGE journal=$journal->getPath() page="article" op="view" path=$submission->getBestId() urlLocaleForPage=""}">
						{$publication->getLocalizedFullTitle(null, 'html')|strip_unsafe_html}
					</a>,
					<a href="{url router=PKP\core\PKPApplication::ROUTE_PAGE journal=$journal->getPath() page="issue" op="view" path=$issue->getBestIssueId() urlLocaleForPage=""}">
						{$journal->getLocalizedName()|escape}: {$issue->getIssueIdentification()|escape}
					</a>
				</li>
			{/iterate}
		</ul>
		<div id="articlesBySameAuthorPages">
			{page_links anchor="articlesBySameAuthor" iterator=$articlesBySameAuthor name="articlesBySameAuthor"}
		</div>
	</section>
{/if}
