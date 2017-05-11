{**
 * templates/dashboard/active.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Dashboard active submissions tab.
 *}

{* Help File *}
{help file="submissions.md" section="active" class="pkp_help_tab"}

<!-- Archived submissions grid: Show all archived submissions -->
{url|assign:activeSubmissionsListGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.submissions.activeSubmissions.ActiveSubmissionsListGridHandler" op="fetchGrid" escape=false}
{load_url_in_div id="activeSubmissionsListGridContainer" url=$activeSubmissionsListGridUrl}
