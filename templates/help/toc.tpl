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

{if $toc->getPrevTopicId()}
<a href="{$pageUrl}/help/view/{$toc->getPrevTopicId()}" id="prevToc">&uArr; {translate key="common.up"}</a>
<br />
{/if}

<span id="tocTitle">{$toc->getTitle()}</span>

<br />

<ul id="toc">
{foreach from=$toc->getTopics() item=topic}
	<li class="toc{if $topic->getId() == $currentTopicId}Selected{/if}"><a href="{$pageUrl}/help/view/{$topic->getId()}">{$topic->getTitle()}</a></li>
{/foreach}
</ul>

{if $toc->getId() != '000000'}
&#187; <a href="{$pageUrl}/help/view/000000" id="indexLink">{translate key="help.helpIndex"}</a>
{/if}
