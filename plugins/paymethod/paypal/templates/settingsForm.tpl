{**
 * plugins/paymethod/paypal/templates/settingsForm.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for paypal payment settings.
 *}

{fbvFormSection title="plugins.paymethod.paypal.settings"}
	{fbvElement type="text" name="accountName" id="accountName" value=$accountName label="plugins.paymethod.paypal.settings.accountName"}
	{fbvElement type="text" name="clientId" id="clientId" value=$clientId label="plugins.paymethod.paypal.settings.clientId"}
	{fbvElement type="text" name="secret" id="secret" value=$secret label="plugins.paymethod.paypal.settings.secret"}
{/fbvFormSection}
{fbvFormSection for="testMode" list=true}
	{fbvElement type="checkbox" name="testMode" id="testMode" checked=$testMode label="plugins.paymethod.paypal.settings.testMode" inline=true}
{/fbvFormSection}
