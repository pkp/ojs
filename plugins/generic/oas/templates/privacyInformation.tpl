{**
 * plugins/generic/oas/privacyInformation.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display OA-S privacy information and an opt-out option.
 *
 *}
{include file="common/header.tpl"}

<form action="{url}" method="POST">
	<p>TODO: Insert privacy information (to be provided by FU Berlin).</p>
	{if $hasOptedOut}
		<p>{translate key="plugins.generic.oas.optout.done"}</p>
	{else}
		<p>{translate key="plugins.generic.oas.optout.cookie"}</p>
		<input type="submit" name="opt-out" class="button defaultButton" value="{translate key="plugins.generic.oas.optout"}"/>
	{/if}
</form>

{include file="common/footer.tpl"}
