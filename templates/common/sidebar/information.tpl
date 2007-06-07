{**
 * information.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu -- information links.
 *
 * $Id$
 *}

{if $currentJournal}
{assign var=forReaders value=$currentJournal->getSetting('readerInformation')}
{assign var=forAuthors value=$currentJournal->getSetting('authorInformation')}
{assign var=forLibrarians value=$currentJournal->getSetting('librarianInformation')}
{if !empty($forReaders) || !empty($forAuthors)}
<div class="block" id="sidebarInformation">
	<span class="blockTitle">{translate key="navigation.info"}</span>
	<ul>
		{if !empty($forReaders)}<li><a href="{url page="information" op="readers"}">{translate key="navigation.infoForReaders"}</a></li>{/if}
		{if !empty($forAuthors)}<li><a href="{url page="information" op="authors"}">{translate key="navigation.infoForAuthors"}</a></li>{/if}
		{if !empty($forLibrarians)}<li><a href="{url page="information" op="librarians"}">{translate key="navigation.infoForLibrarians"}</a></li>{/if}
	</ul>
</div>
{/if}
{/if}
