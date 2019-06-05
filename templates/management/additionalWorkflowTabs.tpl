{**
 * templates/management/additionalWorkflowTabs.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief PPS-specific tabs for the workflow settings page
 *}

				<tab name="{translate key="manager.setup.submissionSettings"}">
					<pkp-form
						v-bind="components.{$smarty.const.FORM_SUBMISSION_SETTINGS}"
						@set="set"
					/>
				</tab>