{**
 * templates/editStaticPageForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for editing a static page
 *}
<script src="{$pluginJavaScriptURL}/StaticPageFormHandler.js"></script>
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#staticPageForm').pkpHandler(
			'$.pkp.controllers.form.staticPages.StaticPageFormHandler',
			{ldelim}
				previewUrl: {url|json_encode router=$smarty.const.ROUTE_PAGE page="pages" op="preview"}
			{rdelim}
		);
	{rdelim});
</script>

{url|assign:actionUrl router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.staticPages.controllers.grid.StaticPageGridHandler" op="updateStaticPage" existingPageName=$blockName escape=false}
<form class="pkp_form" id="staticPageForm" method="post" action="{$actionUrl}">
	{csrf}
	{if $staticPageId}
		<input type="hidden" name="staticPageId" value="{$staticPageId|escape}" />
	{/if}
	{fbvFormArea id="staticPagesFormArea" class="border"}
		{fbvFormSection}
			{fbvElement type="text" label="plugins.generic.staticPages.path" id="path" value=$path maxlength="40" inline=true size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" label="plugins.generic.staticPages.pageTitle" id="title" value=$title maxlength="80" inline=true multilingual=true size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{fbvFormSection}
			{url|replace:"REPLACEME":"%PATH%"|assign:"exampleUrl" router=$smarty.const.ROUTE_PAGE context=$currentContext->getPath() page="REPLACEME"}
			{translate key="plugins.generic.staticPages.viewInstructions" pagesPath=$exampleUrl}
		{/fbvFormSection}
		{fbvFormSection label="plugins.generic.staticPages.content" for="content"}
			{fbvElement type="textarea" multilingual=true name="content" id="content" value=$content rich=true height=$fbvStyles.height.TALL variables=$allowedVariables}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormSection class="formButtons"}
		{fbvElement type="button" class="pkp_helpers_align_left" id="previewButton" label="common.preview"}
		{assign var=buttonId value="submitFormButton"|concat:"-"|uniqid}
		{fbvElement type="submit" class="submitFormButton" id=$buttonId label="common.save"}
	{/fbvFormSection}
</form>
