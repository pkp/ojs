{**
 * registerSite.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site registration.
 *
 * $Id$
 *}

{include file="common/header.tpl"}
{translate key="user.register.selectJournal"}:

<ul>
{foreach from=$journals item=journal}
	<li><a href="{$indexUrl}/{$journal->getPath()}/user/register">{$journal->getSetting('journalTitle')}</a></li>
{/foreach}
</ul>

{include file="common/footer.tpl"}
