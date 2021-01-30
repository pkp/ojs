{**
 * templates/management/context.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * The journal settings page.
 *}
{extends file="layouts/backend.tpl"}

{block name="page"}
	<h1 class="app__pageHeading">
		{translate key="manager.setup"}
	</h1>

	{if $newVersionAvailable}
		<notification>
			{translate key="site.upgradeAvailable.manager" currentVersion=$currentVersion latestVersion=$latestVersion siteAdminName=$siteAdmin->getFullName() siteAdminEmail=$siteAdmin->getEmail()}
		</notification>
	{/if}

	{if $currentContext->getData('disableSubmissions')}
		<notification>
			{translate key="manager.setup.disableSubmissions.notAccepting"}
		</notification>
	{/if}

	<tabs :track-history="true">
		<tab id="masthead" label="{translate key="manager.setup.masthead"}">
			{help file="settings/journal-settings" class="pkp_help_tab"}
			<pkp-form
				v-bind="components.{$smarty.const.FORM_MASTHEAD}"
				@set="set"
			/>
		</tab>
		<tab id="contact" label="{translate key="about.contact"}">
			{help file="settings/journal-settings" section="contact" class="pkp_help_tab"}
			<pkp-form
				v-bind="components.{$smarty.const.FORM_CONTACT}"
				@set="set"
			/>
		</tab>
		<tab id="sections" label="{translate key="section.sections"}">
			{help file="settings/journal-settings" section="sections" class="pkp_help_tab"}
			{capture assign=sectionsGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.settings.sections.SectionGridHandler" op="fetchGrid" escape=false}{/capture}
			{load_url_in_div id="sectionsGridContainer" url=$sectionsGridUrl}
		</tab>
		<tab id="categories" label="{translate key="grid.category.categories"}">
			{help file="settings/journal-settings" section="categories" class="pkp_help_tab"}
			{capture assign=categoriesUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.settings.category.CategoryCategoryGridHandler" op="fetchGrid" escape=false}{/capture}
			{load_url_in_div id="categoriesContainer" url=$categoriesUrl}
		</tab>
	</tabs>
{/block}
