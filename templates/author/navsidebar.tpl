{**
 * navsidebar.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Author navigation sidebar.
 *
 * $Id$
 *}

<div class="sidebarBlockTitle">{translate key="author.journalAuthor"}</div>
<div class="sidebarBlock">
<div class="sidebarBlockSubtitle">{translate key="submission.submissions"}</div>
<ul class="sidebar">
	<li><a href="{$pageUrl}/author/index/active">{translate key="common.active"}&nbsp;({if $submissionsCount[0]}{$submissionsCount[0]}{else}0{/if})</a></li>
	<li><a href="{$pageUrl}/author/index/completed">{translate key="common.completed"}&nbsp;({if $submissionsCount[1]}{$submissionsCount[1]}{else}0{/if})</a></li>
	<li><a href="{$pageUrl}/author/submit">{translate key="author.submit"}</a></li>
</ul>
</div>
