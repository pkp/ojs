{**
 * plugins/generic/usageStats/templates/privacyInformation.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display usage stats privacy information and an opt-out option.
 *
 *}
{include file="common/header.tpl"}

<form action="{url}" method="POST">
	{if !empty($privacyMessage)}<p>{$privacyMessage}</p>{/if}
	{if $hasOptedOut}
		<p>{translate key="plugins.generic.usageStats.optout.done"}</p>
		<input type="submit" name="opt-in" class="button defaultButton" value="{translate key="plugins.generic.usageStats.optin"}"/>
	{else}
		<p>{translate key="plugins.generic.usageStats.optout.cookie"}</p>
		<input type="submit" name="opt-out" class="button defaultButton" value="{translate key="plugins.generic.usageStats.optout"}"/>
	{/if}
</form>

{include file="common/footer.tpl"}
