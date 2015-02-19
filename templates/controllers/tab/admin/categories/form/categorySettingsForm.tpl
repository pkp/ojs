{**
 * controllers/tab/admin/categories/categorySettingsForm.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Category administration.
 *}

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#categoriesForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="categoriesForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.admin.categories.AdminCategoriesTabHandler" op="saveFormData" tab="categories"}">

	{include file="controllers/tab/settings/wizardMode.tpl" wizardMode=$wizardMode}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="categorySettingsNotification"}

	{fbvFormArea id="categorySettings"}
		{fbvFormSection list=true}
			{fbvElement type="checkbox" id="categoriesEnabled" value="1" label="admin.categories.enableCategories" checked=$categoriesEnabled inline=true}
		{/fbvFormSection}
	{/fbvFormArea}

	{url|assign:categoriesUrl router=$smarty.const.ROUTE_COMPONENT component="grid.admin.categories.AdminCategoriesGridHandler" op="fetchGrid" escape=false}
	{load_url_in_div id="categoriesGridContainer" url=$categoriesUrl}

	{if !$wizardMode}
		{fbvFormButtons id="categoryFormSubmit" submitText="common.save" hideCancel=true}
	{/if}
</form>
