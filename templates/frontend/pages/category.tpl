{**
 * templates/frontend/pages/category.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Display the page to view a category.
 *
 * @uses $category Category Current category being viewed
 * @uses $publishedSubmissions array List of published submissions in this category
 * @uses $parentCategory Category Parent category if one exists
 * @uses $subcategories array List of subcategories if they exist
 * @uses $prevPage int The previous page number
 * @uses $nextPage int The next page number
 * @uses $showingStart int The number of the first item on this page
 * @uses $showingEnd int The number of the last item on this page
 * @uses $total int Count of all published submissions in this category
 *}
{include file="frontend/components/header.tpl" pageTitleTranslated=$category->getLocalizedTitle()|escape}

<div class="page page_category">

	{* Breadcrumb *}
	{include file="frontend/components/breadcrumbs_catalog.tpl" type="category" parent=$parentCategory currentTitle=$category->getLocalizedTitle()}
	<h1>
		{$category->getLocalizedTitle()|escape}
	</h1>

	{* Count of articles in this category *}
	<div class="article_count">
		{translate key="catalog.browseTitles" numTitles=$total}
	</div>

	{* Image and description *}
	{assign var="image" value=$category->getImage()}
	{assign var="description" value=$category->getLocalizedDescription()|strip_unsafe_html}
	<div class="about_section{if $image} has_image{/if}{if $description} has_description{/if}">
		{if $image}
			<div class="cover" href="{url router=PKP\core\PKPApplication::ROUTE_PAGE page="articles" op="fullSize" type="category" id=$category->getId()}">
				<img src="{url router=PKP\core\PKPApplication::ROUTE_PAGE page="articles" op="thumbnail" type="category" id=$category->getId()}" alt="null" />
			</div>
		{/if}
		<div class="description">
			{$description|strip_unsafe_html}
		</div>
	</div>

	{if $subcategories|@count}
	<nav class="subcategories" role="navigation">
		<h2>
			{translate key="category.subcategories"}
		</h2>
		<ul>
			{foreach from=$subcategories item=subcategory}
				<li>
					<a href="{url op="category" path=$subcategory->getParentPath()|to_array:$subcategory->getPath()}">
						{$subcategory->getLocalizedTitle()|escape}
					</a>
				</li>
			{/foreach}
		</ul>
	</nav>
	{/if}

	<h2 class="title">
		{translate key="category.heading"}
	</h2>

	{* No published titles in this category *}
	{if empty($publishedSubmissions)}
		<p>{translate key="category.noItems"}</p>
	{else}
		<ul class="cmp_article_list articles">
			{foreach from=$publishedSubmissions item=article}
				<li>
					{include file="frontend/objects/article_summary.tpl" article=$article hidePageNumbers=true hideGalleys=true heading="h3"}
				</li>
			{/foreach}
		</ul>

		{* Pagination *}
		{capture assign=categoryFullPath}{$category->getParentPath()|to_array:$category->getPath()}{/capture}
		{if $prevPage > 1}
			{capture assign=prevUrl}{url router=PKP\core\PKPApplication::ROUTE_PAGE page="articles" op="category" path=$category->getPath()|to_array:$prevPage}{/capture}
		{elseif $prevPage === 1}
			{capture assign=prevUrl}{url router=PKP\core\PKPApplication::ROUTE_PAGE page="articles" op="category" path=$category->getPath()}{/capture}
		{/if}
		{if $nextPage}
			{capture assign=nextUrl}{url router=PKP\core\PKPApplication::ROUTE_PAGE page="articles" op="category" path=$category->getPath()|to_array:$nextPage}{/capture}
		{/if}
		{include
			file="frontend/components/pagination.tpl"
			prevUrl=$prevUrl
			nextUrl=$nextUrl
			showingStart=$showingStart
			showingEnd=$showingEnd
			total=$total
		}
	{/if}

</div><!-- .page -->

{include file="frontend/components/footer.tpl"}
