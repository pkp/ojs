{**
 * templates/controllers/grid/issues/issue.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * The "edit issue" tabset.
 *}
<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#editIssueTabs').pkpHandler('$.pkp.controllers.TabHandler');
	{rdelim});
</script>
<div id="editIssueTabs">
	<ul>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT op="issueToc" issueId=$issueId}">{translate key="issue.toc"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT op="editIssueData" issueId=$issueId}">{translate key="editor.issues.issueData"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT op="issueGalleys" issueId=$issueId}">{translate key="editor.issues.galleys"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT op="editCover" issueId=$issueId}">{translate key="editor.issues.cover"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT op="identifiers" issueId=$issueId}">{translate key="editor.issues.identifiers"}</a></li>
	</ul>
</div>
