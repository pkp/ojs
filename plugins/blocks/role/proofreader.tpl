{**
 * proofreader.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Proofreader navigation sidebar.
 *
 * $Id$
 *}
<div class="block" id="sidebarProofreader">
	<span class="blockTitle">{translate key="user.role.proofreader"}</span>
	<span class="blockSubtitle">{translate key="article.submissions"}</span>
	<ul>
		<li><a href="{url op="index" path="active"}">{translate key="common.queue.short.active"}</a>&nbsp;({if $submissionsCount[0]}{$submissionsCount[0]}{else}0{/if})</li>
		<li><a href="{url op="index" path="completed"}">{translate key="common.queue.short.completed"}</a>&nbsp;({if $submissionsCount[1]}{$submissionsCount[1]}{else}0{/if})</li>
	</ul>
</div>
