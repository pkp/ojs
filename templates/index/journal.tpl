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

{assign var="pageId" value="index.journal"}
{include file="common/header.tpl"}

<div>{$journalDescription}</div>

{if $displayCurrentIssue}
<br /><br />
{include file="issue/issue.tpl"}
{/if}

{if $homepageImage}
<div align="center"><img src="{$publicFilesDir}/{$homepageImage.uploadName}" width="{$homepageImage.width}" height="{$homepageImage.height}" border="0" alt="" /></div>
{/if}

<br /><br />

{$additionalHomeContent}

{include file="common/footer.tpl"}
