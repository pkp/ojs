{**
 * controllers/modals/submissionMetadata/form/issueEntryTabs.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
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
{if not $hideHelp}<p class="pkp_help">{translate key="issue.manage.entryDescription"}</p>{/if}
<div id="newIssueEntryTabs">
	<ul>
		<li>
			<a name="submission" href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.issueEntry.IssueEntryTabHandler" tab="submission" op="submissionMetadata" submissionId=$submissionId stageId=$stageId tabPos="0"}">{translate key="submission.issueEntry.submissionMetadata"}</a>
		</li>
		<li>
			<a name="catalog" href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.issueEntry.IssueEntryTabHandler" tab="publication" op="publicationMetadata" submissionId=$submissionId stageId=$stageId tabPos="1"}">{translate key="submission.issueEntry.publicationMetadata"}</a>
		</li>
</ul>
