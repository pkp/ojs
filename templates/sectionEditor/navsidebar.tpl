{**
 * navsidebar.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Section Editor navigation sidebar.
 *
 * $Id$
 *}

<div class="sidebarBlockTitle">{translate key="sectionEditor.journalSectionEditor"}</div>
<div class="sidebarBlock">
<div class="sidebarBlockSubtitle">{translate key="editor.navigation.submissions"}</div>
<ul class="sidebar">
	<li><a href="{$pageUrl}/sectionEditor/index/submissionsInReview">{translate key="editor.navigation.submissionsInReview"}&nbsp;({if $submissionsCount[0]}{$submissionsCount[0]}{else}0{/if})</a></li>
	<li><a href="{$pageUrl}/sectionEditor/index/submissionsInEditing">{translate key="editor.navigation.submissionsInEditing"}&nbsp;({if $submissionsCount[1]}{$submissionsCount[1]}{else}0{/if})</a></li>
	<li><a href="{$pageUrl}/sectionEditor/index/submissionsArchives">{translate key="editor.navigation.archives"}</a></li>
</ul>
</div>
