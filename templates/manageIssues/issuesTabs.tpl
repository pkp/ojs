{**
 * templates/manageIssues/issues.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * The issue management page.
 *}

<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#issuesTabs').pkpHandler('$.pkp.controllers.TabHandler');
	{rdelim});
</script>
<div id="issuesTabs">
	<ul>
		<li><a name="futureIssues" href="#futureIssuesDiv">{translate key="editor.navigation.futureIssues"}</a></li>
		<li><a name="backIssues" href="#backIssuesDiv">{translate key="editor.navigation.issueArchive"}</a></li>
	</ul>
	<div id="futureIssuesDiv">
		{help file="issue-management.md" class="pkp_help_tab"}
		{capture assign=futureIssuesGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.issues.FutureIssueGridHandler" op="fetchGrid" escape=false}{/capture}
		{load_url_in_div id="futureIssuesGridContainer" url=$futureIssuesGridUrl}
	</div>
	<div id="backIssuesDiv">
		{help file="issue-management.md" class="pkp_help_tab"}
		{capture assign=backIssuesGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.issues.BackIssueGridHandler" op="fetchGrid" escape=false}{/capture}
		{load_url_in_div id="backIssuesGridContainer" url=$backIssuesGridUrl}
	</div>
</div>

