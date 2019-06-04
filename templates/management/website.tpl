{**
 * templates/management/website.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * The website settings page.
 *}
{include file="common/header.tpl" pageTitle="manager.website.title"}

{assign var="uuid" value=""|uniqid|escape}
<div id="settings-context-{$uuid}">
	<tabs>
		<tab id="appearance" name="{translate key="manager.website.appearance"}">
			{help file="settings" section="website" class="pkp_help_tab"}
			<tabs :options="{ useUrlFragment: false }" class="tabs-component--side">
				<tab name="{translate key="manager.setup.theme"}">
					<theme-form
						v-bind="components.{$smarty.const.FORM_THEME}"
						@set="set"
					/>
				</tab>
				<tab name="{translate key="navigation.setup"}">
					<pkp-form
						v-bind="components.{$smarty.const.FORM_APPEARANCE_SETUP}"
						@set="set"
					/>
				</tab>
				<tab name="{translate key="manager.setup.advanced"}">
					<pkp-form
						v-bind="components.{$smarty.const.FORM_APPEARANCE_ADVANCED}"
						@set="set"
					/>
				</tab>
				{call_hook name="Template::Settings::website::appearance"}
			</tabs>
		</tab>
		<tab id="setup" name="{translate key="navigation.setup"}">
			{help file="settings" section="website" class="pkp_help_tab"}
			<tabs :options="{ useUrlFragment: false }" class="tabs-component--side">
				<tab name="{translate key="common.languages"}">
					{capture assign=languagesUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.settings.languages.ManageLanguageGridHandler" op="fetchGrid" escape=false}{/capture}
					{load_url_in_div id="languageGridContainer" url=$languagesUrl}
				</tab>
				<tab name="{translate key="manager.navigationMenus"}">
					{capture assign=navigationMenusGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.navigationMenus.NavigationMenusGridHandler" op="fetchGrid" escape=false}{/capture}
					{load_url_in_div id="navigationMenuGridContainer" url=$navigationMenusGridUrl}
					{capture assign=navigationMenuItemsGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.navigationMenus.NavigationMenuItemsGridHandler" op="fetchGrid" escape=false}{/capture}
					{load_url_in_div id="navigationMenuItemsGridContainer" url=$navigationMenuItemsGridUrl}
				</tab>
				<tab name="{translate key="manager.setup.announcements"}">
					<pkp-form
						v-bind="components.{$smarty.const.FORM_ANNOUNCEMENT_SETTINGS}"
						@set="set"
					/>
				</tab>
				<tab name="{translate key="manager.setup.lists"}">
					<pkp-form
						v-bind="components.{$smarty.const.FORM_LISTS}"
						@set="set"
					/>
				</tab>
				<tab name="{translate key="manager.setup.privacyStatement"}">
					<pkp-form
						v-bind="components.{$smarty.const.FORM_PRIVACY}"
						@set="set"
					/>
				</tab>
				{call_hook name="Template::Settings::website::setup"}
			</tabs>
		</tab>
		<tab id="plugins" name="{translate key="common.plugins"}">
			{help file="settings" section="website" class="pkp_help_tab"}
			<tabs :options="{ useUrlFragment: false }">
				<tab name="{translate key="manager.plugins.installed"}">
					{capture assign=pluginGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.settings.plugins.SettingsPluginGridHandler" op="fetchGrid" escape=false}{/capture}
					{load_url_in_div id="pluginGridContainer" url=$pluginGridUrl}
				</tab>
				<tab name="{translate key="manager.plugins.pluginGallery"}">
					{capture assign=pluginGalleryGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.plugins.PluginGalleryGridHandler" op="fetchGrid" escape=false}{/capture}
					{load_url_in_div id="pluginGalleryGridContainer" url=$pluginGalleryGridUrl}
				</tab>
				{call_hook name="Template::Settings::website::plugins"}
			</tabs>
		</tab>
		{call_hook name="Template::Settings::website"}
	</tabs>
</div>
<script type="text/javascript">
	pkp.registry.init('settings-context-{$uuid}', 'SettingsContainer', {$settingsData|json_encode});
</script>

{include file="common/footer.tpl"}