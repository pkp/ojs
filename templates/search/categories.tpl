{**
 * templates/index/categories.tpl
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site category list.
 *
 *}
{strip}
{assign var="pageTitle" value="navigation.categories"}
{include file="common/header.tpl"}
{/strip}

<br />

<a name="categories"></a>

<ul>
{foreach from=$categories item=categoryArray}
	{assign var=category value=$categoryArray.category}
	<li><a href="{url op="category" path=$category->getId()}">{$category->getLocalizedName()|escape}</a> ({$categoryArray.journals|@count})</li>
{/foreach}
</ul>

{include file="common/footer.tpl"}
