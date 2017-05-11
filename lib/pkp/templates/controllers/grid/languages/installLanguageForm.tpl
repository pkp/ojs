{**
 * controllers/grid/languages/installLanguageForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to install languages.
 *}

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#installLanguageForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="installLanguageForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="grid.admin.languages.AdminLanguageGridHandler" op="saveInstallLocale"}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="installLanguageFormNotification"}

	{fbvFormArea id="availableLocalesFormArea" title="admin.languages.availableLocales"}
		{fbvFormSection list="true" description="admin.languages.installNewLocalesInstructions"}
			{foreach name=locales from=$notInstalledLocales item=locale}
				{fbvElement type="checkbox" id="locale-$locale" name="localesToInstall[$locale]" value=$locale label=$allLocales.$locale translate=false}
			{foreachelse}
				<p>{translate key="admin.languages.noLocalesAvailable"}</p>
			{/foreach}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="downloadLocaleFormArea" title="admin.languages.downloadLocales"}
		{fbvFormSection list="true"}
			{if $downloadAvailable}
				<ul>
				{foreach name=downloadableLocaleLinks from=$downloadableLocaleLinks item=localeLink}
					<li>{include file="linkAction/linkAction.tpl" action=$localeLink}</li>
				{foreachelse}
					<li><p>{translate key="admin.languages.noLocalesToDownload"}</p></li>
				{/foreach}
				</ul>
			{else}
				<p>{translate key="admin.languages.downloadUnavailable"}</p>
			{/if}
		{/fbvFormSection}
	{/fbvFormArea}

	{if not empty($notInstalledLocales)}
		{fbvFormButtons id="mastheadFormSubmit" submitText="common.save"}
	{/if}
</form>
