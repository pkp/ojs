{**
 * templates/controllers/tab/workflow/form/fileCreateFormXML.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Xml file create form.
 *}
{* generate a unique ID for the form *}
{assign var="createFormXML" value="createFormXML"|uniqid|escape}
<script type="text/javascript">
	// Attach the file upload form handler.
	$(function() {ldelim}
		$('#{$createFormXML}').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');

		{rdelim});
</script>


<form id="{$createFormXML}" class="pkp_form" action="{url op="createFile" submissionId=$submissionId stageId=$stageId fileStage=$fileStage}" method="post">
	{csrf}

	{fbvFormArea id="extraFileData"}
		{fbvFormSection title="common.fileName"}
			{fbvElement type="text" label="common.fileName" required=true id="fileName" value=$fileName}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons}
</form>
