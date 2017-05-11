{**
 * templates/controllers/grid/announcements/form/announcementForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Announcement form to read/create/edit announcements.
 *}

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#announcementForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="announcementForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="grid.announcements.ManageAnnouncementGridHandler" op="updateAnnouncement"}">
	{csrf}
	{if $readOnly}
		{* Read only announcement *}

		{fbvFormArea id="announcementInfo"}
			{fbvFormSection}
				{$announcement->getLocalizedDescriptionShort()|strip_unsafe_html}
			{/fbvFormSection}
			{fbvFormSection title="announcement.posted"}
				{$announcement->getDatePosted()|escape}
			{/fbvFormSection}
			{fbvFormSection}
				{$announcement->getLocalizedDescription()|strip_unsafe_html}
			{/fbvFormSection}
		{/fbvFormArea}
	{else}
		{* Editable announcement *}

		{include file="controllers/notification/inPlaceNotification.tpl" notificationId="announcementFormNotification"}
		{fbvFormArea id="announcementInfo"}
			{if $announcement}
				<input type="hidden" name="announcementId" value="{$announcement->getId()|escape}" />
			{/if}
			{if $announcementTypes}
				{fbvElement type="select" id="typeId" from=$announcementTypes selected=$selectedTypeId label="manager.announcements.form.typeId" translate=false}
			{/if}
			{fbvFormSection title="manager.announcements.form.title" for="title" required="true"}
				{fbvElement type="text" multilingual="true" id="title" value=$title maxlength="255" required="true"}
			{/fbvFormSection}
			{fbvFormSection title="manager.announcements.form.descriptionShort" for="descriptionShort" required="true"}
				{fbvElement type="textarea" multilingual="true" id="descriptionShort" value=$descriptionShort label="manager.announcements.form.descriptionShortInstructions" required="true" rich=true height=$fbvStyles.height.SHORT}
			{/fbvFormSection}
			{fbvFormSection title="manager.announcements.form.description" for="description"}
				{fbvElement type="textarea" multilingual="true" id="description" value=$description label="manager.announcements.form.descriptionInstructions" rich=true}
			{/fbvFormSection}
			{fbvFormSection title="manager.announcements.form.dateExpire" for="dataExpire"}
				{fbvElement type="text" id="dateExpire" value=$dateExpire|date_format:$dateFormatShort label="manager.announcements.form.dateExpireInstructions" class="datepicker"}
			{/fbvFormSection}
		{/fbvFormArea}
		<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
		{fbvFormButtons id="announcementFormSubmit" submitText="common.save"}
	{/if}
</form>
