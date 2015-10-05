{**
 * templates/workflow/galleysTab.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Production workflow stage tabs.
 *}

<script type="text/javascript">
// Attach the JS file tab handler.
$(function() {ldelim}
	$('#representationTabs').pkpHandler(
		'$.pkp.controllers.tab.representations.RepresentationsTabHandler',
		{ldelim}
			tabsUrl:{url|json_encode router=$smarty.const.ROUTE_PAGE
				op='representationsTab'
				submissionId=$submission->getId()
				stageId=$smarty.const.WORKFLOW_STAGE_ID_PRODUCTION escape=false
			},
			{if $currentRepresentationTabId}currentRepresentationTabId: {$currentRepresentationTabId|json_encode},{/if}
			emptyLastTab: true,
		{rdelim}
	);
{rdelim});
</script>
<div id="representationTabs" class="pkp_controllers_tab">
	<ul>
		{foreach from=$representations item=representation}
			<li>
				<a id="representation{$representation->getId()|escape}"
					href="{url router=$smarty.const.ROUTE_PAGE page="workflow" op="fetchRepresentation"
					representationId=$representation->getId()
					submissionId=$representation->getSubmissionId()
					stageId=$smarty.const.WORKFLOW_STAGE_ID_PRODUCTION}">{$representation->getLocalizedName()|escape}</a>
			</li>
		{/foreach}
	</ul>
</div>
