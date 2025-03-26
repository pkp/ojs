{**
 * templates/management/additionalDistributionTabs.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief OJS-specific tabs for the distribution settings page
 *
 * @hook Template::Settings::distribution::archiving []
 *}

<tab id="access" label="{translate key="manager.distribution.access"}">
	<pkp-form
		v-bind="components.{APP\components\forms\context\AccessForm::FORM_ACCESS}"
		@set="set"
	/>
</tab>
<tab id="archive" label="{translate key="manager.website.archiving"}">
	<tabs :is-side-tabs="true" :track-history="true">
		<tab id="pln" label="{translate key="manager.setup.plnPluginArchiving"}">
			<pkp-form
				v-bind="components.archivePn"
				@set="set"
			/>
		</tab>
		<tab id="lockss" label="{translate key="manager.setup.otherLockss"}">
			<pkp-form
				v-bind="components.{APP\components\forms\context\ArchivingLockssForm::FORM_ARCHIVING_LOCKSS}"
				@set="set"
			/>
		</tab>
		{call_hook name="Template::Settings::distribution::archiving"}
	</tabs>
</tab>
