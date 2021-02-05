{**
 * templates/manageIssues/issues.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * The issue management page.
 *}
{extends file="layouts/backend.tpl"}

{block name="page"}
	<h1 class="app__pageHeading">
		{translate key="editor.navigation.issues"}
	</h1>

	<tabs :track-history="true">
		<tab id="future" label="{translate key="editor.navigation.futureIssues"}">
			{help file="issue-management" class="pkp_help_tab"}
			{capture assign=futureIssuesGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.issues.FutureIssueGridHandler" op="fetchGrid" escape=false}{/capture}
			{load_url_in_div id="futureIssuesGridContainer" url=$futureIssuesGridUrl}
		</tab>
		<tab id="back" label="{translate key="editor.navigation.issueArchive"}">
			{help file="issue-management" class="pkp_help_tab"}
			{capture assign=backIssuesGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.issues.BackIssueGridHandler" op="fetchGrid" escape=false}{/capture}
			{load_url_in_div id="backIssuesGridContainer" url=$backIssuesGridUrl}
		</tab>
	</tabs>
{/block}
