{**
 * templates/frontend/components/archiveHeader.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Archive header containing a search form and a category listing
 *}
	{* Search *}
	<section class="archiveHeader_search">
		{include file="frontend/components/searchForm_archive.tpl" className="pkp_search_desktop"}
	</section>

	{* Category listing *}
	<section class="archiveHeader_categories">
	<ul>
		{if $categories && $categories->getCount()}
			{iterate from=categories item=category}
				<li class="category_{$category->getId()}{if $category->getParentId()} is_sub{/if}">
					<a href="{url router=$smarty.const.ROUTE_PAGE page="catalog" op="category" path=$category->getPath()|escape}">
						{$category->getLocalizedTitle()|escape}
					</a>
				</li>
			{/iterate}
		{/if}
	</ul>
	</section>
