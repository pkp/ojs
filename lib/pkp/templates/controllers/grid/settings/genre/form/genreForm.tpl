{**
 * templates/controllers/grid/settings/genre/form/genreForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Genre form under context management.
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#genreForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="genreForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="grid.settings.genre.GenreGridHandler" op="updateGenre"}">
{csrf}
{include file="controllers/notification/inPlaceNotification.tpl" notificationId="genreFormNotification"}

{fbvFormArea id="genreInfo"}
{fbvFormSection title="common.name" for="name" required="true"}
	{fbvElement type="text" multilingual="true" id="name" value=$name maxlength="80" required="true"}
{/fbvFormSection}
{fbvFormSection list="true" title="common.options"}
	{fbvElement type="checkbox" id="sortable" checked=$sortable label="manager.setup.genres.sortable"}
	{fbvElement type="checkbox" id="dependent" checked=$dependent label="manager.setup.genres.dependent"}
	{fbvElement type="checkbox" id="supplementary" checked=$supplementary label="manager.setup.genres.supplementary"}
{/fbvFormSection}
{fbvFormSection title="manager.setup.groupType" for="category"}
	{fbvElement type="select" id="category" from=$submissionFileCategories selected=$category translate=false}
{/fbvFormSection}
{/fbvFormArea}

{if $gridId}
	<input type="hidden" name="gridId" value="{$gridId|escape}" />
{/if}
{if $rowId}
	<input type="hidden" name="rowId" value="{$rowId|escape}" />
{/if}
{if $genreId}
	<input type="hidden" name="genreId" value="{$genreId|escape}" />
{/if}
<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
{fbvFormButtons submitText="common.save"}
</form>
