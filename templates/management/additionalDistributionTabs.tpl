{**
 * templates/management/additionalDistributionTabs.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief OJS-specific tabs for the distribution settings page
 *}

<tab id="archive" name="{translate key="manager.website.archiving"}">
	{help file="settings" section="distribution" class="pkp_help_tab"}
	<tabs :options="{ useUrlFragment: false}" class="tabs-component--side">
		<tab name="{translate key="manager.setup.plnPluginArchiving"}">
			<pkp-form
				v-bind="forms.archivePn"
				@set-fields="setFormFields"
				@set-errors="setFormErrors"
				@set-visible-locales="setFormVisibleLocales"
			/>
		</tab>
		<tab name="{translate key="manager.setup.otherLockss"}">
			<pkp-form
				v-bind="forms.{$smarty.const.FORM_ARCHIVING_LOCKSS}"
				@set-fields="setFormFields"
				@set-errors="setFormErrors"
				@set-visible-locales="setFormVisibleLocales"
			/>
		</tab>
		{call_hook name="Template::Settings::distribution::archiving"}
	</tabs>
</tab>
<tab id="payments" name="{translate key="manager.payments"}">
	{help file="settings" section="distribution" class="pkp_help_tab"}
	<pkp-form
		v-bind="forms.{$smarty.const.FORM_PAYMENT_SETTINGS}"
		@set-fields="setFormFields"
		@set-errors="setFormErrors"
		@set-visible-locales="setFormVisibleLocales"
	/>
</tab>
