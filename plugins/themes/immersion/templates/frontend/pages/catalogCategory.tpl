{**
 * templates/frontend/pages/catalogCategory.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Display the page to view a category of the catalog.
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

<main id="immersion_content_main">

	<section class="category">
		<div class="container">

			{assign var="image" value=$category->getImage()}
			{assign var="description" value=$category->getLocalizedDescription()|strip_unsafe_html}
			<header class="row category__header">
				<div class="col-md-6 col-lg-3 ">
					<h1 class="category__title">
						<span class="category__localized_name">{$category->getLocalizedTitle()|escape}</span>
					</h1>
					<p class="category__meta">{translate key="catalog.browseTitles" numTitles=$total}</p>
				</div>

				{if $description}
					<div class="col-md-6 col-lg-9 category__desc">
						{$description}
					</div>
				{/if}
				{if $image}
					<div class="col-lg-12">
						<img class="category__cover img-fluid" src="{url router=$smarty.const.ROUTE_PAGE page="catalog" op="fullSize" type="category" id=$category->getId()}" alt="{$category->getLocalizedTitle()|escape}" />
					</div>
				{/if}
			</header>

			<div class="row">
				{if $subcategories|@count}
					<nav class="subcategories col-12" role="navigation">
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

				<div class="col-12">

					<h3 class="title">
						{translate key="catalog.category.heading"}
					</h3>

					{* No published titles in this category *}
					{if empty($publishedSubmissions)}
						<p>{translate key="catalog.category.noItems"}</p>
					{else}
						<ul class="category__list">
							{foreach from=$publishedSubmissions item=article}
								<li class="category__list-item">
									{include file="frontend/objects/article_summary.tpl" hideGalleys=true}
								</li>
							{/foreach}
						</ul>

						{if $prevPage || $nextPage}
							<div class="row">
								<div class="col-md-8 offset-md-4">
									{* Pagination *}
									{if $prevPage > 1}
										{capture assign=prevUrl}{url router=$smarty.const.ROUTE_PAGE page="catalog" op="category" path=$category->getPath()|to_array:$prevPage}{/capture}
									{elseif $prevPage === 1}
										{capture assign=prevUrl}{url router=$smarty.const.ROUTE_PAGE page="catalog" op="category" path=$category->getPath()}{/capture}
									{/if}
									{if $nextPage}
										{capture assign=nextUrl}{url router=$smarty.const.ROUTE_PAGE page="catalog" op="category" path=$category->getPath()|to_array:$nextPage}{/capture}
									{/if}
									{include
									file="frontend/components/pagination.tpl"
									prevUrl=$prevUrl
									nextUrl=$nextUrl
									showingStart=$showingStart
									showingEnd=$showingEnd
									total=$total
									}
								</div>
							</div>
						{/if}
					{/if}
				</div>
			</div>
		</div>
	</section>
</main><!-- .container -->

{include file="frontend/components/footer.tpl"}
