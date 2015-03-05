{**
 * templates/article/galleys.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Galley list in article view.
 *
 * Available data:
 *  $article Article The article object for the current article view
 *  $galley ArticleGalley The (optional!) galley object for the current article
 *  $galleys array The list of galleys available for this article
 *  $subscriptionRequired boolean Whether or not a subscription is required
 *  $subscribedUser boolean Whether or not the current user is subscribed
 *  $subscribedDomain boolean Whether or not the user has subscription access by domain
 *  $showGalleyLinks boolean True iff OJS should display galley links regardless of subscription status
 *}
{if $galleys}
	<div id="articleFullText">
	<h4>{translate key="reader.fullText"}</h4>

	{if (!$subscriptionRequired || $article->getAccessStatus() == $smarty.const.ARTICLE_ACCESS_OPEN || $subscribedUser || $subscribedDomain)}
		{assign var=hasAccess value=1}
	{else}
		{assign var=hasAccess value=0}
	{/if}

	{if $hasAccess || ($subscriptionRequired && $showGalleyLinks)}
		{foreach from=$article->getGalleys() item=galley name=galleyList}{if $galley->getIsAvailable()}
			<a href="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)|to_array:$galley->getBestGalleyId($currentJournal)}" class="file" target="_parent">{$galley->getGalleyLabel()|escape}</a>
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
	{else}
		&nbsp;<a href="{url page="about" op="subscriptions"}" target="_parent">{translate key="reader.subscribersOnly"}</a>
	{/if}
	</div>
{/if}
