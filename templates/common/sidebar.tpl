{**
 * sidebar.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu.
 *
 * $Id$
 *}
 
{if $leftSidebarTemplate}
	{include file=$leftSidebarTemplate}
	<br />
{/if}
{if $navMenuItems}
<div id="sideNavMenu">
<ul>
	{foreach from=$navMenuItems item=navItem}
	{if $navItem.name}
	
	{if $navItem.path && ($pagePath == $navItem.path || ($navItem.path && strpos($pagePath, $navItem.path) === 0)) || ($pagePath == $navItem.url || ($navItem.url && strpos($pagePath, $navItem.url) === 0))}
	<li><a href="{if $navItem.isAbsolute}{$navItem.url}{else}{$pageUrl}{$navItem.url}{/if}" class="menuSelected">{if $navItem.isLiteral}{$navItem.name}{else}{translate key=$navItem.name}{/if}</a></li>
	{if $navItem.subItems}
		{foreach from=$navItem.subItems item=subnavItem}
		{if $subnavItem.name}
		{if $subnavItem.path && ($pagePath == $subnavItem.path || ($subnavItem.path && strpos($pagePath, $subnavItem.path) === 0)) || ($pagePath == $subnavItem.url || ($subnavItem.url && strpos($pagePath, $subnavItem.url) === 0))}
		<li class="subMenu"><a href="{if $subnavItem.isAbsolute}{$subnavItem.url}{else}{$pageUrl}{$subnavItem.url}{/if}" class="menuSelected">{if $subnavItem.isLiteral}{$subnavItem.name}{else}{translate key=$subnavItem.name}{/if}</a></li>
		{else}
		<li class="subMenu"><a href="{if $subnavItem.isAbsolute}{$subnavItem.url}{else}{$pageUrl}{$subnavItem.url}{/if}">{if $subnavItem.isLiteral}{$subnavItem.name}{else}{translate key=$subnavItem.name}{/if}</a></li>
		{/if}
		{/if}
		{/foreach}
	{/if}
	{else}
	<li><a href="{if $navItem.isAbsolute}{$navItem.url}{else}{$pageUrl}{$navItem.url}{/if}">{if $navItem.isLiteral}{$navItem.name}{else}{translate key=$navItem.name}{/if}</a></li>
	{/if}
	{/if}
	{/foreach}
</ul>
</div>

<br />

<div id="searchBoxTitle">{translate key="common.search"}</div>
<div id="searchBox">
<form method="get" action="{$pageUrl}/search">
<input type="text" name="search" size="20" maxlength="255" value="" class="textField" />
<br />
<select name="searchField" class="selectMenu">
<option value="all">{translate key="search.allFields"}</option>
<option value="author">{translate key="search.author"}</option>
<option value="title">{translate key="search.title"}</option>
<option value="abstract">{translate key="search.abstract"}</option>
<option value="keywords">{translate key="search.indexTerms"}</option>
</select>
<br />
<input type="submit" value="{translate key="common.search"}" class="button" />
</form>
</div>
{/if}
