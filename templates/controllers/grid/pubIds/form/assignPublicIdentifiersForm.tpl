{**
 * templates/controllers/grid/pubIds/form/assignPublicIdentifiersForm.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 *} 
 <script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#assignPublicIdentifierForm').pkpHandler(
			'$.pkp.controllers.form.AjaxFormHandler',
			{ldelim}
				trackFormChanges: true
			{rdelim}
		);
	{rdelim});
</script>
{if $pubObject instanceof Issue}
	<form class="pkp_form" id="assignPublicIdentifierForm" method="post" action="{url component="grid.issues.FutureIssueGridHandler" op="publishIssue" escape=false}">
		<input type="hidden" name="issueId" value="{$pubObject->getId()|escape}" />
		<input type="hidden" name="confirmed" value=true />
		{assign var=hideCancel value=false}
{elseif $pubObject instanceof Article}
	<form class="pkp_form" id="assignPublicIdentifierForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.issueEntry.IssueEntryTabHandler" op="assignPubIds" escape=false}">
		<input type="hidden" name="submissionId" value="{$pubObject->getId()|escape}" />
		<input type="hidden" name="stageId" value="{$formParams.stageId|escape}" />
		{assign var=hideCancel value=true}
{/if}
{fbvFormArea id="confirmationText"}
	<p>{$confirmationText}</p>
{/fbvFormArea}
{if $approval}
	{foreach from=$pubIdPlugins item=pubIdPlugin}
		{assign var=pubIdAssignFile value=$pubIdPlugin->getPubIdAssignFile()}
		{assign var=canBeAssigned value=$pubIdPlugin->canBeAssigned($pubObject)}
		{include file="$pubIdAssignFile" pubIdPlugin=$pubIdPlugin pubObject=$pubObject canBeAssigned=$canBeAssigned}
	{/foreach}
{/if}
{fbvFormButtons id="assignPublicIdentifierForm" submitText="common.ok" hideCancel=$hideCancel}
</form>