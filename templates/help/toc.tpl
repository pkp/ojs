{**
 * toc.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Help table of contents.
 *
 * $Id$
 *}

<div style="padding-top: 0.5em;">
	<form action="{$pageUrl}/help/search" method="post" style="display: inline">
	{translate key="navigation.search"}&nbsp;&nbsp;<input type="text" name="keyword" size="16" maxlength="60" value="{$helpSearchKeyword}" class="textField" />
	</form>
</div>

<br />

<div><a href="{$pageUrl}/help/view/index/topic/000000">{translate key="help.helpIndex"}</a></div>

<br />

{if $toc->getPrevTopicId()}
{translate key="help.contents"}&nbsp;<a href="{$pageUrl}/help/view/{$toc->getPrevTopicId()}">{translate key="common.up"}</a>
<br />

<div class="block">
	{if $mainTopic}
	<a href="{$pageUrl}/help/view/{$mainTopic->getId()}"><span class="blockTitle">{$mainTopic->getTitle()}</span></a>
	{/if}
	<ul>
		{foreach from=$toc->getTopics() item=currTopic name=topics}
			{if !$smarty.foreach.topics.first}
				<li {if ($currTopic->getId() == $topic->getId())}class="current"{/if}><a href="{$pageUrl}/help/view/{$currTopic->getId()}">{$currTopic->getTitle()}</a></li>
			{/if}
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
