{**
 * topic.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Help topic.
 *
 * $Id$
 *}

{foreach name=sections from=$topic->getSections() item=section}
	<a name="section{math equation="counter - 1" counter=$smarty.foreach.sections.iteration}"></a>
	<h4>{$section->getTitle()}</h4>
	<div>{eval var=$section->getContent()}</div>
	{if $smarty.foreach.sections.total > 1}
		{if !$smarty.foreach.sections.first}<div><a href="#top">{translate key="common.top"}</a></div>{/if}
		{if !$smarty.foreach.sections.last}<div class="separator"></div>{/if}
	{/if}
{/foreach}
