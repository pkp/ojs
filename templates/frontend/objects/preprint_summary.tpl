{**
 * templates/frontend/objects/preprint_summary.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief View of an Preprint summary which is shown within a list of preprints.
 *
 * @uses $preprint Preprint The preprint
 * @uses $hasAccess bool Can this user access galleys for this context? The
 *       context may be an issue or an preprint
 * @uses $showDatePublished bool Show the date this preprint was published?
 * @uses $hideGalleys bool Hide the preprint galleys for this preprint?
 * @uses $primaryGenreIds array List of file genre ids for primary file types
 *}
{assign var=preprintPath value=$preprint->getBestId()}

{if (!$section.hideAuthor && $preprint->getHideAuthor() == $smarty.const.AUTHOR_TOC_DEFAULT) || $preprint->getHideAuthor() == $smarty.const.AUTHOR_TOC_SHOW}
	{assign var="showAuthor" value=true}
{/if}

<div class="obj_article_summary">
	{if $preprint->getLocalizedCoverImage()}
		<div class="cover">
			<a {if $journal}href="{url journal=$journal->getPath() page="preprint" op="view" path=$preprintPath}"{else}href="{url page="preprint" op="view" path=$preprintPath}"{/if} class="file">
				<img src="{$preprint->getLocalizedCoverImageUrl()|escape}" alt="{$preprint->getLocalizedCoverImageAltText()|escape|default:'null'}">
			</a>
		</div>
	{/if}

	<div class="title">
		<a id="preprint-{$preprint->getId()}" {if $journal}href="{url journal=$journal->getPath() page="preprint" op="view" path=$preprintPath}"{else}href="{url page="preprint" op="view" path=$preprintPath}"{/if}>
			{$preprint->getLocalizedTitle()|strip_unsafe_html}
			{if $preprint->getLocalizedSubtitle()}
				<span class="subtitle">
					{$preprint->getLocalizedSubtitle()|escape}
				</span>
			{/if}
		</a>
	</div>

	{if $showAuthor || $preprint->getPages() || ($preprint->getDatePublished() && $showDatePublished)}
	<div class="meta">
		{if $showAuthor}
		<div class="authors">
			{$preprint->getAuthorString()|escape}
		</div>
		{/if}

		{if $showDatePublished && $preprint->getDatePublished()}
			<div class="published">
				{$preprint->getDatePublished()|date_format:$dateFormatShort}
			</div>
		{/if}

	</div>
	{/if}

	{if !$hideGalleys}
		<ul class="galleys_links">
			{foreach from=$preprint->getGalleys() item=galley}
				{if $primaryGenreIds}
					{assign var="file" value=$galley->getFile()}
					{if !$galley->getRemoteUrl() && !($file && in_array($file->getGenreId(), $primaryGenreIds))}
						{continue}
					{/if}
				{/if}
				<li>
					{assign var="hasPreprintAccess" value=$hasAccess}
					{if $currentContext->getSetting('publishingMode') == $smarty.const.PUBLISHING_MODE_OPEN || $preprint->getAccessStatus() == $smarty.const.ARTICLE_ACCESS_OPEN}
						{assign var="hasPreprintAccess" value=1}
					{/if}
					{include file="frontend/objects/galley_link.tpl" parent=$preprint labelledBy="preprint-{$preprint->getId()}" hasAccess=$hasPreprintAccess}
				</li>
			{/foreach}
		</ul>
	{/if}

	{call_hook name="Templates::Archive::Preprint"}
</div>
