{**
 * navsidebar.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Reviewer navigation sidebar.
 *
 * $Id$
 *}

{* Note that if the user has come in via an access key, the submission counts won't
   be available as the user isn't actually logged in. Therefore we must check to
   see if the user object actually exists before displaying submission counts. *}

{if $isUserLoggedIn}
	<div class="block" id="sidebarReviewer">
		<span class="blockTitle">{translate key="user.role.reviewer"}</span>
		<span class="blockSubtitle">{translate key="article.submissions"}</span>
		<ul>
			<li><a href="{url op="index" path="active"}">{translate key="common.queue.short.active"}</a>&nbsp;({if $submissionsCount[0]}{$submissionsCount[0]}{else}0{/if})</li>
			<li><a href="{url op="index" path="completed"}">{translate key="common.queue.short.completed"}</a>&nbsp;({if $submissionsCount[1]}{$submissionsCount[1]}{else}0{/if})</li>
		</ul>
	</div>
{/if}
