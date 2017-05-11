{**
 * templates/controllers/grid/files/query/manageQueryNoteFiles.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Allows users to manage the list of files available to a query.
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#manageQueryNoteFilesForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<!-- Current query files -->
<p>{translate key="editor.submission.query.manageQueryNoteFilesDescription"}</p>

<div id="existingFilesContainer">
	<form class="pkp_form" id="manageQueryNoteFilesForm" action="{url component="grid.files.query.ManageQueryNoteFilesGridHandler" op="updateQueryNoteFiles" params=$actionArgs submissionId=$submissionId queryId=$queryId noteId=$noteId stageId=$smarty.const.WORKFLOW_STAGE_ID_EDITING}" method="post">
		{csrf}
		{fbvFormArea id="manageQueryNoteFiles"}
			{fbvFormSection}
				{url|assign:manageQueryNoteFilesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.query.ManageQueryNoteFilesGridHandler" op="fetchGrid" params=$actionArgs submissionId=$submissionId queryId=$queryId noteId=$noteId escape=false}
				{load_url_in_div id="manageQueryNoteFilesGrid" url=$manageQueryNoteFilesGridUrl}
			{/fbvFormSection}

			{fbvFormButtons}
		{/fbvFormArea}
	</form>
</div>
