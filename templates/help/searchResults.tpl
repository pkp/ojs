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

{assign var="pageTitle" value="help.help"}
{include file="help/header.tpl"}

<div id="searchResultsFrame">

<span id="topicTitle">{translate key="help.searchResults"}</span>

<br />

{if count($topics) > 0}
<br />
<b>{translate key="help.matchesFound" matches=$topics|@count}:</b>

<ul class="sections">
{foreach name=results from=$topics item=topic}
<li><a href="{$pageUrl}/help/view/{$topic->getId()}">{$topic->getTitle()}</a></li>
{/foreach}
</ul>
{else}
<br />
{translate key="help.noMatchingTopics"}
<br /><br />
{/if}

&#187; <a href="{$pageUrl}/help/view/000000">{translate key="help.helpIndex"}</a>

</div>

<script type="text/javascript">document.forms[0].keyword.focus()</script>

{include file="help/footer.tpl"}
