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

<div class="sidebarBlockTitle">{translate key="editor.journalEditor"}</div>
<div class="sidebarBlock">
<div class="sidebarBlockSubtitle">{translate key="editor.navigation.submissions"}</div>
<ul class="sidebar">
	<li><a href="{$pageUrl}/editor/index/submissionsUnassigned">{translate key="editor.navigation.unassigned"}&nbsp;({if $submissionsCount[0]}{$submissionsCount[0]}{else}0{/if})</a></li>
	<li><a href="{$pageUrl}/editor/index/submissionsInReview">{translate key="editor.navigation.submissionsInReview"}&nbsp;({if $submissionsCount[1]}{$submissionsCount[1]}{else}0{/if})</a></li>
	<li><a href="{$pageUrl}/editor/index/submissionsInEditing">{translate key="editor.navigation.submissionsInEditing"}&nbsp;({if $submissionsCount[2]}{$submissionsCount[2]}{else}0{/if})</a></li>
	<li><a href="{$pageUrl}/editor/schedulingQueue">{translate key="editor.navigation.submissionsInScheduling"}&nbsp;({if $submissionsCount[3]}{$submissionsCount[3]}{else}0{/if})</a></li>
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
