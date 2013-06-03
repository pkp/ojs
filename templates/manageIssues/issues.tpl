{**
 * templates/manageIssues/issues.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * The issue management page.
 *}
{strip}
{assign var="pageTitle" value="editor.navigation.issues"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#issuesTabs').pkpHandler(
				'$.pkp.controllers.TabHandler');
	{rdelim});
</script>
<div id="issuesTabs">
	<ul>
		<li><a href="#futureIssuesDiv">{translate key="editor.navigation.futureIssues"}</a></li>
		<li><a href="#backIssuesDiv">{translate key="editor.navigation.issueArchive"}</a></li>
	</ul>
	<div id="futureIssuesDiv">
		{url|assign:futureIssuesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.issues.FutureIssueGridHandler" op="fetchGrid" escape=false}
		{load_url_in_div id="futureIssuesGridContainer" url=$futureIssuesGridUrl}
	</div>
	<div id="backIssuesDiv">
		{url|assign:backIssuesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.issues.BackIssueGridHandler" op="fetchGrid" escape=false}
		{load_url_in_div id="backIssuesGridContainer" url=$backIssuesGridUrl}
	</div>
</div>

{include file="common/footer.tpl"}
