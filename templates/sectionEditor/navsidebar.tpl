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

<div class="sidebarBlockTitle">{translate key="editor.navigation.sectionEditorialProcess"}</div>
<div class="sidebarBlock">
<div class="sidebarBlockSubtitle">{translate key="editor.navigation.submissions"}</div>
<ul class="sidebar">
	<li><a href="{$pageUrl}/sectionEditor/index/submissionsInReview">{translate key="editor.navigation.submissionsInReview"}{if $submissionsCount[0]}&nbsp;({$submissionsCount[0]}){/if}</a></li>
	<li><a href="{$pageUrl}/sectionEditor/index/submissionsInEditing">{translate key="editor.navigation.submissionsInEditing"}{if $submissionsCount[1]}&nbsp;({$submissionsCount[1]}){/if}</a></li>
	<li><a href="{$pageUrl}/sectionEditor/index/submissionsArchives">{translate key="editor.navigation.archives"}</a></li>
</ul>
</div>
