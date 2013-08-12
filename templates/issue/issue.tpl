{**
 * templates/issue/issue.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Issue
 *
 *}
{foreach name=sections from=$publishedArticles item=section key=sectionId}
	{if $section.title}<h4 class="tocSectionTitle">{$section.title|escape}</h4>{/if}

	{foreach from=$section.articles item=article}
		{assign var=articlePath value=$article->getBestArticleId($currentJournal)}

		<div class="tocArticle">
			{if $article->getLocalizedFileName() && $article->getLocalizedShowCoverPage() && !$article->getHideCoverPageToc($locale)}
				<div class="tocArticleCoverImage">
					<a href="{url page="article" op="view" path=$articlePath}" class="file">
					<img src="{$coverPagePath|escape}{$article->getFileName($locale)|escape}"{if $article->getCoverPageAltText($locale) != ''} alt="{$article->getCoverPageAltText($locale)|escape}"{else} alt="{translate key="article.coverPage.altText"}"{/if}/></a>
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

			<div class="tocTitle">{if !$hasAccess || $hasAbstract}<a href="{url page="article" op="view" path=$articlePath}">{$article->getLocalizedTitle()|strip_unsafe_html}</a>{else}{$article->getLocalizedTitle()|strip_unsafe_html}{/if}</div>
			<div class="tocAuthors">
				{if (!$section.hideAuthor && $article->getHideAuthor() == $smarty.const.AUTHOR_TOC_DEFAULT) || $article->getHideAuthor() == $smarty.const.AUTHOR_TOC_SHOW}
						{foreach from=$article->getAuthors() item=author name=authorList}
							{$author->getFullName()|escape}{if !$smarty.foreach.authorList.last},{/if}
						{/foreach}
				{/if}

				<span class="tocGalleys">
					{if $hasAccess || ($subscriptionRequired && $showGalleyLinks)}
						{foreach from=$article->getGalleys() item=galley name=galleyList}{if $galley->getIsAvailable()}
							<a href="{url page="article" op="view" path=$articlePath|to_array:$galley->getBestGalleyId($currentJournal)}" class="file">{$galley->getGalleyLabel()|escape}</a>
							{if $subscriptionRequired && $showGalleyLinks && $restrictOnlyPdf}
								{if $article->getAccessStatus() == $smarty.const.ARTICLE_ACCESS_OPEN || !$galley->isPdfGalley()}
									<img class="accessLogo" src="{$baseUrl}/lib/pkp/templates/images/icons/fulltext_open_medium.gif" alt="{translate key="article.accessLogoOpen.altText"}" />
								{else}
									<img class="accessLogo" src="{$baseUrl}/lib/pkp/templates/images/icons/fulltext_restricted_medium.gif" alt="{translate key="article.accessLogoRestricted.altText"}" />
								{/if}
							{/if}
						{/if}{/foreach}
						{if $subscriptionRequired && $showGalleyLinks && !$restrictOnlyPdf}
							{if $article->getAccessStatus() == $smarty.const.ARTICLE_ACCESS_OPEN}
								<img class="accessLogo" src="{$baseUrl}/lib/pkp/templates/images/icons/fulltext_open_medium.gif" alt="{translate key="article.accessLogoOpen.altText"}" />
							{else}
								<img class="accessLogo" src="{$baseUrl}/lib/pkp/templates/images/icons/fulltext_restricted_medium.gif" alt="{translate key="article.accessLogoRestricted.altText"}" />
							{/if}
						{/if}
					{/if}
				</span>
			</div>

			<div class="tocPages">{$article->getPages()|escape}</div>
		</div><br /><br />
	{call_hook name="Templates::Issue::Issue::Article"}
	{/foreach}
{/foreach}

