{**
 * plugins/importexport/duracloud/index.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of operations this plugin can perform
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.duracloud.displayName"}
{include file="common/header.tpl"}
{/strip}

<br/>

<h3>{translate key="plugins.importexport.duracloud.configuration"}</h3>

{if $isConfigured}{* The plugin is configured; allow choice of space. *}
	{plugin_url|assign:duracloudLogoutUrl path="signOut"}
	<p>{translate key="plugins.importexport.duracloud.configuration.configured.description" url=$duracloudUrl escapedUrl=$duracloudUrl|escape username=$duracloudUsername logoutUrl=$duracloudLogoutUrl}</p>

	<form action="{plugin_url path="selectSpace"}" method="post">
		{fieldLabel name=duracloudSpace key="plugins.importexport.duracloud.configuration.space"}&nbsp;&nbsp;
		<select name="duracloudSpace" id="duracloudSpace" class="selectMenu">
				<option disabled="disabled" {if $duracloudSpace == ""}selected="selected" {/if}/>
			{foreach from=$spaces item=space}
				<option {if $space|concat:"" === $duracloudSpace|concat:""}selected {/if}value="{$space|escape}">{$space|escape}</option>
			{/foreach}
		</select>
		<input type="submit" value="{translate key="common.save"}" class="button defaultButton" />
	</form>

	{if in_array($duracloudSpace, $spaces)}{* if $duracloudSpace valid *}
		<h3>{translate key="manager.importExport"}</h3>
		<ul>
			<li><a href="{plugin_url path="exportableIssues"}">{translate key="plugins.importexport.duracloud.export.issues"}</a></li>
			<li><a href="{plugin_url path="importableIssues"}">{translate key="plugins.importexport.duracloud.import.issues"}</a></li>
		</ul>
	{/if}{* $duracloudSpace is valid *}

{else}{* The plugin has not been configured; display the login form. *}
	<form action="{plugin_url path="signIn"}" method="post">
		{include file="common/formErrors.tpl"}
		<table width="100%" class="data">
			<tr valign="top">
				<td width="20%" class="label">{fieldLabel key="common.url" name="duracloudUrl" required=true}</td>
				<td width="80%" class="value"><input type="text" name="duracloudUrl" id="duracloudUrl" value="{$duracloudUrl|escape}" /></td>
			</tr>
			<tr valign="top">
				<td class="label">{fieldLabel key="user.username" name="duracloudUsername" required=true}</td>
				<td class="value"><input type="text" name="duracloudUsername" id="duracloudUsername" value="{$duracloudUsername|escape}" /></td>
			</tr>
			<tr valign="top">
				<td class="label">{fieldLabel key="user.password" name="duracloudPassword" required=true}</td>
				<td class="value"><input type="password" name="duracloudPassword" id="duracloudPassword" value="" /></td>
			</tr>
			<tr valign="top">
				<td colspan="2">
					<input type="submit" class="button defaultButton" value="{translate key="user.login"}" />
				</td>
			</tr>
		</table>
	</form>
{/if}{* $isConfigured *}

{include file="common/footer.tpl"}
