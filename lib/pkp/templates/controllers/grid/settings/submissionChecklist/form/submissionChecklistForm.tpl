
{**
 * templates/controllers/grid/settings/submissionChecklist/form/submissionChecklists.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * SubmissionChecklists grid form
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#editSubmissionChecklistForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="editSubmissionChecklistForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="grid.settings.submissionChecklist.SubmissionChecklistGridHandler" op="updateItem"}">
{csrf}

{include file="controllers/notification/inPlaceNotification.tpl" notificationId="submissionChecklistFormNotification"}


{fbvFormArea id="checklist"}
	{fbvFormSection title="grid.submissionChecklist.column.checklistItem" required="true" for="checklistItem"}
		{fbvElement type="textarea" multilingual="true" name="checklistItem" id="checklistItem" value=$checklistItem required="true"}
	{/fbvFormSection}
{/fbvFormArea}
{if $gridId != null}
	<input type="hidden" name="gridId" value="{$gridId|escape}" />
{/if}
{if $rowId != null}
	<input type="hidden" name="rowId" value="{$rowId|escape}" />
{/if}
{if $submissionChecklistId != null}
	<input type="hidden" name="submissionChecklistId" value="{$submissionChecklistId|escape}" />
{/if}
{fbvFormButtons submitText="common.save"}
</form>
