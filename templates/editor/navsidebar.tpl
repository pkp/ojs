{**
 * navsidebar.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Editor navigation sidebar.
 *
 * $Id$
 *}

<div class="sidebarBlockTitle">{translate key="editor.navigation.editorAdministration"}</div>
<div class="sidebarBlock">
<div class="sidebarBlockSubtitle">{translate key="editor.navigation.submissions"}</div>
<ul class="sidebar">
	<li><a href="{$pageUrl}/submissions">{translate key="editor.navigation.submissionsInReview"}</a></li>
	<li><a href="{$pageUrl}/submissions">{translate key="editor.navigation.submissionsInEditing"}</a></li>
	<li><a href="{$pageUrl}/schedulingQueue">{translate key="editor.navigation.submissionsInScheduling"}</a></li>
</ul>

<br />

<div class="sidebarBlockSubtitle">{translate key="editor.navigation.issues"}</div>
<ul class="sidebar">
	<li><a href="{$pageUrl}/issues">{translate key="editor.navigation.liveIssues"}</a></li>
	<li><a href="{$pageUrl}/issues">{translate key="editor.navigation.createIssue"}</a></li>
</ul>

<br />

<div class="sidebarBlockSubtitle">{translate key="editor.navigation.archives"}</div>
<ul class="sidebar">
	<li><a href="{$pageUrl}/submissionArchive">{translate key="editor.navigation.submissionArchive"}</a></li>
	<li><a href="{$pageUrl}/issues">{translate key="editor.navigation.issueArchive"}</a></li>
</ul>
</div>
