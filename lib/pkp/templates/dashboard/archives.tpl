{**
 * templates/dashboard/archives.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Dashboard archived submissions tab.
 *}

{* Help File *}
{help file="submissions.md" section="archives" class="pkp_help_tab"}

{url|assign:archivedSubmissionsListGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.submissions.archivedSubmissions.ArchivedSubmissionsListGridHandler" op="fetchGrid" escape=false}
{load_url_in_div id="archivedSubmissionsListGridContainer" url=$archivedSubmissionsListGridUrl}
