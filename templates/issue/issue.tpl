{**
 * issue.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Issue
 *
 * $Id$
 *} 
{foreach name=sections from=$publishedArticles item=section key=sectionId}
{if $section.title}<h4>{$section.title|escape}</h4>{/if}

{foreach from=$section.articles item=article}
<table width="100%">
<tr valign="top">
	<td width="70%">{$article->getArticleTitle()|strip_unsafe_html}</td>
	<td align="right" width="30%">
		{if $section.abstractsDisabled || $article->getArticleAbstract() == ""}
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
				<a href="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)|to_array:$galley->getGalleyId()}" class="file">{$galley->getGalleyLabel()|escape}</a>
				{if $subscriptionRequired && $showGalleyLinks && $restrictOnlyPdf}
					{if $article->getAccessStatus() || !$galley->isPdfGalley()}	
						<img class="accessLogo" src="{$baseUrl}/templates/images/icons/fulltext_open_medium.png">
					{else}
						<img class="accessLogo" src="{$baseUrl}/templates/images/icons/fulltext_restricted_medium.png">
					{/if}
				{/if}
			{/foreach}
			{if $subscriptionRequired && $showGalleyLinks && !$restrictOnlyPdf}
				{if $article->getAccessStatus()}
					<img class="accessLogo" src="{$baseUrl}/templates/images/icons/fulltext_open_medium.png">
				{else}
					<img class="accessLogo" src="{$baseUrl}/templates/images/icons/fulltext_restricted_medium.png">
				{/if}
			{/if}				
		{/if}
	</td>
</tr>
<tr>
	<td style="padding-left: 30px;font-style: italic;">
		{if (!$section.hideAuthor && $article->getHideAuthor() == 0) || $article->getHideAuthor() == 2}
			{foreach from=$article->getAuthors() item=author name=authorList}
				{$author->getFullName()|escape}{if !$smarty.foreach.authorList.last},{/if}
			{/foreach}
		{else}
			&nbsp;
		{/if}
	</td>
	<td align="right">{$article->getPages()|escape}</td>
</tr>
</table>
{/foreach}

{if !$smarty.foreach.sections.last}
<div class="separator"></div>
{/if}
{/foreach}
