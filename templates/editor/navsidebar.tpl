{**
 * navsidebar.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Editor navigation sidebar.
 * Lists active assignments and editor functions.
 *
 * $Id$
 *}

<div class="block">
	<span class="blockTitle">{translate key="user.role.editor"}</span>
	
	<span class="blockSubtitle">{translate key="article.submissions"}</span>
	<ul>
		<li><a href="{$pageUrl}/editor/submissions/submissionsUnassigned">{translate key="common.queue.short.submissionsUnassigned"}</a>&nbsp;({if $submissionsCount[0]}<strong>{$submissionsCount[0]}</strong>{else}0{/if})</li>
		<li><a href="{$pageUrl}/editor/submissions/submissionsInReview">{translate key="common.queue.short.submissionsInReview"}</a>&nbsp;({if $submissionsCount[1]}<strong>{$submissionsCount[1]}</strong>{else}0{/if})</li>
		<li><a href="{$pageUrl}/editor/submissions/submissionsInEditing">{translate key="common.queue.short.submissionsInEditing"}</a>&nbsp;({if $submissionsCount[2]}<strong>{$submissionsCount[2]}</strong>{else}0{/if})</li>
		<li><a href="{$pageUrl}/editor/submissions/submissionsArchives">{translate key="common.queue.short.submissionsArchives"}</a></li>
	</ul>
	
	<span class="blockSubtitle">{translate key="editor.navigation.issues"}</span>
	<ul>
		<li><a href="{$pageUrl}/editor/createIssue">{translate key="editor.navigation.createIssue"}</a></li>
		<li><a href="{$pageUrl}/editor/schedulingQueue">{translate key="common.queue.short.submissionsInScheduling"}</a>&nbsp;({if $submissionsCount[3]}<strong>{$submissionsCount[3]}</strong>{else}0{/if})</li>
		<li><a href="{$pageUrl}/editor/notifyUsers">{translate key="editor.notifyUsers"}</a></li>
		<li><a href="{$pageUrl}/editor/issueToc">{translate key="editor.navigation.futureIssues"}</a></li>
		<li><a href="{$pageUrl}/editor/backIssues">{translate key="editor.navigation.issueArchive"}</a></li>
	</ul>
</div>
