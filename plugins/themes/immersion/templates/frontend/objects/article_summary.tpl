{**
 * templates/frontend/objects/article_summary.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief View of an Article summary which is shown within a list of articles.
 *
 * @uses $article Article The article
 * @uses $authorUserGroups Traversible The set of author user groups
 * @uses $hasAccess bool Can this user access galleys for this context? The
 *       context may be an issue or an article
 * @uses $showDatePublished bool Show the date this article was published?
 * @uses $hideGalleys bool Hide the article galleys for this article?
 * @uses $primaryGenreIds array List of file genre ids for primary file types
 *}
{assign var=articlePath value=$article->getBestId()}
{assign var="publication" value=$article->getCurrentPublication()}
{assign var="coverImage" value=$publication->getLocalizedData('coverImage')}
{assign var="coverImageUrl" value=$publication->getLocalizedCoverImageUrl($article->getData('contextId'))}

{if (!$section.hideAuthor && $publication->getData('hideAuthor') == \APP\submission\Submission::AUTHOR_TOC_DEFAULT) || $publication->getData('hideAuthor') == \APP\submission\Submission::AUTHOR_TOC_SHOW}
	{assign var="showAuthor" value=true}
{/if}

<article class="article">
	<div class="row">
		{if $coverImage && $requestedOp !== "search"}
			<div class="col-md-4">
				<figure class="article__img">
					<a {if $journal}href="{url journal=$journal->getPath() page="article" op="view" path=$articlePath}"{else}href="{url page="article" op="view" path=$articlePath}"{/if} class="file">
						<img class="img-fluid"
					 		src="{$coverImageUrl|escape}"
							{if $coverImage.altText != ''} alt="{$coverImage.altText|escape}"{else} alt="{translate key="article.coverPage.altText"}"{/if}>
					</a>
				</figure>
			</div>
		{/if}
		<div class="col-md-{if $requestedOp === "search"}12{else}8{/if}{if !$coverImageUrl} offset-md-4{/if}">
			{if $showAuthor}
				<p class="article__meta">{$publication->getAuthorString($authorUserGroups)|escape}</p>
			{/if}

			<h4 class="article__title">
				<a {if $journal}href="{url journal=$journal->getPath() page="article" op="view" path=$articlePath}"{else}href="{url page="article" op="view" path=$articlePath}"{/if}>
					{$publication->getLocalizedFullTitle(null, 'html')|strip_unsafe_html}
				</a>
			</h4>

			{if !$hideGalleys}
				<ul class="article__btn-group">
					{foreach from=$article->getGalleys() item=galley}
						{if $primaryGenreIds}
							{assign var="file" value=$galley->getFile()}
							{if !$galley->getRemoteUrl() && !($file && in_array($file->getGenreId(), $primaryGenreIds))}
								{continue}
							{/if}
						{/if}
						<li>
							{assign var="hasArticleAccess" value=$hasAccess}
							{if $currentContext->getSetting('publishingMode') == $smarty.const.PUBLISHING_MODE_OPEN || $article->getCurrentPublication()->getData('accessStatus') == $smarty.const.ARTICLE_ACCESS_OPEN}
								{assign var="hasArticleAccess" value=1}
							{/if}
							{include file="frontend/objects/galley_link.tpl" parent=$article publication=$publication hasAccess=$hasArticleAccess purchaseFee=$currentJournal->getData('purchaseArticleFee') purchaseCurrency=$currentJournal->getData('currency')}
						</li>
					{/foreach}
				</ul>
			{/if}
		</div>

		{call_hook name="Templates::Issue::Issue::Article"}
	</div>
</article>
