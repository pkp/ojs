{**
 * registerSite.tpl
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site registration.
 *
 * $Id$
 *}

{include file="common/header.tpl"}

{iterate from=journals item=journal}
	{if !$notFirstJournal}
		{translate key="user.register.selectJournal"}:
		<ul>
		{assign var=notFirstJournal value=1}
	{/if}
	<li><a href="{url journal=$journal->getPath() page="user" op="register"}">{$journal->getTitle()|escape}</a></li>
{/iterate}
{if $journals->wasEmpty()}
	{translate key="user.register.noJournals"}
{else}
	</ul>
{/if}

{include file="common/footer.tpl"}
