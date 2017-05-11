{**
 * templates/controllers/grid/announcements/form/announcementTypeForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to read/create/edit announcement types.
 *}

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#announcementTypeForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="announcementTypeForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="grid.announcements.AnnouncementTypeGridHandler" op="updateAnnouncementType"}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="announcementTypeFormNotification"}
	{fbvFormArea id="announcementTypeInfo"}
		{if $typeId}
			<input type="hidden" name="announcementTypeId" value="{$typeId|escape}" />
		{/if}
		{fbvFormSection title="manager.announcementTypes.form.typeName" for="title" required="true"}
			{fbvElement type="text" multilingual="true" id="name" value=$name maxlength="255" required="true"}
		{/fbvFormSection}
	{/fbvFormArea}
	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
	{fbvFormButtons id="announcementTypeFormSubmit" submitText="common.save"}
</form>
