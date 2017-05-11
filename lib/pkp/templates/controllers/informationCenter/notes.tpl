{**
 * templates/controllers/informationCenter/notes.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display submission file notes/note form in information center.
 *}
<script type="text/javascript">
	// Attach the Notes handler.
	$(function() {ldelim}
		$('#informationCenterNotes').pkpHandler(
			'$.pkp.controllers.informationCenter.NotesHandler',
			{ldelim}
				fetchNotesUrl: {url|json_encode router=$smarty.const.ROUTE_COMPONENT op="listNotes" params=$linkParams escape=false},
				fetchPastNotesUrl: {url|json_encode router=$smarty.const.ROUTE_COMPONENT op="listPastNotes" params=$linkParams escape=false}
			{rdelim}
		);
	{rdelim});
</script>

<div id="informationCenterNotes">

	{if $showEarlierEntries}
		{**
		 * The file information center should provide access to notes
		 * from previous stages. Does not apply to submissions.
		 *}
		<div id="notesAccordion">
			<h3><a href="#">{translate key="informationCenter.currentNotes"}</a></h3>
	{/if}

	{* Leave an empty div to be filled with notes *}
	<div id="notesList"></div>

	{if $showEarlierEntries}
			<h3><a href="#" id="showPastNotesLink">{translate key="informationCenter.pastNotes"}</a></h3>
			{* Leave an empty div to be filled in with past notes *}
			<div id="pastNotesList"></div>
		</div>
	{/if}

	{include file=$newNoteFormTemplate}
</div>
