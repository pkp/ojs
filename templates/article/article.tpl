{**
 * article.tpl
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article View.
 *
 * $Id$
 *}
{include file="article/header.tpl"}

{if $galley}
	{if $galley->isHTMLGalley()}
		<div id="topBar">
			<div id="articleFontSize">
				{translate key="article.fontSize"}:&nbsp;
				<a href="#" onclick="setFontSize('{translate|escape:"jsparam" key="article.fontSize.small.altText"}');" class="icon">{icon path="lib/pkp/templates/images/icons/" name="font_small"}</a>&nbsp;
				<a href="#" onclick="setFontSize('{translate|escape:"jsparam" key="article.fontSize.medium.altText"}');" class="icon">{icon path="lib/pkp/templates/images/icons/" name="font_medium"}</a>&nbsp;
				<a href="#" onclick="setFontSize('{translate|escape:"jsparam" key="article.fontSize.large.altText"}');" class="icon">{icon path="lib/pkp/templates/images/icons/" name="font_large"}</a>
			</div>
		</div>
		{$galley->getHTMLContents()}
	{/if}
{else}
	<div id="topBar">
		{assign var=galleys value=$article->getLocalizedGalleys()}
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
		<div id="articleFontSize">
				{translate key="article.fontSize"}:&nbsp;
			<a href="#" onclick="setFontSize('{translate|escape:"jsparam" key="article.fontSize.small.altText"}');" class="icon">{icon path="lib/pkp/templates/images/icons/" name="font_small"}</a>&nbsp;
			<a href="#" onclick="setFontSize('{translate|escape:"jsparam" key="article.fontSize.medium.altText"}');" class="icon">{icon path="lib/pkp/templates/images/icons/" name="font_medium"}</a>&nbsp;
			<a href="#" onclick="setFontSize('{translate|escape:"jsparam" key="article.fontSize.large.altText"}');" class="icon">{icon path="lib/pkp/templates/images/icons/" name="font_large"}</a>
		</div>
	</div>
	{if $coverPagePath}
		<div id="articleCoverImage"><img src="{$coverPagePath|escape}{$coverPageFileName|escape}"{if $coverPageAltText != ''} alt="{$coverPageAltText|escape}"{else} alt="{translate key="article.coverPage.altText"}"{/if}{if $width} width="{$width|escape}"{/if}{if $height} height="{$height|escape}"{/if}/>
		</div>
	{/if}
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

	{if $article->getCitations()}
		<h4>{translate key="article.citations"}</h4>
		<br />
		<div>{$article->getCitations()|strip_unsafe_html|nl2br}</div>
		<br />
	{/if}

	{if (!$subscriptionRequired || $article->getAccessStatus() == $smarty.const.ARTICLE_ACCESS_OPEN || $subscribedUser || $subscribedDomain)}
		{assign var=hasAccess value=1}
	{else}
		{assign var=hasAccess value=0}
	{/if}
	
	{if $galleys}
		{translate key="reader.fullText"}
		{if $hasAccess || ($subscriptionRequired && $showGalleyLinks)}
			{foreach from=$article->getLocalizedGalleys() item=galley name=galleyList}
				<a href="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)|to_array:$galley->getBestGalleyId($currentJournal)}" class="file" target="_parent">{$galley->getGalleyLabel()|escape}</a>
				{if $subscriptionRequired && $showGalleyLinks && $restrictOnlyPdf}
					{if $article->getAccessStatus() == $smarty.const.ARTICLE_ACCESS_OPEN || !$galley->isPdfGalley()}	
						<img class="accessLogo" src="{$baseUrl}/lib/pkp/templates/images/icons/fulltext_open_medium.gif" alt="{translate key="article.accessLogoOpen.altText"}" />
					{else}
						<img class="accessLogo" src="{$baseUrl}/lib/pkp/templates/images/icons/fulltext_restricted_medium.gif" alt="{translate key="article.accessLogoRestricted.altText"}" />
					{/if}
				{/if}
			{/foreach}
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
	{/if}
{/if}

{include file="article/comments.tpl"}

{include file="article/footer.tpl"}
