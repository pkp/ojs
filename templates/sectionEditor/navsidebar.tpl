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

<div class="sidebarBlockTitle">{translate key="editor.navigation.sectionEditorAdministration"}</div>
<div class="sidebarBlock">
<div class="sidebarBlockSubtitle">{translate key="editor.navigation.submissions"}</div>
<ul class="sidebar">
	<li><a href="{$pageUrl}/sectionEditor/index/submissionsInReview">{translate key="editor.navigation.submissionsInReview"}</a></li>
	<li><a href="{$pageUrl}/sectionEditor/index/submissionsInEditing">{translate key="editor.navigation.submissionsInEditing"}</a></li>
	<li><a href="{$pageUrl}/sectionEditor/index/submissionsArchives">{translate key="editor.navigation.archives"}</a></li>
</ul>
</div>
