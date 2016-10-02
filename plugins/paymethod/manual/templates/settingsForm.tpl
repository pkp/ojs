{**
 * plugins/paymethod/manual/templates/settingsForm.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for manual payment settings.
 *}

{fbvFormSection title="plugins.paymethod.manual.settings"}
	{fbvElement type="textarea" name="manualInstructions" id="manualInstructions" value=$manualInstructions}
{/fbvFormSection}
