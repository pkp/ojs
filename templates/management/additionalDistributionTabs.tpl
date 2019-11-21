{**
 * templates/management/additionalDistributionTabs.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief PPS-specific tabs for the distribution settings page
 *}

<tab id="access" label="{translate key="manager.distribution.access"}">
	{help file="settings/distribution-settings" section="access" class="pkp_help_tab"}
	<pkp-form
		v-bind="components.{$smarty.const.FORM_ACCESS}"
		@set="set"
	/>
</tab>
