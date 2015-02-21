{**
 * plugins/generic/backup/index.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of operations this plugin can perform
 *}
{assign var="pageTitle" value="plugins.generic.backup.link"}
{include file="common/header.tpl"}

{translate key="plugins.generic.backup.longdescription"}

{assign var=footNoteNum value=1}
<ul>
	<li>{if $isDumpConfigured}<a href="{url op="db"}">{/if}{translate key="plugins.generic.backup.db"}{if $isDumpConfigured}</a>{else}<sup>{$footNoteNum}{assign var=dumpFootNote value=$footNoteNum}{assign var=footNoteNum value=$footNoteNum+1}</sup>{/if}</li>
	<li>{if $isTarConfigured}<a href="{url op="files"}">{/if}{translate key="plugins.generic.backup.files"}{if $isTarConfigured}</a>{else}<sup>{$footNoteNum}{assign var=tarFootNote value=$footNoteNum}{assign var=footNoteNum value=$footNoteNum+1}</sup>{/if}</li>
	<li>{if $isTarConfigured}<a href="{url op="code"}">{/if}{translate key="plugins.generic.backup.code"}{if $isTarConfigured}</a>{else}<sup>{$tarFootNote}</sup>{/if}</li>
</ul>

{if $dumpFootNote}{translate key="plugins.generic.backup.db.config" footNoteNum=$dumpFootNote}{/if}
{if $tarFootNote}{translate key="plugins.generic.backup.tar.config" footNoteNum=$tarFootNote}{/if}

<a href="{url page="admin"}">{translate key="admin.siteAdmin"}</a>

{include file="common/footer.tpl"}
