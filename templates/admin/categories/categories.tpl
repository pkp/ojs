{**
 * templates/manager/categories/categories.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of categories in journal management.
 *
 *}
{strip}
{assign var="pageTitle" value="admin.categories"}
{assign var="pageId" value="admin.categories"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
{literal}
$(document).ready(function() { setupTableDND("#dragTable", "moveCategory"); });
{/literal}
</script>

<br/>

<form action="{url op="setCategoriesEnabled"}" method="post">
	{translate key="admin.categories.enable.description"}<br/>
	<input type="radio" id="categoriesEnabledOff" {if !$categoriesEnabled}checked="checked" {/if}name="categoriesEnabled" value="0"/>&nbsp;<label for="categoriesEnabledOff">{translate key="admin.categories.disableCategories"}</label><br/>
	<input type="radio" id="categoriesEnabledOn" {if $categoriesEnabled}checked="checked" {/if}name="categoriesEnabled" value="1"/>&nbsp;<label for="categoriesEnabledOn">{translate key="admin.categories.enableCategories"}</label><br/>
	<input type="submit" value="{translate key="common.record"}" class="button defaultButton"/>
</form>

<br />

<div id="categories">

<table width="100%" class="listing" id="dragTable">
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="75%">{translate key="admin.categories.name"}</td>
		<td width="25%">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=categories item=category key=categoryId}
	<tr valign="top" id="category-{$categoryId|escape}" class="data">
		<td class="drag">
			{$category|escape}
		</td>
		<td>
			<a href="{url op="editCategory" path=$categoryId}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="deleteCategory" path=$categoryId}" onclick="return confirm('{translate|escape:"jsparam" key="admin.categories.confirmDelete"}')" class="action">{translate key="common.delete"}</a>&nbsp;|&nbsp;<a href="{url op="moveCategory" d=u id=$categoryId}">&uarr;</a>&nbsp;<a href="{url op="moveCategory" d=d id=$categoryId}">&darr;</a>
		</td>
	</tr>
{/iterate}
{if $categories->wasEmpty()}
	<tr>
		<td colspan="2" class="nodata">{translate key="admin.categories.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="2" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td align="left">{page_info iterator=$categories}</td>
		<td align="right">{page_links anchor="categories" name="categories" iterator=$categories}</td>
	</tr>
{/if}
</table>

<a href="{url op="createCategory"}" class="action">{translate key="admin.categories.create"}</a>
</div>

{include file="common/footer.tpl"}

