{**
 * templates/frontend/pages/sections.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Display the reader-facing sections page.
 *
 * @uses $section Section
 * @uses $sectionUrlPath string The URL path for this section
 * @uses $sectionDescription string
 * @uses $submissions array List of Submission objects
 * @uses $issueUrls array list of issue url's
 * @uses $issueNames array list of issue name
 * @uses $currentlyShowingStart int 20 in `20-30 of 100 results`
 * @uses $currentlyShowingEnd int 30 in `20-30 of 100 results`
 * @uses $countMax int 100 in `20-30 of 100 results`
 * @uses $currentlyShowingPage int 2 in `2 of 10 pages`
 * @uses $countMaxPage int 10 in `2 of 10 pages`.
 *}

{include file="frontend/components/header.tpl" pageTitleTranslated=$section->getLocalizedTitle()|escape}

<div class="page page_section page_section_{$sectionUrlPath|escape}">
	<h1 class="page_title">
		{$section->getLocalizedTitle()|escape}
	</h1>

	{* Description *}
	{assign var="description" value=$section->getLocalizedDescription()|strip_unsafe_html}
	<div class="about_section {if $description} has_description{/if}">
		<div class="description">
			{$description|strip_unsafe_html}
		</div>
	</div>

	{if $submissions|@count}
		<ul class="cmp_article_list">
			{foreach from=$submissions item=article}
				<li>
					{* TODO remove section=null workaround *}
					{include file="frontend/objects/article_summary.tpl" section=null hideGalleys=true hidePageNumbers=true heading="h3" issueUrl=$issueUrls[$article->getId()] issueName=$issueNames[$article->getId()]}.
				</li>
			{/foreach}

			{* Pagination *}
			{if $prevPage > 1}
				{capture assign="prevUrl"}{url|escape router=$smarty.const.ROUTE_PAGE page="articles" op="section" path=$sectionUrlPath|to_array:$prevPage}{/capture}
			{elseif $prevPage === 1}
				{capture assign="prevUrl"}{url|escape router=$smarty.const.ROUTE_PAGE page="articles" op="section" path=$sectionUrlPath}{/capture}
			{/if}
			{if $nextPage}
				{capture assign="nextUrl"}{url|escape router=$smarty.const.ROUTE_PAGE page="articles" op="section" path=$sectionUrlPath|to_array:$nextPage}{/capture}
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
