{**
 * templates/controllers/grid/files/copyedit/manageCopyeditFiles.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Allows users to manage the list of copyedit files, potentially adding more
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#manageCopyeditFilesForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<!-- Current copyedited files -->
<div id="existingFilesContainer">
	<form class="pkp_form" id="manageCopyeditFilesForm" action="{url component="grid.files.copyedit.ManageCopyeditFilesGridHandler" op="updateCopyeditFiles" submissionId=$submissionId stageId=$smarty.const.WORKFLOW_STAGE_ID_EDITING}" method="post">
		{csrf}
		{fbvFormArea id="manageCopyeditFiles"}
			{fbvFormSection}
				{url|assign:manageCopyeditFilesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.copyedit.ManageCopyeditFilesGridHandler" op="fetchGrid" submissionId=$submissionId escape=false}
				{load_url_in_div id="manageCopyeditFilesGrid" url=$manageCopyeditFilesGridUrl}
			{/fbvFormSection}

			{fbvFormButtons}
		{/fbvFormArea}
	</form>
</div>
