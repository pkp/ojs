{**
 * templates/controllers/statistics/form/reportGeneratorForm.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
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
				fetchFormUrl: '{url op=fetchReportGenerator}',
				metricTypeSelectSelector: '#metricType',
				reportTemplateSelectSelector: '#reportTemplate',
				aggregationOptionsSelector: "input[type='checkbox'], #aggregationColumns",
				columnsSelector: '#columns', 
				timeFilterWrapperSelector: '#reportTimeFilterArea',
				currentMonthSelector: '#currentMonth',
				currentDaySelector: '#today',
				rangeByMonthSelector: '#rangeByMonth',
				rangeByDaySelector: '#rangeByDay',
				startDayElementSelector: "select[name='dateStartDay']",
				endDayElementSelector: "select[name='dateEndDay']",
				dateRangeWrapperSelector : '#dateRangeElementsWrapper',
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
	{if $metricTypeOptions}
		{fbvFormArea id="columnsFormArea" title="defaultMetric.availableMetrics"}
			{fbvFormSection inline=true size=$fbvStyles.size.SMALL}
				{fbvElement type="select" name="metricType" id="metricType" from=$metricTypeOptions selected=$metricType translate=false}
			{/fbvFormSection}
		{/fbvFormArea}
	{else}
		{fbvElement type="hidden" name="metricType" id="metricType" value=$metricType}
	{/if}
	
	{if $reportTemplateOptions}
		{fbvFormArea id="reportTemplatesFormArea" title="manager.statistics.reports.defaultReportTemplates"}
			{fbvFormSection inline=true size=$fbvStyles.size.SMALL}
				{fbvElement type="select" name="reportTemplate" id="reportTemplate" from=$reportTemplateOptions selected=$reportTemplate translate=false}
			{/fbvFormSection}
			{fbvFormSection for="aggregationColumns" title="manager.statistics.reports.aggregationColumns" list=true}
				{fbvElement type="checkboxgroup" name="aggregationColumns" id="aggregationColumns" from=$aggregationOptions selected=$selectedAggregationOptions translate=false}
			{/fbvFormSection}
		{/fbvFormArea}
	{/if}
	{if $showMonthInputs || $showDayInputs}
		{fbvFormArea id="reportTimeFilterArea" title="manager.statistics.reports.filters.byTime"}			
			{fbvFormSection for="currentMonth" size=$fbvStyles.size.SMALL list=true}
				{fbvElement type="radio" name="timeFilterOption" value=$smarty.const.TIME_FILTER_OPTION_CURRENT_DAY id="today" checked=$today label="manager.statistics.reports.today"}
				{fbvElement type="radio" name="timeFilterOption" value=$smarty.const.TIME_FILTER_OPTION_CURRENT_MONTH id="currentMonth" checked=$currentMonth label="manager.statistics.reports.currentMonth"}
			{/fbvFormSection}
			{fbvFormSection title="manager.statistics.reports.filters.byTime.dimensionSelector" list=true size=$fbvStyles.size.SMALL inline=true}
				{fbvElement type="radio" name="timeFilterOption" value=$smarty.const.TIME_FILTER_OPTION_RANGE_DAY id="rangeByDay" inline=true checked=$byDay label="common.day"}
				{fbvElement type="radio" name="timeFilterOption" value=$smarty.const.TIME_FILTER_OPTION_RANGE_MONTH id="rangeByMonth" checked=$byMonth label="common.month"}
			{/fbvFormSection}
			<div id="dateRangeElementsWrapper">
				{fbvFormSection title="search.dateFrom" inline=true size=$fbvStyles.size.SMALL}
					{html_select_date prefix="dateStart" time=$dateStart start_year=$timeFilterStartYear all_extra="class=\"selectMenu\"" end_year=$timeFilterEndYear field_order=YMD}
				{/fbvFormSection}
				{fbvFormSection title="search.dateTo" inline=true size=$fbvStyles.size.SMALL}
					{html_select_date prefix="dateEnd" time=$dateEnd start_year=$timeFilterStartYear all_extra="class=\"selectMenu\"" end_year=$timeFilterEndYear field_order=YMD}
				{/fbvFormSection}
			</div>
		{/fbvFormArea}
	{/if}
	
	{capture assign="advancedOptionsContent"}
		{fbvFormArea id="columnsFormArea" title="manager.statistics.reports.columns"}
			<p>{translate key="manager.statistics.reports.columns.description"}</p>
			{fbvFormSection inline=true size=$fbvStyles.size.SMALL}
				{fbvElement type="select" name="columns[]" id="columns" from=$columnsOptions multiple="multiple" selected=$columns translate=false required=true}
			{/fbvFormSection}
		{/fbvFormArea}
		
		{fbvFormArea id="filterFormArea" title="manager.statistics.reports.filters"}
			{if $issuesOptions}
				{fbvFormSection label="manager.statistics.reports.filters.byContext"}
					<p>{translate key="manager.statistics.reports.filters.byContext.description"}</p>
					{fbvFormSection description="issue.issues" for="issues" inline=true size=$fbvStyles.size.MEDIUM}
						{fbvElement type="select" name="issues[]" id="issues" from=$issuesOptions multiple="multiple" selected=$issues translate=false}
					{/fbvFormSection}
					{if $showArticleInput}
						{fbvFormSection description="article.articles" for="articles" inline=true size=$fbvStyles.size.MEDIUM}
							{fbvElement type="select" name="articles[]" id="articles" multiple="multiple" translate=false}
						{/fbvFormSection}
					{/if}
				{/fbvFormSection}
			{/if}
			
			{fbvFormSection label="manager.statistics.reports.filters.byObject"}
				<p>{translate key="manager.statistics.reports.filters.byObject.description"}</p>
				{fbvFormSection description="manager.statistics.reports.objectType" for="objectTypes" inline=true size=$fbvStyles.size.SMALL}
					{fbvElement type="select" name="objectTypes[]" id="objectTypes" from=$objectTypesOptions multiple="multiple" selected=$objectTypes translate=false}
				{/fbvFormSection}
				{if $fileTypesOptions}
					{fbvFormSection description="common.fileType" for="fileTypes" inline=true size=$fbvStyles.size.SMALL}
						{fbvElement type="select" name="fileTypes[]" id="fileTypes" from=$fileTypesOptions multiple="multiple" selected=$fileTypes translate=false}
					{/fbvFormSection}
				{/if}
				
				{fbvFormSection description="manager.statistics.reports.objectId" for="objectIds" inline=true size=$fbvStyles.size.SMALL}
					{fbvElement type="text" name="objectIds" id="objectIds" value=$objectIds label="manager.statistics.reports.objectId.label"}
				{/fbvFormSection}
			{/fbvFormSection}
				
			{if $countriesOptions}
				{fbvFormSection label="manager.statistics.reports.filters.byLocation"}
					<p>{translate key="manager.statistics.reports.filters.byLocation.description"}</p>
					{fbvFormSection description="common.country" for="countries" inline=true size=$fbvStyles.size.SMALL}
						{fbvElement type="select" name="countries[]" id="countries" from=$countriesOptions multiple="multiple" selected=$countries translate=false}
					{/fbvFormSection}
					{if $showRegionInput}
						{fbvFormSection description="manager.statistics.region" for="regions" inline=true size=$fbvStyles.size.SMALL}
							{fbvElement type="select" name="regions[]" id="regions" from=$regionsOptions multiple="multiple" selected=$regions translate=false}
						{/fbvFormSection}
					{/if}
					{if $showCityInput}
						{fbvFormSection description="manager.statistics.city" for="cityNames" inline=true size=$fbvStyles.size.MEDIUM}
							{fbvElement type="text" name="cityNames" id="cityNames" value=$cityNames label="manager.statistics.reports.cities.label"}
						{/fbvFormSection}
					{/if}
				{/fbvFormSection}
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
	{/capture}
	
	<div id="advancedOptionsWrapper" class="left full">
		{include file="controllers/extrasOnDemand.tpl"
			id="advancedOptionsExtras"
			widgetWrapper="#advancedOptionsWrapper"
			moreDetailsText="manager.statistics.reports.advancedOptions"
			moreDetailsLabel="manager.statistics.reports.advancedOptions.label"
			extraContent=$advancedOptionsContent
		}
	</div>
	
	{fbvFormArea id="reportUrlFormArea" title="manager.statistics.reports.reportUrl"}
		{fbvFormSection}
			{fbvElement type="text" name="reportUrl" id="reportUrl" value=$reportUrl label="manager.statistics.reports.reportUrl.label"}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons id="reportGeneratorFormSubmit" submitText="manager.statistics.reports.generateReport" hideCancel=true}
</form>
