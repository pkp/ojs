{**
 * section.tpl
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Issue Archive.
 *
 * $Id: archive.tpl,v 1.24.2.1 2009/04/08 19:43:32 asmecher Exp $
 *}
{assign var="pageTitle" value="$SectionTitle"}
{include file="common/header.tpl"}

{foreach name=articles from=$publishedArticles item=article}
	{assign var=articlePath value=$article->getBestArticleId($currentJournal)}
	{assign var=issue2 value=$issueDao->getIssueById($article->getIssueId())}
	{assign var=articleId value=$article->getArticleId()}


	{if $article->getFileName($locale) && $article->getShowCoverPage($locale) && !$article->getHideCoverPageToc($locale)}
		<a href="{url page="article" op="view" path=$articlePath}" class="file">
		<img src="{$coverPagePath|escape}{$article->getFileName($locale)|escape}"{if $article->getCoverPageAltText($locale) != ''} alt="{$article->getCoverPageAltText($locale)|escape}"{else} alt="{translate key="article.coverPage.altText"}"{/if}/></a></div>
	{/if}

	{if $article->getArticleAbstract() == ""}
		{assign var=hasAbstract value=0}
	{else}
		{assign var=hasAbstract value=1}
	{/if}

	{if (!$subscriptionRequired || $article->getAccessStatus() || $subscribedUser || $subscribedDomain || ($subscriptionExpiryPartial && $articleExpiryPartial.$articleId))}
		{assign var=hasAccess value=1}
	{else}
		{assign var=hasAccess value=0}
	{/if}

	 <div class="toc-title">{if !$hasAccess || $hasAbstract}<a href="{url page="article" op="view" path=$articlePath}">{$article->getArticleTitle()|strip_unsafe_html}</a>{else}{$article->getArticleTitle()|strip_unsafe_html}{/if}</div>

	<div class="toc-date">{$article->getDatePublished()|date_format:"%B %e, %Y"}. Vol. {$issue2->getVolume()}({$issue2->getNumber()}){if $article->getPages()|escape}, pp.{$article->getPages()|escape}{/if}

</div>

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
		{if $hasAccess || ($subscriptionRequired && $showGalleyLinks)}
			{foreach from=$article->getLocalizedGalleys() item=galley name=galleyList}
				<a href="{url page="article" op="view" path=$articlePath|to_array:$galley->getBestGalleyId($currentJournal)}" class="file">{$galley->getGalleyLabel()|escape}</a>
				{if $subscriptionRequired && $showGalleyLinks && $restrictOnlyPdf}
					{if $article->getAccessStatus() || !$galley->isPdfGalley()}	
						<img class="accessLogo" src="{$baseUrl}/templates/images/icons/fulltext_open_medium.gif" alt="{translate key="article.accessLogoOpen.altText"}" />
					{else}
						<img class="accessLogo" src="{$baseUrl}/templates/images/icons/fulltext_restricted_medium.gif" alt="{translate key="article.accessLogoRestricted.altText"}" />
					{/if}
				{/if}
			{/foreach}
		{/if}
		{if $subscriptionRequired && $showGalleyLinks && !$restrictOnlyPdf}
			{if $article->getAccessStatus()}
				<img class="accessLogo" src="{$baseUrl}/templates/images/icons/fulltext_open_medium.gif" alt="{translate key="article.accessLogoOpen.altText"}" />
			{else}
				<img class="accessLogo" src="{$baseUrl}/templates/images/icons/fulltext_restricted_medium.gif" alt="{translate key="article.accessLogoRestricted.altText"}" />
			{/if}
		{/if}
		{if $CommentDAO->attributedCommentsExistForArticle($article->getArticleId())}
			{if $CommentDAO->attributedCommentsExistForArticle($article->getArticleId()) == 1}
				<a href="{$baseUrl}/comment/view/{$article->getArticleId()}/0" class="file">Comment ({$CommentDAO->attributedCommentsExistForArticle($article->getArticleId())})</a>
			{else}
				<a href="{$baseUrl}/comment/view/{$article->getArticleId()}/0" class="file">Comments ({$CommentDAO->attributedCommentsExistForArticle($article->getArticleId())})</a>
			{/if}
		{else}
		<a href="{$baseUrl}/comment/view/{$article->getArticleId()}/0" class="file">Add a comment</a>
		{/if}
	</div>

	{if !$smarty.foreach.sections.last}
	{/if}
{/foreach}

{include file="common/footer.tpl"}
