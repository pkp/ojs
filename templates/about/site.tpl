{**
 * site.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Journal site.
 *
 * $Id$
 *}

{assign var="pageTitle" value="about.aboutSite"}
{include file="common/header.tpl"}
{if !empty($about)}
	<p>{$about}</p>
{/if}

<h3>{translate key="journal.journals"}</h3>
<ul class="plain">
{foreach from=$journals item=journal}
	<li>&#187; <a href="{$indexUrl}/{$journal->getPath()}/about">{$journal->getTitle()}</a></li>
{/foreach}
</ul>
{include file="common/footer.tpl"}
