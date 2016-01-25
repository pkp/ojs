{**
 * plugins/paymethod/manual/templates/settingsForm.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for manual payment settings.
 *}

{fbvFormSection label="plugins.paymethod.manual.settings" for="manualInstructions" description="plugins.paymethod.manual.settings.instructions"}
	{fbvElement required="true" type="textarea" name="manualInstructions" id="manualInstructions" value=$manualInstructions}
{/fbvFormSection}
