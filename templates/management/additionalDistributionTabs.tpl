{**
 * templates/management/additionalDistributionTabs.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief OJS-specific tabs for the distribution settings page
 *}

<tab id="access" label="{translate key="manager.distribution.access"}">
	{help file="settings/distribution-settings" section="access" class="pkp_help_tab"}
	<pkp-form
		v-bind="components.{$smarty.const.FORM_ACCESS}"
		@set="set"
	/>
</tab>
<tab id="archive" label="{translate key="manager.website.archiving"}">
	{help file="settings/distribution-settings" section="archiving" class="pkp_help_tab"}
	<tabs :is-side-tabs="true">
		<tab id="pln" label="{translate key="manager.setup.plnPluginArchiving"}">
			<pkp-form
				v-bind="components.archivePn"
				@set="set"
			/>
		</tab>
		<tab id="lockss" label="{translate key="manager.setup.otherLockss"}">
			<pkp-form
				v-bind="components.{$smarty.const.FORM_ARCHIVING_LOCKSS}"
				@set="set"
			/>
		</tab>
		{call_hook name="Template::Settings::distribution::archiving"}
	</tabs>
</tab>
