{**
 * plugins/paymethod/paypal/templates/settingsForm.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2006-2009 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for PayPal settings.
 *}
	<tr>
		<td colspan="2"><h4>{translate key="plugins.paymethod.paypal.settings"}</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">{fieldLabel name="paypalurl" required="true" key="plugins.paymethod.paypal.settings.paypalurl"}</td>
		<td class="value" width="80%">
			<input type="text" class="textField" name="paypalurl" id="paypalurl" size="50" value="{$paypalurl|escape}" /><br/>
			{translate key="plugins.paymethod.paypal.settings.paypalurl.description"}<br/>
			&nbsp;
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">{fieldLabel name="selleraccount" required="true" key="plugins.paymethod.paypal.settings.selleraccount"}</td>
		<td class="value" width="80%">
			<input type="text" class="textField" name="selleraccount" id="selleraccount" value="{$selleraccount|escape}" /><br/>
			{translate key="plugins.paymethod.paypal.settings.selleraccount.description"}
		</td>
	</tr>
	{if !$isCurlInstalled}
		<tr>
			<td colspan="2">
				<span class="instruct">{translate key="plugins.paymethod.paypal.settings.curlNotInstalled"}</span>
			</td>
		</tr>
	{/if}
