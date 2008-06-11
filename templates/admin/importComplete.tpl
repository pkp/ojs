{**
 * importComplete.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Migration status information, output, and errors
 *
 * $Id$
 *}
{include file="common/header.tpl"}

<p>{translate key="admin.journals.importOJS1.success"}</p>

{if !empty($redirects)}
<p>{translate key="admin.journals.importOJS1.redirect.desc"}</p>
<ul>
{foreach from=$redirects item=redirect}
	{assign var=redirectFilename value=$redirect[0]}
	{assign var=redirectDescKey value=$redirect[1]}
	{assign var=redirectContents value=$redirect[2]}
	<li>{$redirectFilename|escape}:<br />
	{translate key=$redirectDescKey}<br />
	<textarea name="{$redirectFilename|escape}" cols="60" rows="20" class="textArea" style="font-family: Courier,'Courier New',fixed-width">{$redirectContents}</textarea></li>
{/foreach}
</ul>
{/if}

{if !empty($conflicts)}
<p>{translate key="admin.journals.importOJS1.conflict.desc"}</p>

<ul>
{foreach from=$conflicts item=conflict}
	{assign var=firstUser value=$conflict[0]}
	{assign var=secondUser value=$conflict[1]}
	<li>{translate|escape key="admin.journals.importOJS1.conflict" firstUsername=$firstUser->getUsername() firstName=$firstUser->getFullName() secondUsername=$secondUser->getUsername() secondName=$secondUser->getFullName()}</li>
{/foreach}
</ul>
{/if}

<p>&#187; <a href="{url op="editJournal" path=$journalId}">{translate key="admin.journals.importOJS1.editMigratedJournal"}</a></p>

{include file="common/footer.tpl"}
