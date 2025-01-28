{**
 * templates/frontend/objects/article_details.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief View of an Article which displays all details about the article.
 *  Expected to be primary object on the page.
 *
 * Many journals will want to add custom data to this object, either through
 * plugins which attach to hooks on the page or by editing the template
 * themselves. In order to facilitate this, a flexible layout markup pattern has
 * been implemented. If followed, plugins and other content can provide markup
 * in a way that will render consistently with other items on the page. This
 * pattern is used in the .main_entry column and the .entry_details column. It
 * consists of the following:
 *
 * <!-- Wrapper class which provides proper spacing between components -->
 * <div class="item">
 *     <!-- Title/value combination -->
 *     <div class="label">Abstract</div>
 *     <div class="value">Value</div>
 * </div>
 *
 * All styling should be applied by class name, so that titles may use heading
 * elements (eg, <h3>) or any element required.
 *
 * <!-- Example: component with multiple title/value combinations -->
 * <div class="item">
 *     <div class="sub_item">
 *         <div class="label">DOI</div>
 *         <div class="value">12345678</div>
 *     </div>
 *     <div class="sub_item">
 *         <div class="label">Published Date</div>
 *         <div class="value">2015-01-01</div>
 *     </div>
 * </div>
 *
 * <!-- Example: component with no title -->
 * <div class="item">
 *     <div class="value">Whatever you'd like</div>
 * </div>
 *
 * Core components are produced manually below, but can also be added via
 * plugins using the hooks provided:
 *
 * Templates::Article::Main
 * Templates::Article::Details
 *
 * @uses $article Article This article
 * @uses $publication Publication The publication being displayed
 * @uses $firstPublication Publication The first published version of this article
 * @uses $currentPublication Publication The most recently published version of this article
 * @uses $issue Issue The issue this article is assigned to
 * @uses $section Section The journal section this article is assigned to
 * @uses $primaryGalleys array List of article galleys that are not supplementary or dependent
 * @uses $supplementaryGalleys array List of article galleys that are supplementary
 * @uses $keywords array List of keywords assigned to this article
 * @uses $pubIdPlugins Array of pubId plugins which this article may be assigned
 * @uses $licenseTerms string License terms.
 * @uses $licenseUrl string URL to license. Only assigned if license should be
 *   included with published articles.
 * @uses $ccLicenseBadge string An image and text with details about the license
 *}
<section class="col-md-8 article-page">
	<header class="article-page__header">
    {* Notification that this is an old version *}
		{if $currentPublication->getId() !== $publication->getId()}
		<div class="article-page__alert" role="alert">
			{capture assign="latestVersionUrl"}{url page="article" op="view" path=$article->getBestId()}{/capture}
			{translate key="submission.outdatedVersion"
				datePublished=$publication->getData('datePublished')|date_format:$dateFormatShort
				urlRecentVersion=$latestVersionUrl|escape
			}
		</div>
		{/if}

		{if $section}
			<p class="article-page__meta">{$section->getLocalizedTitle()|escape}</p>
		{else}
			<p class="article-page__meta">{translate key="article.article"}</p>
		{/if}

		<p class="article-page__meta">
			<a href="{url page="issue" op="view" path=$issue->getBestIssueId()}">{$issue->getIssueIdentification()|escape}</a>
		</p>

		<h1 class="article-page__title">
			<span>{$publication->getLocalizedFullTitle(null, 'html')|strip_unsafe_html}</span>
		</h1>

		{* authors list *}
		{if $publication->getData('authors')}
			{assign var="authors" value=$publication->getData('authors')->toArray()|array_values}
			<div class="article-page__meta">
				<ul class="authors-string">
					{foreach from=$authors item=authorString key=authorStringKey}
						{strip}
							<li class="authors-string__item">
								{capture}
									{if $authorString->getLocalizedAffiliation() || $authorString->getLocalizedBiography()}
										{assign var=authorInfo value=true}
									{else}
										{assign var=authorInfo value=false}
									{/if}
								{/capture}
								{if $authorInfo}
									<a class="author-string__href" href="#author-{$authorStringKey+1}">
										<span>{$authorString->getFullName()|escape}</span>
										<sup class="author-symbol author-plus">&ThinSpace;&rtrif;</sup>
										<sup class="author-symbol author-minus hidden">&ThinSpace;&dtrif;</sup>
									</a>
									{else}
									<span class="author-string_href-none">
										<span>{$authorString->getFullName()|escape}</span>
									</span>
								{/if}
								{if $authorString->getOrcid()}
									<a class="orcidImage img-wrapper" href="{$authorString->getOrcid()|escape}">
										{if $orcidIcon}
											{$orcidIcon}
										{else}
											<img src="{$baseUrl}/{$orcidImageUrl}">
										{/if}
									</a>
								{/if}
							</li>
						{/strip}
					{/foreach}
				</ul>
			</div>
			{* Authors *}
			{assign var="authorCount" value=$publication->getData('authors')|@count}
			{assign var="authorBioIndex" value=0}
			<div class="article-page__meta">
				<div class="article-details__authors">
					{foreach from=$authors item=author key=authorKey}
						<div class="article-details__author hidden" id="author-{$authorKey+1}">
							{if $author->getLocalizedAffiliation()}
								<div class="article-details__author-affiliation">
									{$author->getLocalizedAffiliation()|escape}
									{if $author->getData('rorId')}
										<a class="rorImage" href="{$author->getData('rorId')|escape}">{$rorIdIcon}</a>
									{/if}
								</div>
							{/if}
							{if $author->getOrcid()}
								<div class="article-details__author-orcid">
									<a href="{$author->getOrcid()|escape}" target="_blank">
										{$orcidIcon}
										{$author->getOrcid()|escape}
									</a>
								</div>
							{/if}
							{if $author->getLocalizedBiography()}
								<br/>
								<a class="modal-trigger" href="#modalAuthorBio-{$authorKey+1}" data-bs-toggle="modal"
								   data-target="#modalAuthorBio-{$authorKey+1}">
									{translate key="plugins.themes.immersion.article.biography"}
								</a>
								{* author's biography *}
								<div class="modal fade bio-modal" id="modalAuthorBio-{$authorKey+1}" tabindex="-1"
								     role="dialog">
									<div class="modal-dialog" role="document">
										<div class="modal-content">
											<button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
												<span aria-hidden="true">&times;</span>
											</button>
											<div class="modal-body">
												<h2 class="visually-hidden">{translate key="submission.authorBiography"}</h2>
												{$author->getLocalizedBiography()|strip_unsafe_html}
											</div>
										</div>
									</div>
								</div>
							{/if}
						</div>
					{/foreach}
				</div>
			</div>
		{/if}
	</header>

	{* Article Galleys *}
	{if $primaryGalleys || $supplementaryGalleys}
		<div class="article-page__galleys">
			{if $primaryGalleys}
				<ul class="list-galleys primary-galleys">
					{foreach from=$primaryGalleys item=galley}
						<li>
							{include file="frontend/objects/galley_link.tpl" parent=$article publication=$publication galley=$galley purchaseFee=$currentJournal->getData('purchaseArticleFee') purchaseCurrency=$currentJournal->getData('currency')}
						</li>
					{/foreach}
				</ul>
			{/if}
			{if $supplementaryGalleys}
				<ul class="list-galleys supplementary-galleys">
					{foreach from=$supplementaryGalleys item=galley}
						<li>
							{include file="frontend/objects/galley_link.tpl" parent=$article publication=$publication galley=$galley isSupplementary="1"}
						</li>
					{/foreach}
				</ul>
			{/if}
		</div>
	{/if}

	<div class="article-page__meta">

		<dl>
			{* Pub IDs, including DOI *}
			{foreach from=$pubIdPlugins item=pubIdPlugin}
				{assign var=pubId value=$article->getStoredPubId($pubIdPlugin->getPubIdType())}
				{if $pubId}
					{assign var="pubIdUrl" value=$pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}
					<dt>
						{$pubIdPlugin->getPubIdDisplayType()|escape}
					</dt>
					<dd>
						{if $pubIdUrl}
							<a id="pub-id::{$pubIdPlugin->getPubIdType()|escape}"
							   href="{$pubIdUrl}">
								{$pubIdUrl}
							</a>
						{else}
							{$pubId|escape}
						{/if}
					</dd>
				{/if}
			{/foreach}
			{if $article->getDateSubmitted()}
				<dt>
					{translate key="submissions.submitted"}
				</dt>
				<dd>
					{$article->getDateSubmitted()|escape|date_format:$dateFormatLong}
				</dd>
			{/if}

			{if $publication->getData('datePublished')}
				<dt>
					{translate key="submissions.published"}
				</dt>
				<dd>
					{* If this is the original version *}
					{if $firstPublication->getID() === $publication->getId()}
						{$firstPublication->getData('datePublished')|date_format:$dateFormatShort}
					{* If this is an updated version *}
					{else}
						{translate key="submission.updatedOn" datePublished=$firstPublication->getData('datePublished')|date_format:$dateFormatShort dateUpdated=$publication->getData('datePublished')|date_format:$dateFormatShort}
					{/if}
				</dd>
				{if count($article->getPublishedPublications()) > 1}
					<dt>
						{translate key="submission.versions"}
					</dt>
					<dd>
						<ul class="article-page__versions">
							{foreach from=array_reverse($article->getPublishedPublications()) item=iPublication}
								{capture assign="name"}{translate key="submission.versionIdentity" datePublished=$iPublication->getData('datePublished')|date_format:$dateFormatShort version=$iPublication->getData('version')}{/capture}
								<li>
									{if $iPublication->getId() === $publication->getId()}
										{$name}
									{elseif $iPublication->getId() === $currentPublication->getId()}
										<a href="{url page="article" op="view" path=$article->getBestId()}">{$name}</a>
									{else}
										<a href="{url page="article" op="view" path=$article->getBestId()|to_array:"version":$iPublication->getId()}">{$name}</a>
									{/if}
								</li>
							{/foreach}
						</ul>
					</dd>
				{/if}
			{/if}

		</dl>
	</div><!-- .article-page__meta-->

	{* Abstract *}
	{if $publication->getLocalizedData('abstract')}
		<h3 class="label">{translate key="article.abstract"}</h3>
		{$publication->getLocalizedData('abstract')|strip_unsafe_html}
	{/if}

	{* References *}
	{if $parsedCitations || $publication->getData('citationsRaw')}
		<h3 class="label">
			{translate key="submission.citations"}
		</h3>
		{if $parsedCitations}
			<ol class="references">
				{foreach from=$parsedCitations item="parsedCitation"}
					<li>{$parsedCitation->getCitationWithLinks()|strip_unsafe_html} {call_hook name="Templates::Article::Details::Reference" citation=$parsedCitation}</li>
				{/foreach}
			</ol>
		{else}
			<div class="references">
				{$publication->getData('citationsRaw')|escape|nl2br}
			</div>
		{/if}
	{/if}

	{* Hook for plugins under the main block, like Recommend Articles by Author *}
	{call_hook name="Templates::Article::Main"}

	{* Usage statistics chart*}
	{if $activeTheme->getOption('displayStats') != 'none'}
		{$activeTheme->displayUsageStatsGraph($article->getId())}
		<section class="item downloads_chart">
			<h2 class="label">
				{translate key="plugins.themes.immersion.displayStats.downloads"}
			</h2>
			<div class="value">
				<canvas class="usageStatsGraph" data-object-type="Submission" data-object-id="{$article->getId()|escape}"></canvas>
				<div class="usageStatsUnavailable" data-object-type="Submission" data-object-id="{$article->getId()|escape}">
					{translate key="plugins.themes.immersion.displayStats.noStats"}
				</div>
			</div>
		</section>
	{/if}
</section>


<aside class="col-md-4 offset-lg-1 col-lg-3 article-sidebar">

	{* Article/Issue cover image *}
	{if $publication->getLocalizedData('coverImage') || ($issue && $issue->getLocalizedCoverImage())}
		<h2 class="visually-hidden">{translate key="plugins.themes.immersion.article.figure"}</h2>
		<figure>
			{if $publication->getLocalizedData('coverImage')}
				<img
					class="img-fluid"
					src="{$publication->getLocalizedCoverImageUrl($article->getData('contextId'))|escape}"
					alt="{$coverImage.altText|escape|default:''}"
				>
			{else}
				<a href="{url page="issue" op="view" path=$issue->getBestIssueId()}">
					<img
						class="img-fluid"
					  src="{$issue->getLocalizedCoverImageUrl()|escape}"
						alt="{$issue->getLocalizedCoverImageAltText()|escape|default:''}"
					>
				</a>
			{/if}
		</figure>
	{/if}

	{* Display other versions *}
	{if $publication->getData('datePublished')}
		{if count($article->getPublishedPublications()) > 1}
		<h2 class="article-side__title">{translate key="submission.versions"}</h2>
		<ul>
		{foreach from=array_reverse($article->getPublishedPublications()) item=iPublication}
			{capture assign="name"}{translate key="submission.versionIdentity" datePublished=$iPublication->getData('datePublished')|date_format:$dateFormatShort version=$iPublication->getData('version')}{/capture}
			<li>
				{if $iPublication->getId() === $publication->getId()}
					{$name}
				{elseif $iPublication->getId() === $currentPublication->getId()}
					<a href="{url page="article" op="view" path=$article->getBestId()}">{$name}</a>
				{else}
					<a href="{url page="article" op="view" path=$article->getBestId()|to_array:"version":$iPublication->getId()}">{$name}</a>
				{/if}
			</li>
		{/foreach}
		</ul>
		{/if}
	{/if}

	{* Keywords *}
	{if !empty($publication->getLocalizedData('keywords'))}
		<h2 class="article-side__title">{translate key="article.subject"}</h2>
		<ul>
			{foreach name=keywords from=$publication->getLocalizedData('keywords') item=keyword}
				<li>{$keyword|escape}</li>
			{/foreach}
		</ul>
	{/if}

	{* Display categories *}
	{if $categories}
		<h2 class="article-side__title">{translate key="category.category"}</h2>
		<ul>
			{foreach from=$categories item=category}
				<li><a href="{url router=$smarty.const.ROUTE_PAGE page="catalog" op="category" path=$category->getPath()|escape}">{$category->getLocalizedTitle()|escape}</a></li>
			{/foreach}
		</ul>
	{/if}

	{* Licensing info *}
	{assign 'licenseTerms' $currentContext->getLocalizedData('licenseTerms')}
	{assign 'copyrightHolder' $publication->getLocalizedData('copyrightHolder')}
	{* overwriting deprecated variables *}
	{assign 'licenseUrl' $publication->getData('licenseUrl')}
	{assign 'copyrightYear' $publication->getData('copyrightYear')}

	{if $licenseTerms || $licenseUrl}
		<div class="copyright-info">
			{if $licenseUrl}
				{if $ccLicenseBadge}
					{if $copyrightHolder}
						<p>{translate key="submission.copyrightStatement" copyrightHolder=$copyrightHolder|escape copyrightYear=$copyrightYear|escape}</p>
					{/if}
					{$ccLicenseBadge}
				{else}
					<a href="{$licenseUrl|escape}" class="copyright">
						{if $copyrightHolder}
							{translate key="submission.copyrightStatement" copyrightHolder=$copyrightHolder|escape copyrightYear=$copyrightYear|escape}
						{else}
							{translate key="submission.license"}
						{/if}
					</a>
				{/if}
			{/if}

			{* License terms modal. Show only if license is absent *}
			{if $licenseTerms && !$licenseUrl}
				<a class="copyright-notice__modal" data-bs-toggle="modal" data-target="#copyrightModal">
					{translate key="about.copyrightNotice"}
				</a>
				<div class="modal fade" id="copyrightModal" tabindex="-1" role="dialog"
				     aria-labelledby="copyrightModalTitle" aria-hidden="true">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<h5 class="modal-title"
								    id="copyrightModalTitle">{translate key="about.copyrightNotice"}</h5>
								<button type="button" class="close" data-dismiss="modal" aria-label="Close">
									<span aria-hidden="true">&times;</span>
								</button>
							</div>
							<div class="modal-body">
								{$licenseTerms|strip_unsafe_html}
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-primary"
								        data-dismiss="modal">{translate key="common.close"}</button>
							</div>
						</div>
					</div>
				</div>
			{/if}
		</div>
	{/if}
	{call_hook name="Templates::Article::Details"}

</aside>
