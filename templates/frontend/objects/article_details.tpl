{**
 * templates/frontend/objects/article_details.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
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
 * @uses $issue Issue The issue this article is assigned to
 * @uses $section Section The journal section this article is assigned to
 * @uses $keywords array List of keywords assigned to this article
 * @uses $pubIdPlugins Array of pubId plugins which this article may be assigned
 * @uses $citationPlugins Array of citation format plugins
 * @uses $copyright string Copyright notice. Only assigned if statement should
 *   be included with published articles.
 * @uses $copyrightHolder string Name of copyright holder
 * @uses $copyrightYear string Year of copyright
 * @uses $licenseUrl string URL to license. Only assigned if license should be
 *   included with published articles.
 * @uses $ccLicenseBadge string An image and text with details about the license
 *}
<article class="obj_article_details">

	{* Notification if there is a more recent version of this article *}
	{if $versioningEnabled && $isPreviousRevision}
		<div class='cmp_notification'>
			{translate key="submission.versioning.currentVersion"}: 
			{assign var=newVersionLink value=$article->getBestArticleId($currentJournal)}
			<a href="{url op="view" path=$newVersionLink escape=false}">{$latestTitle}</a>
		</div>
	{/if}

	<h1 class="page_title">
		{$article->getLocalizedTitle()|escape}
	</h1>

	{if $article->getLocalizedSubtitle()}
		<h2 class="subtitle">
			{$article->getLocalizedSubtitle()|escape}
		</h2>
	{/if}

	<div class="row">
		<div class="main_entry">

			{if $article->getAuthors(false, $submissionRevision)}
				<ul class="item authors">
					{foreach from=$article->getAuthors(false, $submissionRevision) item=author}
						<li>
							<span class="name">
								{$author->getFullName()|escape}
							</span>
							{if $author->getLocalizedAffiliation()}
								<span class="affiliation">
									{$author->getLocalizedAffiliation()|escape}
								</span>
							{/if}
							{if $author->getOrcid()}
								<span class="orcid">
									{$orcidIcon}
									<a href="{$author->getOrcid()|escape}" target="_blank">
										{$author->getOrcid()|escape}
									</a>
								</span>
							{/if}
						</li>
					{/foreach}
				</ul>
			{/if}

			{* DOI (requires plugin) *}
			{foreach from=$pubIdPlugins item=pubIdPlugin}
				{if $pubIdPlugin->getPubIdType() != 'doi'}
					{php}continue;{/php}
				{/if}
				{if $issue->getPublished()}
					{assign var=pubId value=$article->getStoredPubId($pubIdPlugin->getPubIdType())}
				{else}
					{assign var=pubId value=$pubIdPlugin->getPubId($article)}{* Preview pubId *}
				{/if}
				{if $pubId}
					{assign var="doiUrl" value=$pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}
					<div class="item doi">
						<span class="label">
							{translate key="plugins.pubIds.doi.readerDisplayName"}
						</span>
						<span class="value">
							<a href="{$doiUrl}">
								{$doiUrl}
							</a>
						</span>
					</div>
				{/if}
			{/foreach}

			{* Abstract *}
			{if $article->getLocalizedAbstract()}
				<div class="item abstract">
					<h3 class="label">{translate key="article.abstract"}</h3>
					{$article->getLocalizedAbstract()|strip_unsafe_html|nl2br}
				</div>
			{/if}

			{call_hook name="Templates::Article::Main"}

			{* Author biographies *}
			{assign var="hasBiographies" value=0}
			{foreach from=$article->getAuthors() item=author}
				{if $author->getLocalizedBiography()}
					{assign var="hasBiographies" value=$hasBiographies+1}
				{/if}
			{/foreach}
			{if $hasBiographies}
				<div class="item author_bios">
					<h3 class="label">
						{if $hasBiographies > 1}
							{translate key="submission.authorBiographies"}
						{else}
							{translate key="submission.authorBiography"}
						{/if}
					</h3>
					{foreach from=$article->getAuthors() item=author}
						{if $author->getLocalizedBiography()}
							<div class="sub_item">
								<div class="label">
									{if $author->getLocalizedAffiliation()}
										{capture assign="authorName"}{$author->getFullName()|escape}{/capture}
										{capture assign="authorAffiliation"}<span class="affiliation">{$author->getLocalizedAffiliation()|escape}</span>{/capture}
										{translate key="submission.authorWithAffiliation" name=$authorName affiliation=$authorAffiliation}
									{else}
										{$author->getFullName()|escape}
									{/if}
								</div>
								<div class="value">
									{$author->getLocalizedBiography()|strip_unsafe_html}
								</div>
							</div>
						{/if}
					{/foreach}
				</div>
			{/if}

			{* References *}
			{if $article->getCitations()}
				<div class="item references">
					<h3 class="label">
						{translate key="submission.citations"}
					</h3>
					<div class="value">
						{$article->getCitations()|nl2br}
					</div>
				</div>
			{/if}

		</div><!-- .main_entry -->

		<div class="entry_details">

			{* Article/Issue cover image *}
			{if $article->getLocalizedCoverImage() || $issue->getLocalizedCoverImage()}
				<div class="item cover_image">
					<div class="sub_item">
						{if $article->getLocalizedCoverImage()}
							<img src="{$article->getLocalizedCoverImageUrl()|escape}"{if $article->getLocalizedCoverImageAltText()} alt="{$article->getLocalizedCoverImageAltText()|escape}"{/if}>
						{else}
							<a href="{url page="issue" op="view" path=$issue->getBestIssueId()}">
								<img src="{$issue->getLocalizedCoverImageUrl()|escape}"{if $issue->getLocalizedCoverImageAltText()} alt="{$issue->getLocalizedCoverImageAltText()|escape}"{/if}>
							</a>
						{/if}
					</div>
				</div>
			{/if}

			{* Article Galleys *}
			{assign var=hasRevisions value=false}
			{assign var=galleys value=$article->getGalleys()}
			{if $galleys}
				<div class="item galleys">
					<ul class="value galleys_links">
						{foreach from=$galleys item=galley}
							{assign var=galleyId value=$galley->getId()}
							{assign var=otherRevisions value=$galley->getOtherRevisions($galley->getFileId())} 
							{if $otherRevisions}
								{* variable used later to decide whether to display old revisions or not *}
								{assign var=hasRevisions value=true}
							{/if}
							<li>
								{include file="frontend/objects/galley_link.tpl" parent=$article galley=$galley}
							</li>

						{/foreach}
					</ul>
				</div>
			{/if}

			{if $article->getDatePublished()}
				<div class="item published">
					<div class="label">
						{if $versioningEnabled}
							{translate key="submissions.firstPublished"}
						{else}
							{translate key="submissions.published"}
						{/if}
					</div>
					<div class="value">
						{$article->getDatePublished()|date_format:$dateFormatShort}
					</div>
				</div>
			{/if}

			{* Display version history of each galley *}
			{if $versioningEnabled && $hasRevisions}
				{foreach from=$galleys item=galley}
					{include file="frontend/objects/file_revisions.tpl" parent=$article galley=$galley} 
				{/foreach}
			{/if}

			{* Citation formats *}
			{if $citationPlugins|@count}
				<div class="item citation_formats">
					{* Output the first citation format *}
					{foreach from=$citationPlugins name="citationPlugins" item="citationPlugin"}
						<div class="sub_item citation_display">
							<div class="label">
								{translate key="submission.howToCite"}
							</div>
							<div id="citationOutput" class="value">
								{$citationPlugin->fetchCitation($article, $issue, $currentContext)}
							</div>
						</div>
						{php}break;{/php}
					{/foreach}

					{* Output list of all citation formats *}
					<div class="sub_item citation_format_options">
						<div class="label">
							{translate key="submission.howToCite.citationFormats"}
						</div>
						<div class="value">
							<ul>
								{foreach from=$citationPlugins name="citationPlugins" item="citationPlugin"}
									<li class="{$citationPlugin->getName()|escape}{if $smarty.foreach.citationPlugins.iteration == 1} current{/if}">
										{capture assign="citationUrl"}{url page="article" op="cite" path=$article->getBestArticleId()}/{$citationPlugin->getName()|escape}{/capture}
										<a href="{$citationUrl}"{if !$citationPlugin->isDownloadable()} data-load-citation="true"{/if} target="_blank">{$citationPlugin->getCitationFormatName()|escape}</a>
									</li>
								{/foreach}
							</ul>
						</div>
					</div>
				</div>
			{/if}

			{* Issue article appears in *}
			<div class="item issue">
				<div class="sub_item">
					<div class="label">
						{translate key="issue.issue"}
					</div>
					<div class="value">
						<a class="title" href="{url page="issue" op="view" path=$issue->getBestIssueId()}">
							{$issue->getIssueIdentification()}
						</a>
					</div>
				</div>

				{if $section}
					<div class="sub_item">
						<div class="label">
							{translate key="section.section"}
						</div>
						<div class="value">
							{$section->getLocalizedTitle()|escape}
						</div>
					</div>
				{/if}
			</div>

			{* Keywords *}
			{* @todo keywords not yet implemented *}

			{* Article Subject *}
			{if $article->getLocalizedSubject()}
				<div class="item subject">
					<h3 class="label">
						{translate key="article.subject"}
					</h3>
					<div class="value">
						{$article->getLocalizedSubject()|escape}
					</div>
				</div>
			{/if}

			{* PubIds (requires plugins) *}
			{foreach from=$pubIdPlugins item=pubIdPlugin}
				{if $pubIdPlugin->getPubIdType() == 'doi'}
					{php}continue;{/php}
				{/if}
				{if $issue->getPublished()}
					{assign var=pubId value=$article->getStoredPubId($pubIdPlugin->getPubIdType())}
				{else}
					{assign var=pubId value=$pubIdPlugin->getPubId($article)}{* Preview pubId *}
				{/if}
				{if $pubId}
					<div class="item pubid">
						<div class="label">
							{$pubIdPlugin->getPubIdDisplayType()|escape}
						</div>
						<div class="value">
							{if $pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}
								<a id="pub-id::{$pubIdPlugin->getPubIdType()|escape}" href="{$pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}">
									{$pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}
								</a>
							{else}
								{$pubId|escape}
							{/if}
						</div>
					</div>
				{/if}
			{/foreach}

			{* Licensing info *}
			{if $copyright || $licenseUrl}
				<div class="item copyright">
					{if $licenseUrl}
						{if $ccLicenseBadge}
							{$ccLicenseBadge}
						{else}
							<a href="{$licenseUrl|escape}" class="copyright">
								{if $copyrightHolder}
									{translate key="submission.copyrightStatement" copyrightHolder=$copyrightHolder copyrightYear=$copyrightYear}
								{else}
									{translate key="submission.license"}
								{/if}
							</a>
						{/if}
					{/if}
					{$copyright}
				</div>
			{/if}

			{call_hook name="Templates::Article::Details"}

		</div><!-- .entry_details -->
	</div><!-- .row -->

</article>
