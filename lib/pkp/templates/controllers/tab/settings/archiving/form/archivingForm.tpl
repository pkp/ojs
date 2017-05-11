{**
 * controllers/tab/settings/archiving/form/archivingForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Archiving settings form.
 *
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#archivingForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler', {ldelim}
			baseUrl: {$baseUrl|json_encode}
		{rdelim});
	{rdelim});
</script>

<form id="archivingForm" class="pkp_form" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.WebsiteSettingsTabHandler" op="saveFormData" tab="archiving"}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="archivingFormNotification"}

	{fbvFormArea title="manager.setup.enableArchive" id="lockssArea"}
		{fbvFormSection list="true" translate=false}
			{url|assign:"lockssUrl" router=$smarty.const.ROUTE_PAGE page="gateway" op="lockss"}
			{translate|assign:"enableLockssLabel" key="manager.setup.lockssEnable" lockssUrl=$lockssUrl}
			{fbvElement type="checkbox" id="enableLockss" value="1" checked=$enableLockss label=$enableLockssLabel translate=false}

			{url|assign:"clockssUrl" router=$smarty.const.ROUTE_PAGE page="gateway" op="clockss"}
			{translate|assign:"enableClockssLabel" key="manager.setup.clockssEnable" clockssUrl=$clockssUrl}
			{fbvElement type="checkbox" id="enableClockss" value="1" checked=$enableClockss label=$enableClockssLabel translate=false}
		{/fbvFormSection}
	{/fbvFormArea}

	<div class="lockss_description">
		<h3>{translate key="manager.setup.lockssTitle"}</h3>
		<p>
			{translate key="manager.setup.lockssDescription"}
		</p>
		<p>
			{url|assign:"lockssExistingArchiveUrl" router=$smarty.const.ROUTE_PAGE page="user" op="email" template="LOCKSS_EXISTING_ARCHIVE"}
			{url|assign:"lockssNewArchiveUrl" router=$smarty.const.ROUTE_PAGE page="user" op="email" template="LOCKSS_NEW_ARCHIVE"}
			{translate key="manager.setup.lockssRegister" lockssExistingArchiveUrl=$lockssExistingArchiveUrl lockssNewArchiveUrl=$lockssNewArchiveUrl}
		</p>
	</div>

	<div class="clockss_description">
		<h3>{translate key="manager.setup.clockssTitle"}</h3>
		<p>
			{translate key="manager.setup.clockssDescription"}
		</p>
		<p>
			{translate key="manager.setup.clockssRegister"}
		</p>
	</div>

	{fbvFormButtons id="archivingFormSubmit" submitText="common.save" hideCancel=true}
</form>
