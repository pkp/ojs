{**
 * templates/controllers/statistics/form/reportGeneratorForm.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Report generator form template.
 *
 *}
<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#reportGeneratorForm').pkpHandler('$.pkp.statistics.ReportGeneratorFormHandler',
			{ldelim}
				fetchArticlesUrl: '{url op=fetchArticlesInfo}',
				articleSelectSelector: '#articles',
				issueSelectSelector: '#issues',
				objectTypeSelectSelector: '#objectTypes',
				fileTypeSelectSelector: '#fileTypes',
				fileAssocTypes: {ldelim}
					{foreach from=$fileAssocTypes key=key item=assocType}
						{$key|escape:"javascript"}: '{$assocType|escape:"javascript"}',
					{/foreach}
				{rdelim},
				fetchRegionsUrl: '{url op=fetchRegions}',
				regionSelectSelector: '#regions',
				countrySelectSelector: '#countries'
			{rdelim}
		);
	{rdelim});
</script>

<form class="pkp_form" id="reportGeneratorForm" method="post" action="{url op="saveReportGenerator"}">
	{fbvElement type="hidden" name="metricType" id="metricType" value=$metricType}
	
	{fbvFormArea id="columnsFormArea" title="manager.statistics.reports.columns"}
		<p>{translate key="manager.statistics.reports.columns.description"}</p>
		{fbvFormSection inline=true size=$fbvStyles.size.SMALL}
			{fbvElement type="select" name="columns[]" id="columns" from=$columnsOptions multiple="multiple" selected=$columns translate=false required=true}
		{/fbvFormSection}
	{/fbvFormArea}
	
	{fbvFormArea id="filterFormArea" title="manager.statistics.reports.filters"}
		{capture assign="filterByContextContent"}
			{fbvFormSection title="issue.issues" for="issues" inline=true size=$fbvStyles.size.MEDIUM}
				{fbvElement type="select" name="issues[]" id="issues" from=$issuesOptions multiple="multiple" selected=$issues translate=false}
			{/fbvFormSection}
		
			{fbvFormSection title="article.articles" for="articles" inline=true size=$fbvStyles.size.MEDIUM}
				{fbvElement type="select" name="articles[]" id="articles" from=$articlesOptions multiple="multiple" selected=$articles translate=false}
			{/fbvFormSection}
		{/capture}
		<div id="filterByContextWrapper" class="left full">
			{include file="controllers/extrasOnDemand.tpl"
				id="filterByContextExtras"
				widgetWrapper="#filterByContextWrapper"
				moreDetailsText="manager.statistics.reports.filters.byContext"
				moreDetailsLabel="manager.statistics.reports.filters.byContext.label"
				extraContent=$filterByContextContent
			}
		</div>
		
		{capture assign="filterByObjectContent"}
			{fbvFormSection title="manager.statistics.reports.objectType" for="objectTypes" inline=true size=$fbvStyles.size.SMALL}
				{fbvElement type="select" name="objectTypes[]" id="objectTypes" from=$objectTypesOptions multiple="multiple" selected=$objectTypes translate=false}
			{/fbvFormSection}
			{fbvFormSection title="common.fileType" for="fileTypes" inline=true size=$fbvStyles.size.SMALL}
				{fbvElement type="select" name="fileTypes[]" id="fileTypes" from=$fileTypesOptions multiple="multiple" selected=$fileTypes translate=false}
			{/fbvFormSection}
			{fbvFormSection title="manager.statistics.reports.objectId" for="objectIds" inline=true size=$fbvStyles.size.SMALL}
				{fbvElement type="text" name="objectIds" id="objectIds" value=$objectIds label="manager.statistics.reports.objectId.label"}
			{/fbvFormSection}
		{/capture}
		<div id="filterByObjectWrapper" class="left full">
			{include file="controllers/extrasOnDemand.tpl"
				id="filterByObjectExtras"
				widgetWrapper="#filterByObjectWrapper"
				moreDetailsText="manager.statistics.reports.filters.byObject"
				moreDetailsLabel="manager.statistics.reports.filters.byObject.label"
				extraContent=$filterByObjectContent
			}
		</div>
		
		{capture assign="filterByTimeContent"}		
			{fbvFormSection title="common.month" for="month" inline=true size=$fbvStyles.size.SMALL}
				{fbvElement type="text" name="month" id="month" value=$month label="manager.statistics.reports.month.label"}
			{/fbvFormSection}
			{fbvFormSection title="search.dateFrom" for="monthFrom" inline=true size=$fbvStyles.size.SMALL}
				{fbvElement type="text" name="monthFrom" id="monthFrom" value=$monthFrom}
			{/fbvFormSection}
			{fbvFormSection title="search.dateTo" for="monthTo" inline=true size=$fbvStyles.size.SMALL}
				{fbvElement type="text" name="monthTo" id="monthTo" value=$monthTo}
			{/fbvFormSection}
			{fbvFormSection for="currentMonth" inline=true size=$fbvStyles.size.SMALL list=true}
				{fbvElement type="checkbox" name="currentMonth" id="currentMonth" checked=$currentMonth label="manager.statistics.reports.currentMonth"}
			{/fbvFormSection}
			
			<div style="clear:both"></div>
			
			{fbvFormSection title="common.day" for="day" inline=true size=$fbvStyles.size.SMALL}
				{fbvElement type="text" name="day" id="day" value=$day label="manager.statistics.reports.day.label"}
			{/fbvFormSection}
			{fbvFormSection title="search.dateFrom" for="dayFrom" inline=true size=$fbvStyles.size.SMALL}
				{fbvElement type="text" name="dayFrom" id="dayFrom" value=$dayFrom}
			{/fbvFormSection}
			{fbvFormSection title="search.dateTo" for="dayTo" inline=true size=$fbvStyles.size.SMALL}
				{fbvElement type="text" name="dayTo" id="dayTo" value=$dayTo}
			{/fbvFormSection}
			{fbvFormSection for="today" inline=true size=$fbvStyles.size.SMALL list=true}
				{fbvElement type="checkbox" name="today" id="today" checked=$today label="manager.statistics.reports.today"}
			{/fbvFormSection}
		{/capture}
		
		<div id="filterByTimeWrapper" class="left full">
			{include file="controllers/extrasOnDemand.tpl"
				id="filterByTimeExtras"
				widgetWrapper="#filterByTimeWrapper"
				moreDetailsText="manager.statistics.reports.filters.byTime"
				moreDetailsLabel="manager.statistics.reports.filters.byTime.label"
				extraContent=$filterByTimeContent
			}
		</div>
		
		{if $countriesOptions}
			{capture assign="filterByLocationContent"}
				{fbvFormSection title="common.country" for="countries" inline=true size=$fbvStyles.size.SMALL}
					{fbvElement type="select" name="countries[]" id="countries" from=$countriesOptions multiple="multiple" selected=$countries translate=false}
				{/fbvFormSection}
				{fbvFormSection title="manager.statistics.region" for="regions" inline=true size=$fbvStyles.size.SMALL}
					{fbvElement type="select" name="regions[]" id="regions" from=$regionsOptions multiple="multiple" selected=$regions translate=false}
				{/fbvFormSection}
				{fbvFormSection title="manager.statistics.city" for="cityNames" inline=true size=$fbvStyles.size.MEDIUM}
					{fbvElement type="text" name="cityNames" id="cityNames" value=$cityNames label="manager.statistics.reports.cities.label"}
				{/fbvFormSection}
			{/capture}
			<div id="filterByLocationWrapper" class="left full">
				{include file="controllers/extrasOnDemand.tpl"
					id="filterByLocationExtras"
					widgetWrapper="#filterByLocationWrapper"
					moreDetailsText="manager.statistics.reports.filters.byLocation"
					moreDetailsLabel="manager.statistics.reports.filters.byLocation.label"
					extraContent=$filterByLocationContent
				}
			</div>
		{/if}
		
	{/fbvFormArea}
	
	{fbvFormArea id="orderByFormArea" title="manager.statistics.reports.orderBy"}
		{foreach from=$orderColumnsOptions item=item key=key}
			{fbvFormSection inline=true size=$fbvStyles.size.SMALL}
				{fbvElement type="select" name="orderByColumn[]" id="orderByColumn-$key" from=$orderColumnsOptions defaultValue=0 defaultLabel="manager.statistics.reports.columns"|translate selected=$orderByColumn translate=false}
			{/fbvFormSection}
			{fbvFormSection inline=true size=$fbvStyles.size.SMALL}
				{fbvElement type="select" name="orderByDirection[]" id="orderByDirection-$key" from=$orderDirectionsOptions defaultValue=0 defaultLabel="manager.statistics.reports.orderDir"|translate selected=$orderByDirection translate=false}
			{/fbvFormSection}
			<div style="clear:both"></div>
		{/foreach}
	{/fbvFormArea}
	
	{fbvFormArea id="reportUrlFormArea" title="manager.statistics.reports.reportUrl"}
		{fbvFormSection}
			{fbvElement type="text" name="reportUrl" id="reportUrl" value=$reportUrl label="manager.statistics.reports.reportUrl.label"}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons id="reportGeneratorFormSubmit" submitText="manager.statistics.reports.generateReport" hideCancel=true}
</form>
