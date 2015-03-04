{**
 * plugins/generic/pln/templates/settingsForm.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * PLN plugin settings
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.pln.settings_page"}
{include file="common/header.tpl"}
{/strip}

<div id="plnSettings">
	<form class="pkp_form" id="plnSettingsForm" method="post" action="{plugin_url path="settings"}">
		{include file="common/formErrors.tpl"}
		<table class="data">

			<tr>
				<td class="label">
					{fieldLabel name="terms_of_use" key="plugins.generic.pln.settings.terms_of_use"}
				</td>
				<td class="value">
					<p>{translate key="plugins.generic.pln.settings.terms_of_use_help"}</p>
					{if $hasIssn}
						{foreach name=terms from=$terms_of_use key=term_name item=term_data}
							<p>{$term_data.term}</p>
							<input type="checkbox" name="terms_agreed[{$term_name|escape}]" value="1"{if $terms_of_use_agreement[$term_name]} checked{/if}><label class="agree" for="terms_agreed[{$term_name|escape}]">{translate key="plugins.generic.pln.settings.terms_of_use_agree"}</label>
							{if !$smarty.foreach.terms.last }<div class="separator">&nbsp;</div>{/if}
						{/foreach}
					{else}
						<p>{translate key="plugins.generic.pln.notifications.issn_setting"}</p>
					{/if}
				</td>
			</tr>

			<tr><td colspan="2"><div class="separator">&nbsp;</div></td></tr>
			
			<tr>
				<td class="label">{fieldLabel name="journal_uuid" key="plugins.generic.pln.settings.journal_uuid"}</td>
				<td class="value">
					<p>{translate key="plugins.generic.pln.settings.journal_uuid_help"}</p>
					<input type="text" id="journal_uuid" name="journal_uuid"  size="36" maxlength="36" class="textField" value="{$journal_uuid|escape}" disabled="disabled"/>
				</td>
			</tr>
			
			<tr><td colspan="2"><div class="separator">&nbsp;</div></td></tr>
			
			<tr>
				<td class="label">{fieldLabel name="terms_of_use" key="plugins.generic.pln.settings.refresh"}</td>
				<td class="value">
					<p>{translate key="plugins.generic.pln.settings.refresh_help"}</p>
					<input type="submit" id="refresh" name="refresh" class="button" value="{translate key="plugins.generic.pln.settings.refresh"}"/>
				</td>
			</tr>
			
			<tr><td colspan="2"><div class="separator">&nbsp;</div></td></tr>
			
			<tr>
				<td class="label">
					
				</td>
				<td class="value">
					<input type="button" class="button" value="{translate key="common.cancel"}" onclick="document.location.href='{url|escape:"quotes" page="manager" op="plugins" path="generic" escape="false"}'" />
					<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}" {if not $hasIssn}disabled="disabled"{/if}/>
				</td>
			</tr>

		</table>
	</form>
</div>

{include file="common/footer.tpl"}