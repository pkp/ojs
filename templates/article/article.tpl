{**
 * templates/article/article.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article View.
 *
 * Available data:
 *  $article Article The article object for the current article view
 *  $galley ArticleGalley The (optional!) galley object for the current view
 *  $galleys array The list of galleys available for this article
 *  $citationsFactory ItemIterator List of citations from this article.
 *  $subscriptionRequired boolean Whether or not a subscription is required
 *  $subscribedUser boolean Whether or not the current user is subscribed
 *  $subscribedDomain boolean Whether or not the user has subscription access by domain
 *  $showGalleyLinks boolean True iff OJS should display galley links regardless of subscription status
 *}
{strip}
	{if $galley}
		{assign var=pubObject value=$galley}
	{else}
		{assign var=pubObject value=$article}
	{/if}
	{include file="article/header.tpl"}
{/strip}

{if $galley}
	{call_hook name="Templates::Galley::displayGalley" fileId=$fileId}
{else}
	<div id="topBar">
		{if is_a($article, 'PublishedArticle')}{assign var=galleys value=$article->getGalleys()}{/if}
		{if $galleys && $subscriptionRequired && $showGalleyLinks}
			<div id="accessKey">
				<img src="{$baseUrl}/lib/pkp/templates/images/icons/fulltext_open_medium.gif" alt="{translate key="article.accessLogoOpen.altText"}" />
				{translate key="reader.openAccess"}&nbsp;
				<img src="{$baseUrl}/lib/pkp/templates/images/icons/fulltext_restricted_medium.gif" alt="{translate key="article.accessLogoRestricted.altText"}" />
				{if $purchaseArticleEnabled}
					{translate key="reader.subscriptionOrFeeAccess"}
				{else}
					{translate key="reader.subscriptionAccess"}
				{/if}
			</div>
		{/if}
	</div>
	{if $coverPagePath}
		<div id="articleCoverImage"><img src="{$coverPagePath|escape}{$coverPageFileName|escape}"{if $coverPageAltText != ''} alt="{$coverPageAltText|escape}"{else} alt="{translate key="article.coverPage.altText"}"{/if}{if $width} width="{$width|escape}"{/if}{if $height} height="{$height|escape}"{/if}/>
		</div>
	{/if}
	{call_hook name="Templates::Article::Article::ArticleCoverImage"}
	<div id="articleTitle"><h3>{$article->getLocalizedTitle()|strip_unsafe_html}</h3></div>
	<div id="authorString"><em>{$article->getAuthorString()|escape}</em></div>
	<br />
	{if $article->getLocalizedAbstract()}
		<div id="articleAbstract">
		<h4>{translate key="article.abstract"}</h4>
		<br />
		<div>{$article->getLocalizedAbstract()|strip_unsafe_html|nl2br}</div>
		<br />
		</div>
	{/if}

	{if $article->getLocalizedSubject()}
		<div id="articleSubject">
		<h4>{translate key="article.subject"}</h4>
		<br />
		<div>{$article->getLocalizedSubject()|escape}</div>
		<br />
		</div>
	{/if}

	{include file="article/galleys.tpl"}

	{include file="article/citations.tpl"}
{/if}

{include file="article/pubIds.tpl"}

{include file="article/comments.tpl"}

{include file="article/footer.tpl"}
