{**
 * plugins/generic/recommendByAuthor/templates/articleFooter.tpl
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * A template to be included via Templates::Article::Footer::PageFooter hook.
 *}
{if $articlesBySameAuthor && $articlesBySameAuthor->results|@count}
	<section id="articlesBySameAuthorList">
		<h2>{translate key="plugins.generic.recommendByAuthor.heading"}</h2>
		<ul>
			{foreach from=$articlesBySameAuthor->results item="articleBySameAuthor"}
				{assign var="submission" value=$articleBySameAuthor.publishedSubmission}
				{assign var="article" value=$articleBySameAuthor.article}
				{assign var="issue" value=$articleBySameAuthor.issue}
				{assign var="publication" value=$article->getCurrentPublication()}
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
						key="plugins.generic.recommendByAuthor.publishedIn"
						author=$author
						title=$title
						issue=$issue
					}
				</li>
			{/foreach}
		</ul>
		<div id="articlesBySameAuthorPages">
			{include
				file="frontend/components/pagination.tpl"
				prevUrl=$articlesBySameAuthor->previousUrl
				nextUrl=$articlesBySameAuthor->nextUrl
				showingStart=$articlesBySameAuthor->start
				showingEnd=$articlesBySameAuthor->end
				total=$articlesBySameAuthor->total
			}
		</div>
	</section>
{/if}
