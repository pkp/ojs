{**
 * templates/frontend/objects/article_details.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief View of an Article which displays all details about the article.
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

	{if $article->getAuthors()}
		<ul class="authors">
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

	{if $article->getGalleys()}
		<ul class="galleys_links">
			{foreach from=$article->getGalleys() item=galley}
				<li>
					{include file="frontend/objects/galley_link.tpl" parent=$article}
				</li>
			{/foreach}
		</ul>
	{/if}

	{* Keywords *}
	{* @todo keywords not yet implemented *}

	{if $article->getLocalizedSubject()}
		<div class="subject">
			<h3>{translate key="article.subject"}</h3>
			<p>
				{$article->getLocalizedSubject()|escape}
			</p>
		</div>
	{/if}

	{if $article->getLocalizedAbstract()}
		<div class="abstract">
			<h3>{translate key="article.abstract"}</h3>
			{$article->getLocalizedAbstract()|strip_unsafe_html|nl2br}
		</div>
	{/if}

	<div class="issue">
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

	{* Citations *}
	{* @todo this hasn't been tested or styled *}
	{if $citationFactory->getCount()}
		<div class="citations">
			<h3>{translate key="submission.citations"}</h3>
			<ul>
				{iterate from=citationFactory item=citation}
					<li>
						{$citation->getRawCitation()|strip_unsafe_html}
					</li>
				{/iterate}
			</ul>
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
			<div class="pubid">
				<div class="type">
					{$pubIdPlugin->getPubIdDisplayType()|escape}
				</div>
				<div class="id">
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

</article>
