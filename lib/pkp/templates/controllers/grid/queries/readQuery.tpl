{**
 * templates/controllers/grid/queries/readQuery.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Read a query.
 *
 *}
<script>
	$(function() {ldelim}
		$('#readQueryContainer').pkpHandler(
			'$.pkp.controllers.grid.queries.ReadQueryHandler',
			{ldelim}
				fetchNoteFormUrl: {url|json_encode router=$smarty.const.ROUTE_COMPONENT component=$queryNotesGridHandlerName op="addNote" params=$requestArgs queryId=$query->getId() escape=false},
				fetchParticipantsListUrl: {url|json_encode router=$smarty.const.ROUTE_COMPONENT component="grid.queries.QueriesGridHandler" op="participants" params=$requestArgs queryId=$query->getId() escape=false}
			{rdelim}
		);
	{rdelim});
</script>

<div id="readQueryContainer" class="pkp_controllers_query">
    <h4>
        {translate key="editor.submission.stageParticipants"}
		{if $editAction}
			{include file="linkAction/linkAction.tpl" action=$editAction contextId="editQuery"}
		{/if}
    </h4>
    <ul id="participantsListPlaceholder" class="participants"></ul>

	{url|assign:queryNotesGridUrl router=$smarty.const.ROUTE_COMPONENT component=$queryNotesGridHandlerName op="fetchGrid" params=$requestArgs queryId=$query->getId() escape=false}
	{load_url_in_div id="queryNotesGrid" url=$queryNotesGridUrl}

    <div class="openNoteForm add_note">
        <span class="pkp_spinner"></span>
        <a href="#">
            {translate key="submission.query.addNote"}
        </a>
    </div>

	<div id="newNotePlaceholder"></div>
</div>
