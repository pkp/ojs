{**
 * plugins/generic/googleAnalytics/settingsForm.tpl
 *
 * Copyright (c) 2013-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Google Analytics plugin settings
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.googleAnalytics.manager.googleAnalyticsSettings"}
{include file="common/header.tpl"}
{/strip}
<div id="googleAnalyticsSettings">
<div id="description">{translate key="plugins.generic.googleAnalytics.manager.settings.description"}</div>

<div class="separator"></div>

<br />

<form method="post" action="{plugin_url path="settings"}">
{include file="common/formErrors.tpl"}

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="googleAnalyticsSiteId" required="true" key="plugins.generic.googleAnalytics.manager.settings.googleAnalyticsSiteId"}</td>
		<td width="80%" class="value"><input type="text" name="googleAnalyticsSiteId" id="googleAnalyticsSiteId" value="{$googleAnalyticsSiteId|escape}" size="15" maxlength="25" class="textField" />
			<br />
			<span class="instruct">{translate key="plugins.generic.googleAnalytics.manager.settings.googleAnalyticsSiteIdInstructions"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="trackingCode-urchin" required="true" key="plugins.generic.googleAnalytics.manager.settings.trackingCode"}</td>
		<td width="80%" class="value">
			<div><input type="radio" name="trackingCode" id="trackingCode-urchin" value="urchin" {if $trackingCode eq "urchin" || $trackingCode eq ""}checked="checked" {/if}/> {fieldLabel name="trackingCode-urchin" key="plugins.generic.googleAnalytics.manager.settings.urchin"}</div>
			<div><input type="radio" name="trackingCode" id="trackingCode-ga" value="ga" {if $trackingCode eq "ga"}checked="checked" {/if}/> {fieldLabel name="trackingCode-ga" key="plugins.generic.googleAnalytics.manager.settings.ga"}</div>
			<div><input type="radio" name="trackingCode" id="trackingCode-analytics" value="analytics" {if $trackingCode eq "analytics"}checked="checked" {/if}/> {fieldLabel name="trackingCode-analytics" key="plugins.generic.googleAnalytics.manager.settings.analytics"}</div>
		</td>
	</tr>
	{if $siteAdmin}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="enableSite" key="plugins.generic.googleAnalytics.manager.settings.enableSite"}</td>
		<td width="80%" class="value">
			<div>
				<span class="instruct">{translate key="plugins.generic.googleAnalytics.manager.settings.enableSiteInstructions"}</span>
			</div>
			<div><input type="radio" name="enableSite" id="enableSite-true" value="{$smarty.const.GOOGLE_ANALYTICS_SITE_ENABLE}" {if $trackingCode eq GOOGLE_ANALYTICS_SITE_ENABLE}checked="checked" {/if}/> {fieldLabel name="enableSite-true" key="plugins.generic.googleAnalytics.manager.settings.siteEnable"}</div>
			<div>
				<input type="radio" name="enableSite" id="enableSite-unchanged" value="{$smarty.const.GOOGLE_ANALYTICS_SITE_UNCHANGED}" {if $trackingCode eq GOOGLE_ANALYTICS_SITE_UNCHANGED || $trackingCode eq ""}checked="checked" {/if}/> {fieldLabel name="enableSite-unchanged" key="plugins.generic.googleAnalytics.manager.settings.siteUnchanged"}
				<dl>
					<dt>{translate key="plugins.generic.googleAnalytics.manager.settings.googleAnalyticsSiteId"}</dt>
					<dd>{$siteGoogleAnalyticsSiteId|escape}</dd>
					<dt>{translate key="plugins.generic.googleAnalytics.manager.settings.trackingCode"}</dt>
					<dd>
						{if $siteTrackingCode eq "analytics"}{translate key="plugins.generic.googleAnalytics.manager.settings.analytics"}
						{elseif $siteTrackingCode eq "ga"}{translate key="plugins.generic.googleAnalytics.manager.settings.ga"}
						{elseif $siteTrackingCode eq "urchin"}{translate key="plugins.generic.googleAnalytics.manager.settings.urchin"}
						{else}{$siteTrackingCode}
						{/if}
					</dd>
				</dl>
			</div>
			{if $siteEnabled}
			<div><input type="radio" name="enableSite" id="enableSite-false" value="{$smarty.const.GOOGLE_ANALYTICS_SITE_DISABLE}" {if $trackingCode eq GOOGLE_ANALYTICS_SITE_DISABLE}checked="checked" {/if}/> {fieldLabel name="enableSite-false" key="plugins.generic.googleAnalytics.manager.settings.siteDisable"}</div>
			{/if}
		</td>
	</tr>
	{/if}
</table>

<br />

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/><input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
{include file="common/footer.tpl"}
