{**
 * topic.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Help topic.
 *
 * $Id$
 *}

{foreach name=sections from=$topic->getSections() item=section}
	<a name="section{math equation="counter - 1" counter=$smarty.foreach.sections.iteration}"></a>
	{if $section->getTitle()}<h4>{$section->getTitle()}</h4>{/if}
	<div>{eval var=$section->getContent()}</div>
	{if $smarty.foreach.sections.total > 1}
		{if !$smarty.foreach.sections.first}<div style="text-align:right;"><a href="#top" class="action">{translate key="common.top"}</a></div>{/if}
		{if !$smarty.foreach.sections.last}<div class="separator"></div>{/if}
	{/if}
{/foreach}



{if $relatedTopics}
<h5>{translate key="help.relatedTopics"}</h5>
<ul>
	{foreach from=$relatedTopics item=relatedTopic}
	<li><a href="{$pageUrl}/help/view/{$relatedTopic.id}">{$relatedTopic.title}</a></li>
	{/foreach}
</ul>
{/if}