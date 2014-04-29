{**
 * templates/index/categories.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
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

<ul class="plain">
{foreach from=$categories item=categoryArray}
	{assign var=category value=$categoryArray.category}
	<li>&#187; <a href="{url op="category" path=$category->getId()}">{$category->getLocalizedName()|escape}</a> ({$categoryArray.journals|@count})</li>
{/foreach}
</ul>

{include file="common/footer.tpl"}

