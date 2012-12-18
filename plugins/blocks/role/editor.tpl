{**
 * editor.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Editor navigation sidebar.
 * Lists active assignments and editor functions.
 *
 * $Id$
 *}
<div class="block" id="sidebarEditor">
	<span class="blockTitle">{translate key="user.role.editor"}</span>
	
	<span class="blockSubtitle">{translate key="article.submissions"}</span>
	<ul>
		<li><a href="{url op="submissions" path="submissionsUnassigned"}">{translate key="common.queue.short.submissionsUnassigned"}</a>&nbsp;({if $submissionsCount[0]}{$submissionsCount[0]}{else}0{/if})</li>
		<li><a href="{url op="submissions" path="submissionsInReview"}">{translate key="common.queue.short.submissionsInReview"}</a>&nbsp;({if $submissionsCount[1]}{$submissionsCount[1]}{else}0{/if})</li>
		<li><a href="{url op="submissions" path="submissionsInEditing"}">{translate key="common.queue.short.submissionsInEditing"}</a>&nbsp;({if $submissionsCount[2]}{$submissionsCount[2]}{else}0{/if})</li>
		<li><a href="{url op="submissions" path="submissionsArchives"}">{translate key="common.queue.short.submissionsArchives"}</a></li>
	</ul>
	
	<span class="blockSubtitle">{translate key="editor.navigation.issues"}</span>
	<ul>
		{* 20111201 BLH Don't display 'Create Issue' for UEE *}
		{if $journalPath != 'nelc_uee' || $isSiteAdmin}
			<li><a href="{url op="createIssue"}">{translate key="editor.navigation.createIssue"}</a></li>
		{/if}
		<li><a href="{url op="notifyUsers"}">{translate key="editor.notifyUsers"}</a></li>
        {* 20111201 BLH Display 'Published Content' & 'Unpublished Content for UCLA Encyclopedia of Egyptology *}
		{* 20120502 LS Display only 'Published Content' for UEE *}
        {if $journalPath == 'nelc_uee'}
        	<li><a href="{url op="backIssues"}">{translate key="editor.navigation.publishedContent"}</a></li>
        {else}
        	<li><a href="{url op="futureIssues"}">{translate key="editor.navigation.futureIssues"}</a></li>
        	<li><a href="{url op="backIssues"}">{translate key="editor.navigation.issueArchive"}</a></li>
        {/if}		
	</ul>
</div>
