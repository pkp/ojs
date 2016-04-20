{**
 * templates/frontend/objects/article_details.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
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
 * @uses $citationFactory @todo
 * @uses $pubIdPlugins @todo
 *}
<article class="obj_article_details">
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

			{if $article->getAuthors()}
				<ul class="item authors">
					{foreach from=$article->getAuthors() item=author}
						<li>
							<span class="name">
								{$author->getFullName()|escape}
							</span>
							{if $author->getLocalizedAffiliation()}
								<span class="affiliation">
									{$author->getLocalizedAffiliation()|escape}
								</span>
							{/if}
						</li>
					{/foreach}
				</ul>
			{/if}

			{if $article->getLocalizedAbstract()}
				<div class="item abstract">
					<h3 class="label">{translate key="article.abstract"}</h3>
					{$article->getLocalizedAbstract()|strip_unsafe_html|nl2br}
				</div>
			{/if}

			{call_hook name="Templates::Article::Main"}

		</div><!-- .main_entry -->

		<div class="entry_details">

			{if $article->getGalleys()}
				<div class="item galleys">
					<ul class="value galleys_links">
						{foreach from=$article->getGalleys() item=galley}
							<li>
								{include file="frontend/objects/galley_link.tpl" parent=$article}
							</li>
						{/foreach}
					</ul>
				</div>
			{/if}

			{* Keywords *}
			{* @todo keywords not yet implemented *}

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

			<div class="item issue">
				<div class="value">
					{capture assign="issueLink"}
						<a class="title" href="{url page="issue" op="view" path=$issue->getBestIssueId($currentJournal)}">
							{$issue->getIssueIdentification()}
						</a>
					{/capture}

					{if $section}
						{translate key="article.publishedInWithSection" sectionTitle=$section->getLocalizedTitle()|escape issueLink=$issueLink}
					{else}
						{translate key="article.publishedIn" issueLink=$issueLink}
					{/if}
				</div>
			</div>

			{* Citations *}
			{* @todo this hasn't been tested or styled *}
			{if $citationFactory->getCount()}
				<div class="item citations">
					<h3 class="label">
						{translate key="submission.citations"}
					</h3>
					<div class="value">
						<ul>
							{iterate from=citationFactory item=citation}
								<li>
									{$citation->getRawCitation()|strip_unsafe_html}
								</li>
							{/iterate}
						</ul>
					</div>
				</div>
			{/if}

			{* PubIds (requires plugins) *}
			{* @todo this hasn't been tested or styled *}
			{foreach from=$pubIdPlugins item=pubIdPlugin}
				{if $issue->getPublished()}
					{assign var=pubId value=$pubIdPlugin->getPubId($pubObject)}
				{else}
					{assign var=pubId value=$pubIdPlugin->getPubId($pubObject, true)}{* Preview rather than assign a pubId *}
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

			{call_hook name="Templates::Article::Details"}

		</div><!-- .entry_details -->
	</div><!-- .row -->

</article>
