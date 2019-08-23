{**
 * controllers/modals/submissionMetadata/form/issueEntryTabs.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display a submission's issue entry form.
 *
 *}

<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#newIssueEntryTabs').pkpHandler(
				'$.pkp.controllers.tab.issueEntry.IssueEntryTabHandler',
				{ldelim}
					{if $selectedTab}selected:{$selectedTab},{/if}
					{if $tabsUrl}tabsUrl:'{$tabsUrl}',{/if}
					{if $tabContentUrl}tabContentUrl:'{$tabContentUrl}',{/if}
					emptyLastTab: true
				{rdelim});
	{rdelim});
</script>
<div id="newIssueEntryTabs">
	<ul>
		<li>
			<a name="submission" href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.issueEntry.IssueEntryTabHandler" tab="submission" op="submissionMetadata" submissionId=$submissionId stageId=$stageId submissionVersion=$submissionVersion tabPos="0"}">{translate key="submission.issueEntry.submissionMetadata"}</a>
		</li>
		<li>
			<a name="catalog" href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.issueEntry.IssueEntryTabHandler" tab="identifiers" op="identifiers" submissionId=$submissionId stageId=$stageId submissionVersion=$submissionVersion tabPos="1"}">{translate key="submission.identifiers"}</a>
		</li>
		{if $citationsEnabled}
			<li>
				<a name="citations" href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.issueEntry.IssueEntryTabHandler" tab="citations" op="citations" submissionId=$submissionId stageId=$stageId submissionVersion=$submissionVersion tabPos="2"}">{translate key="submission.citations"}</a>
			</li>
		{/if}
</ul>
