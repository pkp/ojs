{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Journal index page.
 *
 * $Id$
 *}

{assign var="pageTitleTranslated" value=$siteTitle}
{include file="common/header.tpl"}

<div>{$journalDescription}</div>

<br />

{if $homepageImage}
<div align="center"><img src="{$publicFilesDir}/{$homepageImage.uploadName}" width="{$homepageImage.width}" height="{$homepageImage.height}" border="0" alt="" /></div>
{/if}

<br /><br />

{$additionalHomeContent}

{if $issue}
	{* Display the table of contents or cover page of the current issue. *}
	{include file="issue/view.tpl"}
{/if}

{include file="common/footer.tpl"}
