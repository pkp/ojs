{**
 * templates/dashboard/archives.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Dashboard submissions tab.
 *}

<!-- Archived submissions grid: Show all archived submissions -->
{if array_intersect(array(ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_REVIEWER, ROLE_ID_ASSISTANT), $userRoles)}
	{url|assign:archivedSubmissionsListGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.submissions.archivedSubmissions.ArchivedSubmissionsListGridHandler" op="fetchGrid"}
	{load_url_in_div id="archivedSubmissionsListGridContainer" url="$archivedSubmissionsListGridUrl"}
{/if}
