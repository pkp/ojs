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

<div class="block">
	<span class="blockTitle">{translate key="sectionEditor.journalSectionEditor"}</span>
	<span class="blockSubtitle">{translate key="editor.navigation.submissions"}</span>
	<ul>
		<li><a href="{$pageUrl}/sectionEditor/index/submissionsInReview">{translate key="editor.navigation.submissionsInReview"}</a>&nbsp;({if $submissionsCount[0]}<strong>{$submissionsCount[0]}</strong>{else}0{/if})</li>
		<li><a href="{$pageUrl}/sectionEditor/index/submissionsInEditing">{translate key="editor.navigation.submissionsInEditing"}</a>&nbsp;({if $submissionsCount[1]}<strong>{$submissionsCount[1]}</strong>{else}0{/if})</li>
		<li><a href="{$pageUrl}/sectionEditor/index/submissionsArchives">{translate key="editor.navigation.archives"}</a></li>
	</ul>
</div>
