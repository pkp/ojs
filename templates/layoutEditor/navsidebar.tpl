{**
 * navsidebar.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * LayoutEditor navigation sidebar.
 *
 * $Id$
 *}

<div class="sidebarBlockTitle">{translate key="layoutEditor.journalLayoutEditor"}</div>
<div class="sidebarBlock">
<div class="sidebarBlockSubtitle">{translate key="article.submissions"}</div>
<ul class="sidebar">
	<li><a href="{$pageUrl}/layoutEditor/index/active">{translate key="common.active"}&nbsp;({if $submissionsCount[0]}{$submissionsCount[0]}{else}0{/if})</a></li>
	<li><a href="{$pageUrl}/layoutEditor/index/completed">{translate key="common.completed"}&nbsp;({if $submissionsCount[1]}{$submissionsCount[1]}{else}0{/if})</a></li>
</ul>
</div>
