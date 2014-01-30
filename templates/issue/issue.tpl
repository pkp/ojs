{**
 * templates/issue/issue.tpl
 *
 * Copyright (c) 2013 Simon Fraser University Library
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Issue
 *
 *}
{foreach name=sections from=$publishedArticles item=section key=sectionId}
	{if $section.title}<div class="toc-Title2">{$section.title|escape}</div>{/if}

	{foreach from=$section.articles item=article}
		{assign var=issue2 value=$issueDao->getIssueById($article->getIssueId())}
		{assign var=articlePath value=$article->getBestArticleId($currentJournal)}
		{assign var=articleId value=$article->getArticleId()}

		<div class="toc-title">{$article->getArticleTitle()|strip_unsafe_html}</div>
		<div class="toc-date">{$article->getDatePublished()|date_format:"%B %e, %Y"}. Vol. {$issue2->getVolume()}({$issue2->getNumber()}){if $article->getPages()|escape}, pp.{$article->getPages()|escape}{/if}</div>

		<div class="toc-byline">
			{if (!$section.hideAuthor && $article->getHideAuthor() == 0) || $article->getHideAuthor() == 2}
				{foreach from=$article->getAuthors() item=author name=authorList}
					{$author->getFullName()|escape}{if !$smarty.foreach.authorList.last},{/if}
				{/foreach}
			{else}
				&nbsp;
			{/if}
		</div>
		<div class="toc-links">
			{foreach from=$article->getGalleys() item=galley name=galleyList}
				<a href="{url page="article" op="view" path=$articlePath|to_array:$galley->getBestGalleyId($currentJournal)}" {if $galley->getRemoteURL()}target="_blank" {/if}class="file">{$galley->getGalleyLabel()|escape}</a>
			{/foreach}
			{if $ArticleCommentDAO->attributedCommentsExistForArticle($article->getArticleId())}
				{if $ArticleCommentDAO->attributedCommentsExistForArticle($article->getArticleId()) == 1}
					<a href="{$baseUrl}/comment/view/{$article->getArticleId()}/0" class="file">Comment ({$ArticleCommentDAO->attributedCommentsExistForArticle($article->getArticleId())})</a>
				{else}
					<a href="{$baseUrl}/comment/view/{$article->getArticleId()}/0" class="file">Comments ({$CommentDAO->attributedCommentsExistForArticle($article->getArticleId())})</a>
				{/if}
			{/if}
			{else}
				<a href="{$baseUrl}/comment/view/{$article->getArticleId()}/0" class="file">Add a comment</a>
			{/if}
		</div>
	{/foreach}

{if !$smarty.foreach.sections.last}
{/if}
{/foreach}