{**
 * templates/controllers/grid/queries/queryNoteForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Read a query.
 *
 *}
<script>
	// Attach the handler.
	$(function() {ldelim}
		$('#noteForm').pkpHandler(
			'$.pkp.controllers.form.CancelActionAjaxFormHandler',
			{ldelim}
				cancelUrl: {url|json_encode op="deleteNote" params=$actionArgs noteId=$noteId escape=false}
			{rdelim}
		);
	{rdelim});
</script>

<form class="pkp_form" id="noteForm" action="{url op="insertNote" params=$actionArgs noteId=$noteId escape=false}" method="post">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="queryNoteFormNotification"}

	{fbvFormSection title="stageParticipants.notify.message" for="comment" required="true"}
		{fbvElement type="textarea" id="comment" rich=true value=$comment required="true"}
	{/fbvFormSection}

	{fbvFormArea id="queryNoteFilesArea"}
		{url|assign:queryNoteFilesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.query.QueryNoteFilesGridHandler" op="fetchGrid" params=$actionArgs noteId=$noteId escape=false}
		{load_url_in_div id="queryNoteFilesGrid" url=$queryNoteFilesGridUrl}
	{/fbvFormArea}

	{fbvFormButtons id="addNoteButton"}
</form>
