{**
 * index.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RTAdmin journal list
 *
 * $Id$
 *}

{assign var="pageTitle" value="rt.researchTools"}
{include file="common/header.tpl"}

<h3>{translate key="user.myJournals"}</h3>

<ul class="plain">
{foreach from=$journals item=journal}
<li>&#187; <a href="{$indexUrl}/{$journal->getPath()}/rtadmin">{$journal->getSetting('journalTitle')}</a></li>
{/foreach}
</ul>

{include file="common/footer.tpl"}
