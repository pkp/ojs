{**
 * plugins/generic/pln/templates/settingsForm.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * PLN plugin settings
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.pln.displayName"}
{include file="common/header.tpl"}
{/strip}

<div id="plnSettings">
	<h3>{translate key="plugins.generic.pln.settings"}</h3>

	<form class="pkp_form" id="plnSettingsForm" method="post" action="{plugin_url path="settings"}">
		{include file="common/formErrors.tpl"}
		<input type="hidden" name="journal_uuid" id="journal_uuid" value="{$journal_uuid|escape}"/>

		<table class="data">

			<tr valign="top">
				<td class="label">
					{fieldLabel name="pln_network" key="plugins.generic.pln.settings.pln_network"}
				</td>
				<td class="value">
					<p>{translate key="plugins.generic.pln.settings.pln_network_help"}</p>
					<select name="pln_network" id="pln_network">
						{foreach from=$pln_networks key=pln_network_name item=pln_network_host}
						<option {if $pln_network_name == $pln_network}selected="selected" {/if}value="{$pln_network_name|escape}">{$pln_network_name|escape}</option>
						{/foreach}
					</select>
				</td>
			</tr>

			<tr valign="top">
				<td class="label">
					{fieldLabel name="terms_of_use" key="plugins.generic.pln.settings.terms_of_use"}
				</td>
				<td class="value">
					<p>{translate key="plugins.generic.pln.settings.terms_of_use_help"}</p>
					<textarea name="terms_of_use" id="terms_of_use">{$terms_of_use|escape}</textarea>
				</td>
			</tr>
			
			<tr valign="top">
				<td class="label">&nbsp;</td>
				<td class="value">
					<input type="checkbox" name="terms_of_use_agreement" id="terms_of_use_agreement" value="{$terms_of_use_agreement|escape}"{if $terms_of_use_agreement}checked {/if}>{translate key="plugins.generic.pln.settings.terms_of_use_agree"}
				</td>
			</tr>
			
			<tr><td colspan="2"><div class="separator">&nbsp;</div></td></tr>

			<tr valign="top">
				<td class="label">
					{fieldLabel name="object_type" key="plugins.generic.pln.settings.object_type"}
				</td>
				<td class="value">
					<p>{translate key="plugins.generic.pln.settings.object_type_help"}</p>
					<select name="object_type" id="object_type">
						{foreach from=$supported_objects key=supported_object_type item=supported_object_class}
						<option {if $object_type == $supported_object_type}selected="selected" {/if}value="{$supported_object_type|escape}">{translate key=$supported_object_type}</option>
						{/foreach}
					</select>
				</td>
			</tr>
			
			<tr valign="top">
				<td class="label">
					{fieldLabel name="object_threshold" key="plugins.generic.pln.settings.object_threshold"}
				</td>
				<td class="value">
					<p>{translate key="plugins.generic.pln.settings.object_threshold_help"}</p>
					<select name="object_threshold" id="object_threshold">
						{section name=threshold start=1 loop=21 step=1}
						<option {if $smarty.section.threshold.index == $object_threshold}selected="selected" {/if}value="{$smarty.section.threshold.index|escape}">{$smarty.section.threshold.index|escape}</option>
						{/section}
					</select>
				</td>
			</tr>		

			<tr><td colspan="2"><div class="separator">&nbsp;</div></td></tr>

			<tr>
				<td class="label">
					{fieldLabel name="curl_support" key="plugins.generic.pln.settings.curl_support"}
				</td>
				<td class="value">
					<p>{translate key="plugins.generic.pln.settings.curl_support_help"}</p>
					<strong>{$curl_support|escape}</strong>
				</td>
			</tr>

			<tr>
				<td class="label">
					{fieldLabel name="zip_support" key="plugins.generic.pln.settings.zip_support"}
				</td>
				<td class="value">
					<p>{translate key="plugins.generic.pln.settings.zip_support_help"}</p>
					<strong>{$zip_support|escape}</strong>
				</td>
			</tr>

		</table>

		<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/>
		<input type="button" class="button" value="{translate key="common.cancel"}" onclick="document.location.href='{url|escape:"quotes" page="manager" op="plugins" path="generic" escape="false"}'" />
	</form>
</div>

{include file="common/footer.tpl"}