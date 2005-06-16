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
	<input type="text" name="keyword" size="16" maxlength="60" value="{$helpSearchKeyword}" class="textField" />&nbsp;<input type="submit" value="{translate key="common.search"}" class="button" />
	</form>
</div>

<br />

<div><a href="{$pageUrl}/help/toc">{translate key="help.toc"}</a></div>

<br />

{if $toc->getParentTopicId() && $toc->getParentTopicId() != $topic->getId()}
{translate key="help.contents"}&nbsp;<a href="{$pageUrl}/help/view/{$toc->getParentTopicId()}">{translate key="help.upALevel"}</a>
<br />
{/if}

<div class="block">
	<span class="blockTitle">{$toc->getTitle()}</span>
	<ul>
		{foreach from=$toc->getTopics() item=currTopic}
			{if $currTopic->getId() == $topic->getId()}
			<li><a href="{$pageUrl}/help/view/{$currTopic->getId()}" class="current">{$currTopic->getTitle()}</a>
			{if $subToc}
			<ul>
			{foreach from=$subToc->getTopics() item=currSubTopic}
				<li><a href="{$pageUrl}/help/view/{$currSubTopic->getId()}">{$currSubTopic->getTitle()}</a></li>
			{/foreach}
			</ul>
			{/if}
			</li>
			{else}
			<li><a href="{$pageUrl}/help/view/{$currTopic->getId()}">{$currTopic->getTitle()}</a></li>
			{/if}
		{/foreach}
	</ul>
</div>

