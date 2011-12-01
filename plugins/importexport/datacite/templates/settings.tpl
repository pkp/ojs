{**
 * @file plugins/importexport/datacite/templates/settings.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * DataCite plugin settings
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.common.settings"}
{include file="common/header.tpl"}
{/strip}
<div id="dataciteSettings">
	<br />
	<br />

	<div id="description">{translate key="plugins.importexport.datacite.settings.form.description"}</div>

	<br />

	<form method="post" action="{plugin_url path="settings"}">
		{include file="common/formErrors.tpl"}
		<table width="100%" class="data">
			<tr valign="top">
				<td width="20%" class="label">{fieldLabel name="symbol" required="true" key="plugins.importexport.datacite.settings.form.symbol"}</td>
				<td width="80%" class="value">
					<input type="text" name="symbol" value="{$symbol|escape}" size="20" maxlength="50" id="symbol" class="textField" />
				</td>
			</tr>
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr valign="top">
				<td width="20%" class="label">{fieldLabel name="password" key="plugins.importexport.datacite.settings.form.savePassword"}</td>
				<td width="80%" class="value">
					<input type="checkbox" name="savePassword" id="savePassword" value="1"{if !empty($password)} checked="checked"{/if} />
				</td>
			</tr>
			<tr valign="top" id="passwordInput" {if empty($password)}style="visible: none"{/if}>
				<td width="20%" > </td>
				<td width="80%" class="value">
					<input type="password" name="password" value="{$password|escape}" size="20" maxlength="50" id="password" class="textField" />
					<br />
					<span class="instruct">{translate key="plugins.importexport.datacite.settings.form.savePasswordInstruction"}</span>
				</td>
			</tr>
			{literal}<script type='text/javascript'>
				$(function(){
					// jQuerify DOM elements
					$passwordCheckbox = $('#savePassword');
					$passwordInput = $('#passwordInput');

					// Set the initial state
					initialCheckboxState = $passwordCheckbox.attr('checked');
					if (initialCheckboxState) {
						$passwordInput.css('display', 'table-row');
					} else {
						$passwordInput.css('display', 'none');
					}

					// Toggle the password row.
					// NB: Has to be click() rather than change() to work in IE.
					$passwordCheckbox.click(function() {
						checkboxState = $passwordCheckbox.attr('checked');
						toggleState = ($passwordInput.css('display') === 'table-row');
						if (checkboxState !== toggleState) {
							$passwordInput.toggle(300);
						}
						if (checkboxState) {
							$passwordInput.find('input').focus();
						}
					});
				});
			</script>{/literal}
		</table>

		<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

		<p>
			<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/>
			&nbsp;
			<input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
		</p>
	</form>

</div>
{include file="common/footer.tpl"}
