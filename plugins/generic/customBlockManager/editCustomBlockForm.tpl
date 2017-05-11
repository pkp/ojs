{**
 * plugins/generic/customBlockManager/editCustomBlockForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for editing a custom sidebar block
 *
 *}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#customBlockForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="customBlockForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.customBlockManager.controllers.grid.CustomBlockGridHandler" op="updateCustomBlock" existingBlockName=$blockName}">
	{csrf}
	{fbvFormArea id="customBlocksFormArea" class="border"}
		{fbvFormSection}
			{fbvElement type="text" label="plugins.generic.customBlockManager.blockName" id="blockName" value=$blockName maxlength="40" inline=true size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{fbvFormSection label="plugins.generic.customBlock.content" for="blockContent"}
			{fbvElement type="textarea" multilingual=true name="blockContent" id="blockContent" value=$blockContent rich=true height=$fbvStyles.height.TALL}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormButtons submitText="common.save"}
</form>
