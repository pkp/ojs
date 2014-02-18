{**
 * plugins/gateways/metsGateway/settingsForm.tpl
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * METS gateway plugin settings
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.gateways.metsGateway.displayName"}
{include file="common/header.tpl"}
{/strip}

{url|assign:"directoryUrl" page="gateway" op="plugin" path="METSGatewayPlugin"}
<div id="metsGatewaySettings">
<h3>{translate key="plugins.gateways.metsGateway.settings"}</h3>

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#metsGatewayForm').pkpHandler('$.pkp.controllers.form.FormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="metsGatewayForm" method="post" action="{plugin_url path="settings"}">
{include file="common/formErrors.tpl"}

<table class="data">
	<tr>
		<td class="label" align="right">{fieldLabel name="FLocat" key="plugins.gateways.metsGateway.settings.FLocat"}</td>
		<td class="value"><input type="radio" name="contentWrapper" id="FLocat" value="FLocat" {if $contentWrapper eq "FLocat"}checked="checked" {/if}/></td>
	</tr>
	<tr>
		<td class="label" align="right">{fieldLabel name="FContent" key="plugins.gateways.metsGateway.settings.FContent"}</td>
		<td class="value"><input type="radio" name="contentWrapper" id="FContent" value="FContent" {if $contentWrapper eq "FContent"}checked="checked" {/if}/></td>
	</tr>
	<tr>
		<td colspan="2"><div class="separator">&nbsp;</div></td>
	</tr>
	<tr>
		<td class="label" align="right">{fieldLabel name="organization" key="plugins.gateways.metsGateway.settings.organization"}</td>
		<td class="value">
		<input type="text" name="organization" id="organization" value="{$organization|escape}" size="50" maxlength="50" class="textField" /></td>
	</tr>
	<tr>
		<td class="label" align="right">{fieldLabel name="preservationLevel"  key="plugins.gateways.metsGateway.settings.preservationLevel"}</td>
		<td class="value">
		<input type="text" name="preservationLevel" id="preservationLevel" value="{$preservationLevel}" size="2" maxlength="1" class="textField" /></td>
	</tr>
	<tr>
		<td class="label" align="right">{fieldLabel name="exportSuppFiles"  key="plugins.gateways.metsGateway.settings.exportSuppFiles"}</td>
		<td class="value"><input type="checkbox" name="exportSuppFiles" id="exportSuppFiles" value="on" {if $exportSuppFiles eq "on"}checked="checked" {/if}/></td>
	</tr>
</table>

<br/>

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/>
<input type="button" class="button" value="{translate key="common.cancel"}" onclick="document.location.href='{url|escape:"quotes" page="manager" op="plugins" escape="false"}'"/>
</form>
</div>
{include file="common/footer.tpl"}
