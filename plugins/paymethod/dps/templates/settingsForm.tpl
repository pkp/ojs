{**
 * plugins/paymethod/dps/templates/settingsForm.tpl
 *
 * Robert Carter <r.carter@auckland.ac.nz>
 *
 * Based on the work of these people:
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2006-2009 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for DPS settings.
 *}
	<tr>
		<td colspan="2"><h4>{translate key="plugins.paymethod.dps.settings"}</td>
	</tr>

	<tr valign="top">
		<td class="label" width="20%">{fieldLabel name="dpsmerchant" required="true" key="plugins.paymethod.dps.settings.dpsmerchant"}</td>
		<td class="value" width="80%">
			<input type="text" class="textField" name="dpsmerchant" id="dpsmerchant" size="50" value="{$dpsmerchant|escape}" /><br/>
			{translate key="plugins.paymethod.dps.settings.dpsmerchant.description"}<br/>
			&nbsp;
		</td>
	</tr>

	<tr valign="top">
		<td class="label" width="20%">{fieldLabel name="dpsurl" required="true" key="plugins.paymethod.dps.settings.dpsurl"}</td>
		<td class="value" width="80%">
			<input type="text" class="textField" name="dpsurl" id="dpsurl" size="50" value="{$dpsurl|escape}" /><br/>
			{translate key="plugins.paymethod.dps.settings.dpsurl.description"}<br/>
			&nbsp;
		</td>
	</tr>

	<tr valign="top">
		<td class="label" width="20%">{fieldLabel name="dpsuser" required="true" key="plugins.paymethod.dps.settings.dpsuser"}</td>
		<td class="value" width="80%">
			<input type="text" class="textField" name="dpsuser" id="dpsuser" size="50" value="{$dpsuser|escape}" /><br/>
			{translate key="plugins.paymethod.dps.settings.dpsuser.description"}<br/>
			&nbsp;
		</td>
	</tr>

	<tr valign="top">
		<td class="label" width="20%">{fieldLabel name="dpskey" required="true" key="plugins.paymethod.dps.settings.dpskey"}</td>
		<td class="value" width="80%">
			<input type="text" class="textField" name="dpskey" id="dpskey" size="50" value="{$dpskey|escape}" /><br/>
			{translate key="plugins.paymethod.dps.settings.dpskey.description"}<br/>
			&nbsp;
		</td>
	</tr>

	<tr valign="top">
		<td class="label" width="20%">{fieldLabel name="dpscertpath" required="true" key="plugins.paymethod.dps.settings.dpscertpath"}</td>
		<td class="value" width="80%">
			<input type="text" class="textField" name="dpscertpath" id="dpscertpath" size="50" value="{$dpscertpath|escape}" /><br/>
			{translate key="plugins.paymethod.dps.settings.dpscertpath.description"}<br/>
			&nbsp;
		</td>
	</tr>

	{if !$isCurlInstalled}
		<tr>
			<td colspan="2">
				<span class="instruct">{translate key="plugins.paymethod.dps.settings.curlNotInstalled"}</span>
			</td>
		</tr>
	{/if}
