{**
 * journalSponsorship.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Journal / Journal Sponsorship.
 *
 * $Id$
 *}

{assign var="pageTitle" value="about.journalSponsorship"}
{include file="common/header.tpl"}

<div>
	{$contributorNote}
</div>

<div>
	<ul>
		{foreach from=$contributors item=contributor}
		{if $contributor.name}
			{if $contributor.url}
				<li><a href="{$contributor.url}">{$contributor.name}</a></li>
			{else}
				<li>{$contributor.name}</li>
			{/if}
		{/if}
		{/foreach}
	</ul>
</div>

{include file="common/footer.tpl"}
