{**
 * templates/controllers/informationCenter/notesList.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display submission file note list in information center.
 *}

<div id="{$notesListId}" class="pkp_notes_list">
	{iterate from=notes item=note}
		{assign var=noteId value=$note->getId()}
		{if $noteFilesDownloadLink && isset($noteFilesDownloadLink[$noteId])}
			{assign var=downloadLink value=$noteFilesDownloadLink[$noteId]}
		{else}
			{assign var=downloadLink value=0}
		{/if}
		{assign var=noteViewStatus value=$note->markViewed($currentUserId)}
		{include file="controllers/informationCenter/note.tpl" noteFileDownloadLink=$downloadLink noteViewStatus=$noteViewStatus}
	{/iterate}
	{if $notes->wasEmpty()}
		<p class="no_notes">{translate key="informationCenter.noNotes"}</p>
	{/if}
</div>
