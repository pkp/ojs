{**
 * templates/controllers/informationCenter/submissionHistory.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Information Center submission history tab.
 *}

{help file="editorial-workflow.md" section="editorial-history" class="pkp_help_tab"}

{url|assign:submissionHistoryGridUrl params=$gridParameters router=$smarty.const.ROUTE_COMPONENT component="grid.eventLog.SubmissionEventLogGridHandler" op="fetchGrid" escape=false}
{load_url_in_div id="submissionHistoryGridContainer" url=$submissionHistoryGridUrl}
