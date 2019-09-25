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
		<tab id="appearance" label="{translate key="manager.website.appearance"}">
			{help file="settings" section="website" class="pkp_help_tab"}
			<tabs is-side-tabs="true">
				<tab id="theme" label="{translate key="manager.setup.theme"}">
					<theme-form
						v-bind="components.{$smarty.const.FORM_THEME}"
						@set="set"
					/>
				</tab>
				<tab id="setup" label="{translate key="navigation.setup"}">
					<pkp-form
						v-bind="components.{$smarty.const.FORM_APPEARANCE_SETUP}"
						@set="set"
					/>
				</tab>
				<tab id="advanced" label="{translate key="manager.setup.advanced"}">
					<pkp-form
						v-bind="components.{$smarty.const.FORM_APPEARANCE_ADVANCED}"
						@set="set"
					/>
				</tab>
				{call_hook name="Template::Settings::website::appearance"}
			</tabs>
		</tab>
		<tab id="setup" label="{translate key="navigation.setup"}">
			{help file="settings" section="website" class="pkp_help_tab"}
			<tabs is-side-tabs="true">
				<tab id="languages" label="{translate key="common.languages"}">
					{capture assign=languagesUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.settings.languages.ManageLanguageGridHandler" op="fetchGrid" escape=false}{/capture}
					{load_url_in_div id="languageGridContainer" url=$languagesUrl}
				</tab>
				<tab id="navigationMenus" label="{translate key="manager.navigationMenus"}">
					{capture assign=navigationMenusGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.navigationMenus.NavigationMenusGridHandler" op="fetchGrid" escape=false}{/capture}
					{load_url_in_div id="navigationMenuGridContainer" url=$navigationMenusGridUrl}
					{capture assign=navigationMenuItemsGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.navigationMenus.NavigationMenuItemsGridHandler" op="fetchGrid" escape=false}{/capture}
					{load_url_in_div id="navigationMenuItemsGridContainer" url=$navigationMenuItemsGridUrl}
				</tab>
				<tab id="announcements" label="{translate key="manager.setup.announcements"}">
					<pkp-form
						v-bind="components.{$smarty.const.FORM_ANNOUNCEMENT_SETTINGS}"
						@set="set"
					/>
				</tab>
				<tab id="lists" label="{translate key="manager.setup.lists"}">
					<pkp-form
						v-bind="components.{$smarty.const.FORM_LISTS}"
						@set="set"
					/>
				</tab>
				<tab id="privacy" label="{translate key="manager.setup.privacyStatement"}">
					<pkp-form
						v-bind="components.{$smarty.const.FORM_PRIVACY}"
						@set="set"
					/>
				</tab>
				{call_hook name="Template::Settings::website::setup"}
			</tabs>
		</tab>
		<tab id="plugins" label="{translate key="common.plugins"}">
			{help file="settings" section="website" class="pkp_help_tab"}
			<tabs :options="{ useUrlFragment: false }">
				<tab id="installedPlugins" label="{translate key="manager.plugins.installed"}">
					{capture assign=pluginGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.settings.plugins.SettingsPluginGridHandler" op="fetchGrid" escape=false}{/capture}
					{load_url_in_div id="pluginGridContainer" url=$pluginGridUrl}
				</tab>
				<tab id="pluginGallery" label="{translate key="manager.plugins.pluginGallery"}">
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
