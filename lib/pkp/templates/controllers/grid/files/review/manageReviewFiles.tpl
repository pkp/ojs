{**
 * controllers/grid/files/review/manageReviewFiles.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Allows editor to add more file to the review (that weren't added when the submission was sent to review)
 *}


<!-- Current review files -->
<div id="existingFilesContainer">
	<script type="text/javascript">
		$(function() {ldelim}
			// Attach the form handler.
			$('#manageReviewFilesForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
		{rdelim});
	</script>
	<form class="pkp_form" id="manageReviewFilesForm" action="{url component="grid.files.review.ManageReviewFilesGridHandler" op="updateReviewFiles" submissionId=$submissionId|escape stageId=$stageId|escape reviewRoundId=$reviewRoundId|escape}" method="post">
		{csrf}
		<!-- Available submission files -->
		{url|assign:availableReviewFilesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.review.ManageReviewFilesGridHandler" op="fetchGrid" submissionId=$submissionId stageId=$stageId reviewRoundId=$reviewRoundId escape=false}
		{load_url_in_div id="availableReviewFilesGrid" url=$availableReviewFilesGridUrl}
		{fbvFormButtons}
	</form>
</div>
