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

<div class="sidebarBlockTitle">{translate key="editor.navigation.editorialProcess"}</div>
<div class="sidebarBlock">
<div class="sidebarBlockSubtitle">{translate key="editor.navigation.submissions"}</div>
<ul class="sidebar">
	<li><a href="{$pageUrl}/editor/index/submissionsUnassigned">{translate key="editor.navigation.unassigned"}{if $submissionsCount[0]}&nbsp;({$submissionsCount[0]}){/if}</a></li>
	<li><a href="{$pageUrl}/editor/index/submissionsInReview">{translate key="editor.navigation.submissionsInReview"}{if $submissionsCount[1]}&nbsp;({$submissionsCount[1]}){/if}</a></li>
	<li><a href="{$pageUrl}/editor/index/submissionsInEditing">{translate key="editor.navigation.submissionsInEditing"}{if $submissionsCount[2]}&nbsp;({$submissionsCount[2]}){/if}</a></li>
	<li><a href="{$pageUrl}/editor/schedulingQueue">{translate key="editor.navigation.submissionsInScheduling"}{if $submissionsCount[3]}&nbsp;({$submissionsCount[3]}){/if}</a></li>
</ul>

<br />

<div class="sidebarBlockSubtitle">{translate key="editor.navigation.issues"}</div>
<ul class="sidebar">
	<li><a href="{$pageUrl}/editor/issueManagement">{translate key="editor.navigation.liveIssues"}</a></li>
	<li><a href="{$pageUrl}/editor/createIssue">{translate key="editor.navigation.createIssue"}</a></li>
</ul>

<br />

<div class="sidebarBlockSubtitle">{translate key="editor.navigation.archives"}</div>
<ul class="sidebar">
	<li><a href="{$pageUrl}/editor/index/submissionsArchives">{translate key="editor.navigation.submissionArchive"}</a></li>
	<li><a href="{$pageUrl}/editor/backIssues">{translate key="editor.navigation.issueArchive"}</a></li>
</ul>
</div>
