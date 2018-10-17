{**
 * controllers/tab/settings/appearance/form/appearanceForm.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Website appearance management form.
 *
 *}

{help file="settings.md" section="website" class="pkp_help_tab"}

{include file="core:controllers/tab/settings/appearance/form/setup.tpl"}
<form id="appearanceForm" class="pkp_form" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.WebsiteSettingsTabHandler" op="saveFormData" tab="appearance"}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="appearanceFormNotification"}
	{include file="controllers/tab/settings/wizardMode.tpl" wizardMode=$wizardMode}

	{* Header *}
	{include file="core:controllers/tab/settings/appearance/form/header.tpl"}

	{* Footer *}
	{include file="core:controllers/tab/settings/appearance/form/footer.tpl"}

	{* Theme and stylesheet *}
	{include file="core:controllers/tab/settings/appearance/form/theme.tpl"}
	{include file="core:controllers/tab/settings/appearance/form/stylesheet.tpl"}

	{* Sidebar *}
	{include file="core:controllers/tab/settings/appearance/form/sidebar.tpl"}

	{* Homepage Image *}
	{include file="core:controllers/tab/settings/appearance/form/homepageImage.tpl"}

	{* Favicon *}
	{include file="core:controllers/tab/settings/appearance/form/favicon.tpl"}

	{* Additional Homepage Content *}
	{include file="core:controllers/tab/settings/appearance/form/additionalHomepageContent.tpl"}

	{* List Display Options *}
	{include file="core:controllers/tab/settings/appearance/form/lists.tpl"}

	{* Journal thumbnail *}
	{assign var="journal_thumbnail_field_id" value=$uploadImageLinkActions.journalThumbnail->getId()}
	{fbvFormArea id="thumbnailArea" class="$wizard_class"}
		{fbvFormSection for=$journal_thumbnail_field_id label="manager.setup.journalThumbnail" description="manager.setup.journalThumbnail.description"}
			<div id="journalThumbnail">
				{$imagesViews.journalThumbnail}
			</div>
			<div id="{$uploadImageLinkActions.journalThumbnail->getId()}" class="pkp_linkActions">
				{include file="linkAction/linkAction.tpl" action=$uploadImageLinkActions.journalThumbnail contextId="appearanceForm"}
			</div>
		{/fbvFormSection}
	{/fbvFormArea}

	{* Save button *}
	{if !$wizardMode}
		{fbvFormButtons id="appearanceFormSubmit" submitText="common.save" hideCancel=true}
	{/if}
</form>
