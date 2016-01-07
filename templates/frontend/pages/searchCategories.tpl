{**
 * templates/frontend/pages/searchCategories.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site category list.
 *
 *}
{strip}
{assign var="pageTitle" value="navigation.categories"}
{include file="frontend/components/header.tpl"}
{/strip}

<br />

<a name="categories"></a>

<ul>
{foreach from=$categories item=categoryArray}
	{assign var=category value=$categoryArray.category}
	<li><a href="{url op="category" path=$category->getId()}">{$category->getLocalizedName()|escape}</a> ({$categoryArray.journals|@count})</li>
{/foreach}
</ul>

{include file="common/frontend/footer.tpl"}
