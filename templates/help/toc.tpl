{**
 * toc.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Help table of contents.
 *
 * $Id$
 *}

<div>
	<form action="{$pageUrl}/help/search" method="post" style="display: inline">
	{translate key="navigation.search"}&nbsp;&nbsp;<input type="text" name="keyword" size="16" maxlength="60" value="{$helpSearchKeyword}" class="textField" />
	</form>
</div>

<br />

<div><a href="{$pageUrl}/help/view/index/topic/000000">{translate key="help.helpIndex"}</a></div>

{if not $showSearch}
<br />

{if $toc->getPrevTopicId()}
{translate key="help.contents"}&nbsp;<a href="{$pageUrl}/help/view/{$toc->getPrevTopicId()}">{translate key="common.up"}</a>
<br />
{/if}

<div class="block">
	<span class="blockTitle">{$toc->getTitle()}</span>
	<ul>
		{foreach from=$toc->getTopics() item=topic}
			<li><a href="{$pageUrl}/help/view/{$topic->getId()}">{$topic->getTitle()}</a></li>
		{/foreach}
	</ul>
</div>

{if $showRelatedTopics}
<div class="block">
	<span class="blockTitle">{translate key="help.relatedTopics"}</span>
	<ul>
		{foreach from=$relatedTopics item=relatedTopic}
			<li><a href="{$pageUrl}/help/view/{$relatedTopic[1]}">{$relatedTopic[0]}</a></li>
		{/foreach}
	</ul>
</div>
{/if}

{/if}
