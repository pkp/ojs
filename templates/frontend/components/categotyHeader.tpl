{**
 * templates/frontend/components/categotyHeader.tpl
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * CategotyHeader header containing a category listing
 *}

 <section class="categotyHeader">
	<section class="categotyHeader_categories">
        <ul class="categories_listing">
            {foreach from=$categories item=category}
                {if !$category->getParentId()}
                    <li class="category_{$category->getPath()|escape}">
                        <a href="{url router=PKP\core\PKPApplication::ROUTE_PAGE page="catalog" op="category" path=$category->getPath()|escape}">
                            {$category->getLocalizedTitle()|escape}
                        </a>
                    </li>
                {/if}
            {/foreach}
        </ul>
	</section>
</section>
