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

<div id="main">

	<h4>{translate key="help.ojsHelp"}</h4>
	
	<div class="thickSeparator"></div>
	
	<div id="breadcrumb">
		<a href="{$pageUrl}/help/view/index/topic/000000">{translate key="navigation.home"}</a>
	</div>
	
	<h2>{translate key="help.searchResults"}</h2>
	
	<div id="content">
		<h4>{translate key="help.searchResultsFor"} "{$helpSearchKeyword|escape}"</h4>
		<div id="search">
		{if count($topics) > 0}
			<h5>{translate key="help.matchesFound" matches=$topics|@count}</h5>
			<ul>
			{foreach name=results from=$topics item=topic}
				<li><a href="{$pageUrl}/help/view/{$topic->getId()}">{$topic->getTitle()}</a></li>
			{/foreach}
			</ul>
		{else}
			<em>{translate key="help.noMatchingTopics"}</em>
		{/if}
		</div>
		
		<div class="separator"></div>
		
		<div>
			<h4>{translate key="help.search"}</h4>
			<form action="{$pageUrl}/help/search" method="post" style="display: inline">
			{translate key="help.searchFor"}&nbsp;&nbsp;<input type="text" name="keyword" size="30" maxlength="60" value="{$helpSearchKeyword|escape}" class="textField" />
			<input type="submit" value="{translate key="common.search"}" class="button" />
			</form>
			<script type="text/javascript">document.forms[0].keyword.focus()</script>
		</div>
	</div>
</div>

{include file="help/footer.tpl"}
