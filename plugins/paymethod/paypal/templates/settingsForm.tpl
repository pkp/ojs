{**
 * plugins/paymethod/paypal/templates/settingsForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2006-2009 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for PayPal settings.
 *}

{fbvFormSection label="plugins.paymethod.paypal.settings.paypalurl" for="paypalurl" description="plugins.paymethod.paypal.settings.paypalurl.description"}
	{fbvElement required="true" type="text" name="paypalurl" id="paypalurl" value=$paypalurl}
{/fbvFormSection}
{fbvFormSection label="plugins.paymethod.paypal.settings.selleraccount" for="selleraccount" description="plugins.paymethod.paypal.settings.selleraccount.description"}
	{fbvElement required="true" type="text" name="selleraccount" id="selleraccount" value=$selleraccount}
{/fbvFormSection}
{if !$isCurlInstalled}
	{fbvFormSection}
		{translate key="plugins.paymethod.paypal.settings.curlNotInstalled"}
	{/fbvFormSection}
{/if}
