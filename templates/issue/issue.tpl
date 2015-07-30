{**
 * templates/issue/issue.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Issue
 *
 *}
{foreach name=sections from=$publishedArticles item=section key=sectionId}
	{if $section.title}
		<h3 class="heading_section">
			{$section.title|escape}
		</h3>
	{/if}

	{if $section.articles}
		<ul class="articles">

			{foreach from=$section.articles item=article}
				{assign var=articlePath value=$article->getBestArticleId($currentJournal)}

				<li>
					{if $article->getLocalizedFileName() && $article->getLocalizedShowCoverPage() && !$article->getHideCoverPageToc($locale)}
						<div class="cover">
							<a href="{url page="article" op="view" path=$articlePath}" class="file">
								<img src="{$coverPagePath|escape}{$article->getFileName($locale)|escape}"{if $article->getCoverPageAltText($locale) != ''} alt="{$article->getCoverPageAltText($locale)|escape}"{else} alt="{translate key="article.coverPage.altText"}"{/if}>
							</a>
						</div>
					{/if}
					{call_hook name="Templates::Issue::Issue::ArticleCoverImage"}

					{if $article->getLocalizedAbstract() == ""}
						{assign var=hasAbstract value=0}
					{else}
						{assign var=hasAbstract value=1}
					{/if}

					{assign var=articleId value=$article->getId()}
					{if (!$subscriptionRequired || $article->getAccessStatus() == $smarty.const.ARTICLE_ACCESS_OPEN || $subscribedUser || $subscribedDomain || ($subscriptionExpiryPartial && $articleExpiryPartial.$articleId))}
						{assign var=hasAccess value=1}
					{else}
						{assign var=hasAccess value=0}
					{/if}

					<div class="title">
						<a href="{url page="article" op="view" path=$articlePath}">
							{$article->getLocalizedTitle()|strip_unsafe_html}
						</a>
					</div>

					<div class="authors">
						{if (!$section.hideAuthor && $article->getHideAuthor() == $smarty.const.AUTHOR_TOC_DEFAULT) || $article->getHideAuthor() == $smarty.const.AUTHOR_TOC_SHOW}
							{foreach from=$article->getAuthors() item=author name=authorList}
								{$author->getFullName()|escape}{if !$smarty.foreach.authorList.last}{translate key="article.authorSeparator"}{/if}
							{/foreach}
						{/if}
					</div>

					<ul class="ojs_galleys">
						{if $hasAccess || ($subscriptionRequired && $showGalleyLinks)}
							{foreach from=$article->getGalleys() item=galley name=galleyList}
								{if $galley->getIsAvailable()}

									{* Determine galley type and URL op *}
									{if $galley->isPdfGalley()}
										{assign var=type value="pdf"}
									{else}
										{assign var=type value="file"}
									{/if}

									{* Get user access flag *}
									{assign var=restricted value=0}
									{if $subscriptionRequired && $showGalleyLinks && !$hasAccess && $article->getAccessStatus() != $smarty.const.ISSUE_ACCESS_OPEN}
										{if $restrictOnlyPdf && type == 'pdf'}
											{assign var=restricted value="1"}
										{elseif !$restrictOnlyPdf}
											{assign var=restricted value="1"}
										{/if}
									{/if}

									<li class="galley {$type}{if $restricted} restricted{/if}">
										<a href="{url page="article" op="view" path=$articlePath|to_array:$galley->getBestGalleyId($currentJournal)}" class="file">
											{$galley->getGalleyLabel()|escape}
										</a>
									</li>
								{/if}
							{/foreach}
						{/if}
					</ul>

					{if $article->getPages()}
						<div class="pages">
							{$article->getPages()|escape}
						</div>
					{/if}

					{call_hook name="Templates::Issue::Issue::Article"}
				</li>
			{/foreach}
		</ul><!-- .articles -->
	{/if}

{/foreach}
