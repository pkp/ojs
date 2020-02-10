{**
 * templates/frontend/pages/series.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Display the reader-facing series page.
 *
 * @uses $section Section
 * @uses $sectionPath string The URL path for this section
 * @uses $sectionDescription string
 * @uses $preprints array List of Submission objects
 * @uses $currentlyShowingStart int 20 in `20-30 of 100 results`
 * @uses $currentlyShowingEnd int 30 in `20-30 of 100 results`
 * @uses $countMax int 100 in `20-30 of 100 results`
 * @uses $currentlyShowingPage int 2 in `2 of 10 pages`
 * @uses $countMaxPage int 10 in `2 of 10 pages`.
 *}

{include file="frontend/components/header.tpl" pageTitleTranslated=$section->getLocalizedTitle()|escape}

<div class="page page_section page_section_{$sectionPath|escape}">
	<h1 class="page_title">
		{$section->getLocalizedTitle()|escape}
	</h1>

	<div class="section_description">
		{$section->getLocalizedDescription()|strip_unsafe_html}
	</div>

	{if $preprints|@count}
		<ul class="cmp_article_list">
			{foreach from=$preprints item=preprint}
				<li>
					{* TODO remove section=null workaround *}
					{include file="frontend/objects/preprint_summary.tpl" section=null showDatePublished=true}
				</li>
			{/foreach}

			{* Pagination *}
			{if $prevPage > 1}
				{capture assign="prevUrl"}{url|escape router=$smarty.const.ROUTE_PAGE page="section" op="view" path=$sectionPath|to_array:$prevPage}{/capture}
			{elseif $prevPage === 1}
				{capture assign="prevUrl"}{url|escape router=$smarty.const.ROUTE_PAGE page="section" op="view" path=$sectionPath}{/capture}
			{/if}
			{if $nextPage}
				{capture assign="nextUrl"}{url|escape router=$smarty.const.ROUTE_PAGE page="section" op="view" path=$sectionPath|to_array:$nextPage}{/capture}
			{/if}
			{include
				file="frontend/components/pagination.tpl"
				prevUrl=$prevUrl
				nextUrl=$nextUrl
				showingStart=$showingStart
				showingEnd=$showingEnd
				total=$total
			}
		</ul>
	{else}
		<p class="section_empty">
			{translate key="section.emptySection"}
		</p>
	{/if}

</div><!-- .page -->

{include file="frontend/components/footer.tpl"}