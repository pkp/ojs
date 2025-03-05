{**
 * templates/frontend/objects/article_details.tpl
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
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
 * @uses $article Submission This article
 * @uses $publication Publication The publication being displayed
 * @uses $firstPublication Publication The first published version of this article
 * @uses $currentPublication Publication The most recently published version of this article
 * @uses $issue Issue The issue this article is assigned to
 * @uses $section Section The journal section this article is assigned to
 * @uses $categories Category The category this article is assigned to
 * @uses $primaryGalleys array List of article galleys that are not supplementary or dependent
 * @uses $supplementaryGalleys array List of article galleys that are supplementary
 * @uses $keywords array List of keywords assigned to this article
 * @uses $pubIdPlugins Array of pubId plugins which this article may be assigned
 * @uses $licenseTerms string License terms.
 * @uses $licenseUrl string URL to license. Only assigned if license should be
 *   included with published submissions.
 * @uses $ccLicenseBadge string An image and text with details about the license
 * @uses $pubLocaleData Array of e.g. publication's locales and metadata field names to show in multiple languages.
 *
 * @hook Templates::Article::Main []
 * @hook Templates::Article::Details::Reference []
 * @hook Templates::Article::Details []
 *}

{**
 * Function useFilters: Filter text content of the metadata shown on the page
 * E.g. usage $value|useFilters:$filters
 * Filter format with params: e.g. '[funcName, param2, ...]'
 * @param string $value, Required, The value to be filtered
 * @param array|null $filters, Required, E.g. [ 'escape', ['default', ''] ]
 * @return string, filtered value
 *
 * $authorName|useFilters:['escape']
 *}

{**
 * Function wrapData: Publication's multilingual data to array for js and page
 * Filters texts using function useFilters
 * @param array $data, Required, E.g. $publication->getTitles('html')
 * @param string $switcher, Required, Switcher's name
 * @param array $filters, Optional, E.g. [ 'escape', ['default', ''] ]
 * @param string $separator, Optional but required to join array (e.g. keywords)
 * @return array, [ 'switcher' => string name, 'data' => array multilingual, 'defaultLocale' => string locale code ]
 *
 * $publication->getFullTitles('html')|wrapData:"titles":['strip_unsafe_html']
 *}

{**
 * Authors' affiliations: concat affiliation and ror icon
 * @param array $affiliationNamesWithRors, Required
 * @param string $rorIdIcon, Required
 * @param array $filters, Optional, E.g. ['escape']
 * @return array, As variable $affiliationNamesWithRors
 *}
{function concatAuthorAffiliationsWithRors}
	{foreach from=$affiliationNamesWithRors item=$namesPerLocale key=$locale}
		{foreach from=$namesPerLocale item=$nameWithRor key=$key}
			{* Affiliation name *}
			{$affiliationRor=$nameWithRor.name|useFilters:$filters}
			{* Ror *}
			{if $nameWithRor.ror}
				{capture assign="ror"}<a href="{$nameWithRor.ror|useFilters:$filters}">{$rorIdIcon}</a>{/capture}
				{$affiliationRor="{$affiliationRor}{$ror}"}
			{/if}
			{$affiliationNamesWithRors[$locale][$key]=$affiliationRor}
		{/foreach}
	{/foreach}
	{assign "affiliationNamesWithRors" value=$affiliationNamesWithRors scope="parent" nocache}
{/function}

{**
 * Switchers' listbox container
 *}
{function switcherContainer}
	<ul role="listbox" aria-label="{translate key="plugins.themes.default.languageSwitcher.ariaDescription.select"}"></ul>
{/function}

{* Language switchers' buttons text contents: locale names can be used instead of lang attribute codes by removing the line under this comment. *}
{$pubLocaleData.localeNames = $pubLocaleData.accessibility.langAttrs}

 {if !$heading}
 	{assign var="heading" value="h3"}
 {/if}
<article class="obj_article_details">

	{* Indicate if this is only a preview *}
	{if $publication->getData('status') !== PKP\submission\PKPSubmission::STATUS_PUBLISHED}
	<div class="cmp_notification notice">
		{capture assign="submissionUrl"}{url page="dashboard" op="editorial" workflowSubmissionId=$article->getId()}{/capture}
		{translate key="submission.viewingPreview" url=$submissionUrl}
	</div>
	{* Notification that this is an old version *}
	{elseif $currentPublication->getId() !== $publication->getId()}
		<div class="cmp_notification notice">
			{capture assign="latestVersionUrl"}{url page="article" op="view" path=$article->getBestId()}{/capture}
			{translate key="submission.outdatedVersion"
				datePublished=$publication->getData('datePublished')|date_format:$dateFormatShort
				urlRecentVersion=$latestVersionUrl|escape
			}
		</div>
	{/if}

	<div class="page_titles" aria-live="polite">
		{** Example usage of the language switcher, title and subtitle
		* In h1, the attribute data-pkp-switcher-data="title" and, in h2, the attribute data-pkp-switcher-data="subtitle" are used to sync the text content in elements when
		* the language is switched. 
		* The attribute data-pkp-switcher="titles" is the container for the switcher's buttons, it needs to match json's switcher-key's value, e.g. {"title":{"switcher":"titles"...
		* The function wrapData wraps publication data for the json and the tpl, e.g. $pubLocaleData.title=$publication->getTitles('html')|wrapData:"titles":['strip_unsafe_html'],
		*  $pubLocaleData's title-key needs to match data-pkp-switcher-data'-attribute's value, e.g. data-pkp-switcher-data="title". This is for to sync the data in the correct element.
		* See all the examples below.
		* The rest of the work is handled by the js code.
		*}
		{* Publication title for json *}
		{* array $data | wrapData : string $switcher : ?array $filters : ?string $separator (e.g. keywords) *}
		{$pubLocaleData.title=$publication->getTitles('html')|wrapData:"titles":['strip_unsafe_html']}
		<h1
			class="page_title"
			data-pkp-switcher-data="title"
			lang="{$pubLocaleData.accessibility.langAttrs[$pubLocaleData.title.defaultLocale]}"
		>
			{$pubLocaleData.title.data[$pubLocaleData.title.defaultLocale]}
		</h1>

		{if $publication->getSubTitles('html')}
			{* Publication subtitle for json *}
			{$pubLocaleData.subtitle=$publication->getSubTitles('html')|wrapData:"titles":['strip_unsafe_html']}
			<h2
				class="subtitle"
				data-pkp-switcher-data="subtitle"
				lang="{$pubLocaleData.accessibility.langAttrs[$pubLocaleData.subtitle.defaultLocale]}"
			>
				{$pubLocaleData.subtitle.data[$pubLocaleData.subtitle.defaultLocale]}
			</h2>
		{/if}
		{* Title and subtitle common switcher *}
		{if isset($pubLocaleData.opts.title)}
			<span aria-label="{translate key="plugins.themes.default.languageSwitcher.ariaDescription.titles"}" role="group" data-pkp-switcher="titles">{switcherContainer}</span>
		{/if}
	</div>

	{* Comma list separator for authors' affiliations and keywords *}
	{capture assign=commaListSeparator}{translate key="common.commaListSeparator"}{/capture}

	<div class="row">
		<div class="main_entry">

			{if $publication->getData('authors')}
				<section class="item authors">
					<h2 class="pkp_screen_reader">{translate key="article.authors"}</h2>
					<ul class="authors">
					{foreach from=$publication->getData('authors') item=author}
						<li aria-live="polite">
							<div>
								{* Publication author name for json *}
								{$pubLocaleData["author{$author@index}Name"]=$author|getAuthorFullNames|wrapData:"author{$author@index}":['escape']}
								{* Name *}
								<span
									class="name"
									data-pkp-switcher-data="author{$author@index}Name"
									lang="{$pubLocaleData.accessibility.langAttrs[$pubLocaleData["author{$author@index}Name"].defaultLocale]}"
								>
									{$pubLocaleData["author{$author@index}Name"].data[$pubLocaleData["author{$author@index}Name"].defaultLocale]}
								</span>
								{* Author switcher *}
								{if isset($pubLocaleData.opts.author)}
									<span aria-label="{translate key="plugins.themes.default.languageSwitcher.ariaDescription.author"}" role="group" data-pkp-switcher="author{$author@index}">{switcherContainer}</span>
								{/if}
							</div>
							{if $author->getAffiliations()}
								{* Publication author affiliations for json *}
								{concatAuthorAffiliationsWithRors affiliationNamesWithRors=$author|getAffiliationNamesWithRors rorIdIcon=$rorIdIcon filters=['escape']}
								{$pubLocaleData["author{$author@index}Affiliation"]=$affiliationNamesWithRors|wrapData:"author{$author@index}":null:$commaListSeparator}
								{* Affiliation *}
								<span class="affiliation">
									<span
										data-pkp-switcher-data="author{$author@index}Affiliation"
										lang="{$pubLocaleData.accessibility.langAttrs[$pubLocaleData["author{$author@index}Affiliation"].defaultLocale]}"
									>
										{$pubLocaleData["author{$author@index}Affiliation"].data[$pubLocaleData["author{$author@index}Affiliation"].defaultLocale]}
									</span>
								</span>
							{/if}
							{assign var=authorUserGroup value=$userGroupsById[$author->getData('userGroupId')]}
							{if $authorUserGroup->showTitle}
								<span class="userGroup">
									{$authorUserGroup->getLocalizedData('name')|escape}
								</span>
							{/if}
							{if $author->getData('orcid')}
								<span class="orcid">
									{if $author->hasVerifiedOrcid()}
										{$orcidIcon}
									{else}
										{$orcidUnauthenticatedIcon}
									{/if}
									<a href="{$author->getData('orcid')|escape}" target="_blank">
										{$author->getOrcidDisplayValue()|escape}
									</a>
								</span>
							{/if}
						</li>
					{/foreach}
					</ul>
				</section>
			{/if}

			{* DOI *}
			{assign var=doiObject value=$article->getCurrentPublication()->getData('doiObject')}
			{if $doiObject}
				{assign var="doiUrl" value=$doiObject->getData('resolvingUrl')|escape}
				<section class="item doi">
					<h2 class="label">
						{capture assign=translatedDOI}{translate key="doi.readerDisplayName"}{/capture}
						{translate key="semicolon" label=$translatedDOI}
					</h2>
					<span class="value">
						<a href="{$doiUrl}">
							{$doiUrl}
						</a>
					</span>
				</section>
			{/if}


			{* Keywords *}
			{if $publication->getData('keywords')}
				{* Publication keywords for json *}
				{$pubLocaleData.keywords=$publication->getData('keywords')|wrapData:"keywords":['escape']:$keywordSeparator}
				<section class="item keywords" aria-live="polite">
					<h2 class="label">
						{capture assign=translatedKeywords}{translate key="article.subject"}{/capture}
						{translate key="semicolon" label=$translatedKeywords}
					</h2>
					<span>
						<span
							class="value"
							lang="{$pubLocaleData.accessibility.langAttrs[$pubLocaleData.keywords.defaultLocale]}"
							data-pkp-switcher-data="keywords"
						>
							{$pubLocaleData.keywords.data[$pubLocaleData.keywords.defaultLocale]}
						</span>
						{* Keyword switcher *}
						{if isset($pubLocaleData.opts.keywords)}
							<span aria-label="{translate key="plugins.themes.default.languageSwitcher.ariaDescription.keywords"}" role="group" data-pkp-switcher="keywords">{switcherContainer}</span>
						{/if}
					</span>
				</section>
			{/if}

			{* Abstract *}
			{if $publication->getData('abstract')}
				{* Publication abstract for json *}
				{$pubLocaleData.abstract=$publication->getData('abstract')|wrapData:"abstract":['strip_unsafe_html']}
				<section class="item abstract" aria-live="polite">
					<div>
						<h2 class="label">
						{translate key="article.abstract"}
						</h2>
						{* Abstract switcher *}
						{if isset($pubLocaleData.opts.abstract)}
							<span aria-label="{translate key="plugins.themes.default.languageSwitcher.ariaDescription.abstract"}" role="group" data-pkp-switcher="abstract">{switcherContainer}</span>
						{/if}
					</div>
					<div
						data-pkp-switcher-data="abstract"
						lang="{$pubLocaleData.accessibility.langAttrs[$pubLocaleData.abstract.defaultLocale]}"
					>
						{$pubLocaleData.abstract.data[$pubLocaleData.abstract.defaultLocale]}
					</div>
				</section>
			{/if}

			{call_hook name="Templates::Article::Main"}

			{* Usage statistics chart*}
			{if $activeTheme->getOption('displayStats') != 'none'}
				{$activeTheme->displayUsageStatsGraph($article->getId())}
				<section class="item downloads_chart">
					<h2 class="label">
						{translate key="plugins.themes.default.displayStats.downloads"}
					</h2>
					<div class="value">
						<canvas class="usageStatsGraph" data-object-type="Submission" data-object-id="{$article->getId()|escape}"></canvas>
						<div class="usageStatsUnavailable" data-object-type="Submission" data-object-id="{$article->getId()|escape}">
							{translate key="plugins.themes.default.displayStats.noStats"}
						</div>
					</div>
				</section>
			{/if}

			{* Author biographies *}
			{assign var="hasBiographies" value=0}
			{foreach from=$publication->getData('authors') item=author}
				{if $author->getLocalizedData('biography')}
					{assign var="hasBiographies" value=$hasBiographies+1}
				{/if}
			{/foreach}
			{if $hasBiographies}
				<section class="item author_bios">
					<h2 class="label">
						{if $hasBiographies > 1}
							{translate key="submission.authorBiographies"}
						{else}
							{translate key="submission.authorBiography"}
						{/if}
					</h2>
					<ul class="authors">
					{foreach from=$publication->getData('authors') item=author}
						{if $author->getLocalizedData('biography')}
							<li class="sub_item">
								<div class="label">
									{if $author->getLocalizedAffiliationNamesAsString()}
										{capture assign="authorName"}{$author->getFullName()|escape}{/capture}
										{capture assign="authorAffiliations"} {$author->getLocalizedAffiliationNamesAsString(null, ', ')|escape} {/capture}
										{translate key="submission.authorWithAffiliation" name=$authorName affiliation=$authorAffiliations}
									{else}
										{$author->getFullName()|escape}
									{/if}
								</div>
								<div class="value">
									{$author->getLocalizedData('biography')|strip_unsafe_html}
								</div>
							</li>
						{/if}
					{/foreach}
					</ul>
				</section>
			{/if}

			{* References *}
			{if $parsedCitations || $publication->getData('citationsRaw')}
				<section class="item references">
					<h2 class="label">
						{translate key="submission.citations"}
					</h2>
					<div class="value">
						{if $parsedCitations}
							{foreach from=$parsedCitations item="parsedCitation"}
								<p>{$parsedCitation->getCitationWithLinks()|strip_unsafe_html} {call_hook name="Templates::Article::Details::Reference" citation=$parsedCitation}</p>
							{/foreach}
						{else}
							{$publication->getData('citationsRaw')|escape|nl2br}
						{/if}
					</div>
				</section>
			{/if}

		</div><!-- .main_entry -->

		<div class="entry_details">

			{* Article/Issue cover image *}
			{if $publication->getLocalizedData('coverImage') || ($issue && $issue->getLocalizedCoverImage())}
				<div class="item cover_image">
					<div class="sub_item">
						{if $publication->getLocalizedData('coverImage')}
							{assign var="coverImage" value=$publication->getLocalizedData('coverImage')}
							<img
								src="{$publication->getLocalizedCoverImageUrl($article->getData('contextId'))|escape}"
								alt="{$coverImage.altText|escape|default:''}"
							>
						{else}
							<a href="{url page="issue" op="view" path=$issue->getBestIssueId()}">
								<img src="{$issue->getLocalizedCoverImageUrl()|escape}" alt="{$issue->getLocalizedCoverImageAltText()|escape|default:''}">
							</a>
						{/if}
					</div>
				</div>
			{/if}

			{* Article Galleys *}
			{if $primaryGalleys}
				<div class="item galleys">
					<h2 class="pkp_screen_reader">
						{translate key="submission.downloads"}
					</h2>
					<ul class="value galleys_links">
						{foreach from=$primaryGalleys item=galley}
							<li>
								{include file="frontend/objects/galley_link.tpl" parent=$article publication=$publication galley=$galley purchaseFee=$currentJournal->getData('purchaseArticleFee') purchaseCurrency=$currentJournal->getData('currency')}
							</li>
						{/foreach}
					</ul>
				</div>
			{/if}
			{if $supplementaryGalleys}
				<div class="item galleys">
					<h3 class="pkp_screen_reader">
						{translate key="submission.additionalFiles"}
					</h3>
					<ul class="value supplementary_galleys_links">
						{foreach from=$supplementaryGalleys item=galley}
							<li>
								{include file="frontend/objects/galley_link.tpl" parent=$article publication=$publication galley=$galley isSupplementary="1"}
							</li>
						{/foreach}
					</ul>
				</div>
			{/if}

			{if $publication->getData('datePublished')}
			<div class="item published">
				<section class="sub_item">
					<h2 class="label">
						{translate key="submissions.published"}
					</h2>
					<div class="value">
						{* If this is the original version *}
						{if $firstPublication->getId() === $publication->getId()}
							<span>{$firstPublication->getData('datePublished')|date_format:$dateFormatShort}</span>
						{* If this is an updated version *}
						{else}
							<span>{translate key="submission.updatedOn" datePublished=$firstPublication->getData('datePublished')|date_format:$dateFormatShort dateUpdated=$publication->getData('datePublished')|date_format:$dateFormatShort}</span>
						{/if}
					</div>
				</section>
				{if count($article->getPublishedPublications()) > 1}
					<section class="sub_item versions">
						<h2 class="label">
							{translate key="submission.versions"}
						</h2>
						<ul class="value">
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
					</section>
				{/if}
			</div>
			{/if}

			{* Data Availability Statement *}
			{if $publication->getLocalizedData('dataAvailability')}
				<section class="item dataAvailability">
					<h2 class="label">{translate key="submission.dataAvailability"}</h2>
					{$publication->getLocalizedData('dataAvailability')|strip_unsafe_html}
				</section>
			{/if}

			{* Issue article appears in *}
			{if $issue || $section || $categories}
				<div class="item issue">

					{if $issue}
						<section class="sub_item">
							<h2 class="label">
								{translate key="issue.issue"}
							</h2>
							<div class="value">
								<a class="title" href="{url page="issue" op="view" path=$issue->getBestIssueId()}">
									{$issue->getIssueIdentification()|escape}
								</a>
							</div>
						</section>
					{/if}

					{if $section}
						<section class="sub_item">
							<h2 class="label">
								{translate key="section.section"}
							</h2>
							<div class="value">
								{$section->getLocalizedTitle()|escape}
							</div>
						</section>
					{/if}

					{if $categories}
						<section class="sub_item">
							<h2 class="label">
								{translate key="category.category"}
							</h2>
							<div class="value">
								<ul class="categories">
									{foreach from=$categories item=category}
										<li><a href="{url router=PKP\core\PKPApplication::ROUTE_PAGE page="catalog" op="category" path=$category->getPath()|escape}">{$category->getLocalizedTitle()|escape}</a></li>
									{/foreach}
								</ul>
							</div>
						</section>
					{/if}
				</div>
			{/if}

			{* PubIds (requires plugins) *}
			{foreach from=$pubIdPlugins item=pubIdPlugin}
				{if $pubIdPlugin->getPubIdType() == 'doi'}
					{continue}
				{/if}
				{assign var=pubId value=$publication->getStoredPubId($pubIdPlugin->getPubIdType())}
				{if $pubId}
					<section class="item pubid">
						<h2 class="label">
							{$pubIdPlugin->getPubIdDisplayType()|escape}
						</h2>
						<div class="value">
							{if $pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}
								<a id="pub-id::{$pubIdPlugin->getPubIdType()|escape}" href="{$pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}">
									{$pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}
								</a>
							{else}
								{$pubId|escape}
							{/if}
						</div>
					</section>
				{/if}
			{/foreach}

			{* Licensing info *}
			{if $currentContext->getLocalizedData('licenseTerms') || $publication->getData('licenseUrl')}
				<div class="item copyright">
					<h2 class="label">
						{translate key="submission.license"}
					</h2>
					{if $publication->getData('licenseUrl')}
						{if $ccLicenseBadge}
							{if $publication->getLocalizedData('copyrightHolder')}
								<p>{translate key="submission.copyrightStatement" copyrightHolder=$publication->getLocalizedData('copyrightHolder') copyrightYear=$publication->getData('copyrightYear')}</p>
							{/if}
							{$ccLicenseBadge}
						{else}
							<a href="{$publication->getData('licenseUrl')|escape}" class="copyright">
								{if $publication->getLocalizedData('copyrightHolder')}
									{translate key="submission.copyrightStatement" copyrightHolder=$publication->getLocalizedData('copyrightHolder') copyrightYear=$publication->getData('copyrightYear')}
								{else}
									{translate key="submission.license"}
								{/if}
							</a>
						{/if}
					{/if}
					{$currentContext->getLocalizedData('licenseTerms')}
				</div>
			{/if}

			{call_hook name="Templates::Article::Details"}

		</div><!-- .entry_details -->
	</div><!-- .row -->

</article>

<script type="text/javascript">
	/* Publication multilingual data to json for js
	 * Grave accent (`) had to be encoded, otherwise json parse error
	 */
	{$pubLocaleDataJson=$pubLocaleData|json_encode|replace:'`':'&#96;'|escape:'javascript':'UTF-8'}
	var pubLocaleDataJson = "{$pubLocaleDataJson}";
</script>
