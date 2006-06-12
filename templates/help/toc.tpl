{**
 * toc.tpl
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Help table of contents.
 *
 * $Id$
 *}

<div style="padding-top: 0.5em;">
	<form action="{url op="search"}" method="post" style="display: inline">
	<input type="text" name="keyword" size="16" maxlength="60" value="{$helpSearchKeyword}" class="textField" />&nbsp;<input type="submit" value="{translate key="common.search"}" class="button" />
	</form>
</div>

<br />

<div><a href="{url op="toc"}">{translate key="help.toc"}</a></div>

<br />

{if $toc->getParentTopicId() && $toc->getParentTopicId() != $topic->getId()}
{translate key="help.contents"}&nbsp;<a href="{url op="view" path=$toc->getParentTopicId()|explode:"/"}">{translate key="help.upALevel"}</a>
<br />
{/if}

<div class="block">
	<span class="blockTitle">{$toc->getTitle()}</span>
	<ul>
		{foreach from=$toc->getTopics() item=currTopic}
			{if $currTopic->getId() == $topic->getId()}
			<li><a href="{url op="view" path=$currTopic->getId()|explode:"/"}" class="current">{$currTopic->getTitle()}</a>
			{if $subToc}
			<ul>
			{foreach from=$subToc->getTopics() item=currSubTopic}
				<li><a href="{url op="view" path=$currSubTopic->getId()|explode:"/"}">{$currSubTopic->getTitle()}</a></li>
			{/foreach}
			</ul>
			{/if}
			</li>
			{else}
			<li><a href="{url op="view" path=$currTopic->getId()|explode:"/"}">{$currTopic->getTitle()}</a></li>
			{/if}
		{/foreach}
	</ul>
</div>

