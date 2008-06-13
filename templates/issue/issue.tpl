{**
 * issue.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Issue
 *
 * $Id$
 *} 
{foreach name=sections from=$publishedArticles item=section key=sectionId}
{if $section.title}<h4 class="tocSectionTitle">{$section.title|escape}</h4>{/if}

{foreach from=$section.articles item=article}
<table class="tocArticle" width="100%">
<tr valign="top">
	{if $article->getFileName($locale) && $article->getShowCoverPage($locale)}
	<td rowspan="2">
		<div class="tocArticleCoverImage">
		<a href="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)}" class="file">
		<img src="{$coverPagePath|escape}{$article->getFileName($locale)|escape}"{if $article->getCoverPageAltText($locale) != ''} alt="{$article->getCoverPageAltText($locale)|escape}"{else} alt="{translate key="article.coverPage.altText"}"{/if}/></a></div>
	</td>
	{/if}

	<td class="tocTitle">{$article->getArticleTitle()|strip_unsafe_html}</td>
	<td class="tocGalleys">
		{if $article->getArticleAbstract() == ""}
			{assign var=hasAbstract value=0}
		{else}
			{assign var=hasAbstract value=1}
		{/if}

		{assign var=articleId value=$article->getArticleId()}
		{if (!$subscriptionRequired || $article->getAccessStatus() || $subscribedUser || $subscribedDomain || ($subscriptionExpiryPartial && $articleExpiryPartial.$articleId))}
			{assign var=hasAccess value=1}
		{else}
			{assign var=hasAccess value=0}
		{/if}

		{if !$hasAccess || $hasAbstract}<a href="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)}" class="file">{if $hasAbstract}{translate key=article.abstract}{else}{translate key="article.details"}{/if}</a>{/if}

		{if $hasAccess || ($subscriptionRequired && $showGalleyLinks)}
			{foreach from=$article->getLocalizedGalleys() item=galley name=galleyList}
				<a href="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)|to_array:$galley->getBestGalleyId($currentJournal)}" class="file">{$galley->getGalleyLabel()|escape}</a>
				{if $subscriptionRequired && $showGalleyLinks && $restrictOnlyPdf}
					{if $article->getAccessStatus() || !$galley->isPdfGalley()}	
						<img class="accessLogo" src="{$baseUrl}/templates/images/icons/fulltext_open_medium.gif" alt="{translate key="article.accessLogoOpen.altText"}" />
					{else}
						<img class="accessLogo" src="{$baseUrl}/templates/images/icons/fulltext_restricted_medium.gif" alt="{translate key="article.accessLogoRestricted.altText"}" />
					{/if}
				{/if}
			{/foreach}
			{if $subscriptionRequired && $showGalleyLinks && !$restrictOnlyPdf}
				{if $article->getAccessStatus()}
					<img class="accessLogo" src="{$baseUrl}/templates/images/icons/fulltext_open_medium.gif" alt="{translate key="article.accessLogoOpen.altText"}" />
				{else}
					<img class="accessLogo" src="{$baseUrl}/templates/images/icons/fulltext_restricted_medium.gif" alt="{translate key="article.accessLogoRestricted.altText"}" />
				{/if}
			{/if}				
		{/if}
	</td>
</tr>
<tr>
	<td class="tocAuthors">
		{if (!$section.hideAuthor && $article->getHideAuthor() == 0) || $article->getHideAuthor() == 2}
			{foreach from=$article->getAuthors() item=author name=authorList}
				{$author->getFullName()|escape}{if !$smarty.foreach.authorList.last},{/if}
			{/foreach}
		{else}
			&nbsp;
		{/if}
	</td>
	<td class="tocPages">{$article->getPages()|escape}</td>
</tr>
</table>
{/foreach}

{if !$smarty.foreach.sections.last}
<div class="separator"></div>
{/if}
{/foreach}
