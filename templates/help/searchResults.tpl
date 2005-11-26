{**
 * searchResults.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show help search results.
 *
 * $Id$
 *}

{include file="help/header.tpl"}

<div id="main" style="margin: 0; width: 660px;">

	<h4>{translate key="help.ojsHelp"}</h4>

	<div class="thickSeparator"></div>

	<div id="breadcrumb">
		<a href="{get_help_id key="index.index" url="true"}">{translate key="navigation.home"}</a>
	</div>

	<h2>{translate key="help.searchResults"}</h2>

	<div id="content">
		<h4>{translate key="help.searchResultsFor"} "{$helpSearchKeyword|escape}"</h4>
		<div id="search">
		{if count($searchResults) > 0}
			<h5>{translate key="help.matchesFound" matches=$searchResults|@count}</h5>
			<ul>
			{foreach name=results from=$searchResults item=result}
				{assign var=sections value=$result.topic->getSections()}
				<li>
					<a href="{url op="view" path=$result.topic->getId()}">{$result.topic->getTitle()}</a>
					{eval var=$sections[0]->getContent()|strip_tags|truncate:200}
					<div class="searchBreadcrumb">
						<a href="{url op="view" path="index"|to_array:"topic":"000000"}">{translate key="navigation.home"}</a>
						{foreach name=breadcrumbs from=$result.toc->getBreadcrumbs() item=breadcrumb key=key}
							{if $breadcrumb != $result.topic->getId()}
							 &gt; <a href="{url op="view" path=$breadcrumb}">{$key}</a>
							{/if}
						{/foreach}
						{if $result.topic->getId() != "index/topic/000000"}
						&gt; <a href="{url op="view" path=$result.topic->getId()}" class="current">{$result.topic->getTitle()}</a>
						{/if}
					</div>
				</li>
			{/foreach}
			</ul>
		{else}
			<em>{translate key="help.noMatchingTopics"}</em>
		{/if}
		</div>

		<div class="separator"></div>

		<div>
			<h4>{translate key="help.search"}</h4>
			<form action="{url op="search"}" method="post" style="display: inline">
			{translate key="help.searchFor"}&nbsp;&nbsp;<input type="text" name="keyword" size="30" maxlength="60" value="{$helpSearchKeyword|escape}" class="textField" />
			<input type="submit" value="{translate key="common.search"}" class="button" />
			</form>
		</div>
	</div>
</div>

{include file="help/footer.tpl"}
