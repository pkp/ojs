
{**
 * templates/controllers/grid/admin/categories/adminCategoryForm.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Category edit form
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#editCategoryForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="editCategoryForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="grid.admin.categories.AdminCategoriesGridHandler" op="updateItem"}">

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="adminCategoryFormNotification"}

	{fbvFormArea id="categoryData"}
		{fbvFormSection title="common.name" required="true" for="checklistItem"}
			{fbvElement type="text" multilingual="true" name="name" id="name" value=$name}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons submitText="common.save"}
</form>
