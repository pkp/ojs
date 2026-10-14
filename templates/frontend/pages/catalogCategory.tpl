{**
 * templates/frontend/pages/catalogCategory.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Display the page to view a category of the catalog.
 *
 * @uses $category Category Current category being viewed
 * @uses $results array List of published submissions in this category
 * @uses $parentCategory Category Parent category if one exists
 * @uses $subcategories array List of subcategories if they exist
 * @uses $orderBy string Order option
 * @uses $orderDir string When set, either 'asc' or 'desc'
 *}
{include file="frontend/components/header.tpl" pageTitleTranslated=$category->getLocalizedTitle()|escape}

<div class="page page_catalog_category">

	{* Breadcrumb *}
	{include file="frontend/components/breadcrumbs_catalog.tpl" type="category" parent=$parentCategory currentTitle=$category->getLocalizedTitle()}
	<h1>
		{$category->getLocalizedTitle()|escape}
	</h1>

	{* Count of articles in this category *}
	<div class="article_count">
		{translate key="catalog.browseTitles" numTitles=$results->total()}
	</div>

	{* Image and description *}
	{assign var="image" value=$category->getImage()}
	{assign var="description" value=$category->getLocalizedDescription()|strip_unsafe_html}
	<div class="about_section{if $image} has_image{/if}{if $description} has_description{/if}">
		{if $image}
			<div class="cover" href="{url router=PKP\core\PKPApplication::ROUTE_PAGE page="catalog" op="fullSize" type="category" id=$category->getId()}">
				<img src="{url router=PKP\core\PKPApplication::ROUTE_PAGE page="catalog" op="thumbnail" type="category" id=$category->getId()}" alt="null" />
			</div>
		{/if}
		<div class="description">
			{$description|strip_unsafe_html}
		</div>
	</div>

	{if $subcategories|@count}
	<nav class="subcategories" role="navigation">
		<h2>
			{translate key="catalog.category.subcategories"}
		</h2>
		<ul>
			{foreach from=$subcategories item=subcategory}
				<li>
					<a href="{url op="category" path=$subcategory->getPath()}">
						{$subcategory->getLocalizedTitle()|escape}
					</a>
				</li>
			{/foreach}
		</ul>
	</nav>
	{/if}

	<h2 class="title">
		{translate key="catalog.category.heading"}
	</h2>

	{* No published titles in this category *}
	{if empty($results)}
		<p>{translate key="catalog.category.noItems"}</p>
	{else}
		<ul class="cmp_article_list articles">
			{foreach from=$results item=result}
				<li>
					{include file="frontend/objects/article_summary.tpl" article=$result.submission hideGalleys=true heading="h3"}
				</li>
			{/foreach}
		</ul>

		{page_info iterator=$results}
		{page_links anchor="results" iterator=$results name="category" query=$query searchContext=$searchContext authors=$authors dateFromMonth=$dateFromMonth dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateToMonth=$dateToMonth dateToDay=$dateToDay dateToYear=$dateToYear orderBy=$orderBy orderDir=$orderDir}
	{/if}

</div><!-- .page -->

{include file="frontend/components/footer.tpl"}
