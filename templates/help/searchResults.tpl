{**
 * searchResults.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show help search results.
 *
 * $Id$
 *}

{include file="help/header.tpl"}

<br />

<div id="search">

{if count($topics) > 0}
	<b>{translate key="help.matchesFound" matches=$topics|@count}</b>
	<ul>
	{foreach name=results from=$topics item=topic}
		<li><a href="{$pageUrl}/help/view/{$topic->getId()}">{$topic->getTitle()}</a></li>
	{/foreach}
	</ul>
{else}
	{translate key="help.noMatchingTopics"}
{/if}

</div>

<script type="text/javascript">document.forms[0].keyword.focus()</script>

{include file="help/footer.tpl"}
