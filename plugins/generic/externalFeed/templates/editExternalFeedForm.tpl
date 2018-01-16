{**
 * plugins/generic/externalFeed/templates/editExternalFeedForm.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for editing a external feed
 *}
 
<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#externalFeedForm').pkpHandler(
			'$.pkp.controllers.form.AjaxFormHandler'
		);
	{rdelim});
</script>

<form id="externalFeedForm" class="pkp_form" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.externalFeed.controllers.grid.ExternalFeedGridHandler" op="updateExternalFeed" existingPageName=$blockName escape=false}">
	{csrf}
	
	{if $feedId}
		<input type="hidden" name="feedId" value="{$feedId|escape}" />
	{/if}
	
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="ExternalFeedFormNotification"}
	
	{fbvFormSection}
		{fbvFormSection for="title" title="plugins.generic.externalFeed.form.title" description="plugins.generic.externalFeed.form.titleDescription"}
			{assign var="formLocale" value=$formLocale|escape}
			{fbvElement type="text" id="title" name=title[$formLocale] value=$title}
		{/fbvFormSection}
		
		{fbvFormSection for="feedUrl" title="plugins.generic.externalFeed.form.feedUrl" description="plugins.generic.externalFeed.form.feedUrlDescription"}
			{fbvElement type="text" id="feedUrl" value=$feedUrl|escape}
		{/fbvFormSection}
	{/fbvFormSection}
	
	{fbvFormSection list=true description="plugins.generic.externalFeed.form.display"}
		
		{fbvElement type="checkbox" name="displayHomepage" id="displayHomepage" value="1" 
			label="plugins.generic.externalFeed.form.displayHomepage" checked=$displayHomepage|compare:1"}
			
		{if $displayBlock eq $smarty.const.EXTERNAL_FEED_DISPLAY_BLOCK_HOMEPAGE}
			{assign var="displayBlockNone" value=false}
			{assign var="displayBlockHomepage" value=true}
			{assign var="displayBlockAll" value=false}
		{elseif $displayBlock eq $smarty.const.EXTERNAL_FEED_DISPLAY_BLOCK_ALL}
			{assign var="displayBlockNone" value=false}
			{assign var="displayBlockHomepage" value=false}
			{assign var="displayBlockAll" value=true}
		{else}
			{assign var="displayBlockNone" value=true}
			{assign var="displayBlockHomepage" value=false}
			{assign var="displayBlockAll" value=false}
		{/if}
		{fbvElement type="radio" name="displayBlock" id="displayBlock-none" value="0" 
			label="plugins.generic.externalFeed.form.displayBlockNone" checked=$displayBlockNone}
		{fbvElement type="radio" name="displayBlock" id="displayBlock-homepage" value="1" 
			label="plugins.generic.externalFeed.form.displayBlockHomepage" checked=$displayBlockHomepage}
		{fbvElement type="radio" name="displayBlock" id="displayBlock-all" value="2" 
			label="plugins.generic.externalFeed.form.displayBlockAll" checked=$displayBlockAll}
		
	{/fbvFormSection}
	
	
	{fbvFormSection}
		{if $limitItems}
			{assign var="limitItemsChecked" value=true}
		{else}
			{assign var="limitItemsChecked" value=false}
		{/if}
		<input type="checkbox" name="limitItems" id="limitItems" value="1" {if $limitItemsChecked} checked="checked" {/if}>
		{translate key="plugins.generic.externalFeed.form.recentItems1"}
		<input type="text" name="recentItems" id="recentItems" value="{$recentItems|escape}" size="2" maxlength="90" placeholder="enter number here" >
		{translate key="plugins.generic.externalFeed.form.recentItems2"}
	{/fbvFormSection}
	
	{fbvFormButtons id="externalFeedSubmit" submitText="common.save" hideCancel=true}
	
</form>