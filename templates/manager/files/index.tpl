{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Files browser.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.filesBrowser"}
{include file="common/header.tpl"}

<div class="subTitle">{translate key="manager.files.viewFiles"}</div>

<br />

<div class="blockTitle">{translate key="manager.files.uploadedFiles"} [ /{$base} ]</div>
<div class="block">
	{foreach item=file from=$files}
		{if $file.name eq ".."}
			<a href="{$pageUrl}/manager/files/{$prev}">&lt; {translate key="manager.files.prevDir"} &gt;</a><br />
		{elseif $file.type eq "dir"}
			<a href="{$pageUrl}/manager/files/{$base}{$file.name}">{$file.name}</a><br />
		{else}
			{$file.name}<br />
		{/if}
	{/foreach}
</div>

{include file="common/footer.tpl"}