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

<a name="top"></a>
<span id="topicTitle">{$topic->getTitle()}</span>

<br />

{if count($topic->getSections()) > 1}
	<ul id="sections">
	{foreach name=sections from=$topic->getSections() item=section}
		<li><a href="#section{$smarty.foreach.sections.iteration}">{$section->getTitle()}</a></li>
	{/foreach}
	</ul>
{/if}

<br />

{foreach name=sections from=$topic->getSections() item=section}
<a name="section{$smarty.foreach.sections.iteration}"></a>
<div class="sectionTitle">{$section->getTitle()}</div>
<div class="sectionContent">{$section->getContent()}</div>
{if $smarty.foreach.sections.total > 1}
<div class="sectionTopLink"><a href="#top">{translate key="common.top"}</a></div>
{/if}
{/foreach}
