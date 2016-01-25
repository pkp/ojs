{**
 * plugins/generic/announcementFeed/settingsForm.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Announcement Feed plugin settings
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.announcementfeed.displayName"}
{include file="common/header.tpl"}
{/strip}
<div id="announcementFeedSettings">
<div id="description">{translate key="plugins.generic.announcementfeed.description"}</div>

<div class="separator">&nbsp;</div>

<h3>{translate key="plugins.generic.announcementfeed.settings"}</h3>

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#announcementFeedForm').pkpHandler('$.pkp.controllers.form.FormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="announcementFeedForm" method="post" action="{plugin_url path="settings"}">
{include file="common/formErrors.tpl"}

<script>
	{literal}
	<!--
		function toggleLimitRecentItems(form) {
			form.recentItems.disabled = !form.recentItems.disabled;
		}
	// -->
	{/literal}
</script>

<table class="data">
	<tr>
		<td class="label" align="right"><input type="radio" name="displayPage" id="displayPage-all" value="all" {if $displayPage eq "all"}checked="checked" {/if}/></td>
		<td class="value">{translate key="plugins.generic.announcementfeed.settings.all"}</td>
	</tr>
	<tr>
		<td class="label" align="right"><input type="radio" name="displayPage" id="displayPage-homepage" value="homepage" {if $displayPage eq "homepage"}checked="checked" {/if}/></td>
		<td class="value">{translate key="plugins.generic.announcementfeed.settings.homepage"}</td>
	</tr>
	<tr>
		<td class="label" align="right"><input type="radio" name="displayPage" id="displayPage-announcement" value="announcement" {if $displayPage eq "announcement"}checked="checked" {/if}/></td>
		<td class="value">{translate key="plugins.generic.announcementfeed.settings.announcement"}</td>
	</tr>
	<tr>
		<td colspan="2"><div class="separator">&nbsp;</div></td>
	</tr>
	<tr>
		<td class="label" align="right"><input type="checkbox" name="limitRecentItems" id="limitRecentItems" value="1" onclick="toggleLimitRecentItems(this.form)"{if $limitRecentItems} checked="checked"{/if}/></td>
		<td class="value">
		{translate key="plugins.generic.announcementfeed.settings.recentAnnouncements1"} <input type="text" name="recentItems" id="recentItems" value="{$recentItems|escape}" {if not $limitRecentItems}disabled="disabled"{/if} size="2" maxlength="90" class="textField" />
		{translate key="plugins.generic.announcementfeed.settings.recentAnnouncements2"}</td>
	</tr>

</table>

<br/>

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/> <input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
{include file="common/footer.tpl"}
