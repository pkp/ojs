{**
 * plugins/citationFormats/abnt/settingsForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Contributed by Lepidus Tecnologia
 *
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * ABNT Citation plugin settings
 *
 *}
<div id="abntCitationSettings">
<div id="description">{translate key="plugins.citationFormats.abnt.manager.settings.description"}</div>

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#abntSetupForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>
<form class="pkp_form" name="abntSetupForm" id="abntSetupForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="citationFormats" plugin=$pluginName verb="settings" save=true}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="abntSetupFormNotification"}

	{fbvFormArea id="abntSettingsFormArea"}
		{fbvFormSection}
			{fbvElement type="text" id="location" name="location" value=$location label="plugins.citationFormats.abnt.manager.settings.location" multilingual=true}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons}
</div>
