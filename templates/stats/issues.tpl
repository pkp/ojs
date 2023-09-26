{**
 * templates/stats/issues.tpl
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * The issues statistics page.
 *
 *}
{extends file="layouts/backend.tpl"}

{block name="page"}

	<div class="pkpStats">
		<pkp-header>
			<h1>{translate key="issue.issues"}</h1>
			<spinner v-if="isLoadingTimeline"></spinner>
			<template slot="actions">
				<date-range
					unique-id="issue-stats-date-range"
					:date-start="dateStart"
					:date-start-min="dateStartMin"
					:date-end="dateEnd"
					:date-end-max="dateEndMax"
					:options="dateRangeOptions"
					date-range-label="{translate key="stats.dateRange"}"
					date-format-instructions-label="{translate key="stats.dateRange.instructions"}"
					change-date-range-label="{translate key="stats.dateRange.change"}"
					since-date-label="{translate key="stats.dateRange.sinceDate"}"
					until-date-label="{translate key="stats.dateRange.untilDate"}"
					all-dates-label="{translate key="stats.dateRange.allDates"}"
					custom-range-label="{translate key="stats.dateRange.customRange"}"
					from-date-label="{translate key="stats.dateRange.from"}"
					to-date-label="{translate key="stats.dateRange.to"}"
					apply-label="{translate key="stats.dateRange.apply"}"
					invalid-date-label="{translate key="stats.dateRange.invalidDate"}"
					date-does-not-exist-label="{translate key="stats.dateRange.dateDoesNotExist"}"
					invalid-date-range-label="{translate key="stats.dateRange.invalidDateRange"}"
					invalid-end-date-max-label="{translate key="stats.dateRange.invalidEndDateMax"}"
					invalid-start-date-min-label="{translate key="stats.dateRange.invalidStartDateMin"}"
					@set-range="setDateRange"
				></date-range>
			</template>
		</pkp-header>
		<div class="pkpStats__container -pkpClearfix">
			<div class="pkpStats__content">
				<div v-if="chartData" class="pkpStats__graph">
					<div class="pkpStats__graphHeader">
						<h2 class="pkpStats__graphTitle -screenReader" id="issue-stats-graph-title">
							{translate key="stats.views"}
						</h2>
						<div class="pkpStats__graphSelectors">
							<div class="pkpStats__graphSelector pkpStats__graphSelector--timelineType">
								<pkp-button
									:aria-pressed="timelineType === 'toc'"
									aria-describedby="issue-stats-graph-title"
									@click="setTimelineType('toc')"
								>
									{translate key="stats.views"}
								</pkp-button>
								<pkp-button
									:aria-pressed="timelineType === 'files'"
									aria-describedby="issue-stats-graph-title"
									@click="setTimelineType('files')"
								>
									{translate key="stats.downloads"}
								</pkp-button>
							</div>
							<div class="pkpStats__graphSelector pkpStats__graphSelector--timelineInterval">
								<pkp-button
									:aria-pressed="timelineInterval === 'day'"
									aria-describedby="issue-stats-graph-title"
									:disabled="!isDailyIntervalEnabled"
									@click="setTimelineInterval('day')"
								>
									{translate key="stats.daily"}
								</pkp-button>
								<pkp-button
									:aria-pressed="timelineInterval === 'month'"
									aria-describedby="issue-stats-graph-title"
									:disabled="!isMonthlyIntervalEnabled"
									@click="setTimelineInterval('month')"
								>
									{translate key="stats.monthly"}
								</pkp-button>
							</div>
						</div>
					</div>
					<table class="-screenReader" role="region" aria-live="polite">
						<caption v-if="timelineType === 'files'">{translate key="stats.downloads.timelineInterval"}</caption>
						<caption v-else>{translate key="stats.views.timelineInterval"}</caption>
						<thead>
							<tr>
								<th scope="col">{translate key="common.date"}</th>
								<th v-if="timelineType === 'files'" scope="col">{translate key="stats.downloads"}</th>
								<th v-else scope="col">{translate key="stats.views"}</th>
							</tr>
						</thead>
						<tbody>
							<tr	v-for="segment in timeline" :key="segment.date">
								<th scope="row">{{ segment.label }}</th>
								<td>{{ segment.value }}</td>
							</tr>
						</tbody>
					</table>
					<line-chart :chart-data="chartData" aria-hidden="true"></line-chart>
					<span v-if="isLoadingTimeline" class="pkpStats__loadingCover">
						<spinner></spinner>
					</span>
				</div>
				<div class="pkpStats__panel" role="region" aria-live="polite">
					<pkp-header>
						<h2>
							{translate key="stats.issues.details"}
							<tooltip
								tooltip="{translate key="stats.issues.tooltip.text"}"
								label="{translate key="stats.issues.tooltip.label"}"
							></tooltip>
							<spinner v-if="isLoadingItems"></spinner>
						</h2>
						<template slot="actions">
							<div class="pkpStats__itemsOfTotal">
								{{
									replaceLocaleParams(itemsOfTotalLabel, {
										count: items.length,
										total: itemsMax
									})
								}}
								<a
									v-if="items.length < itemsMax"
									href="#issueDetailTablePagination"
									class="-screenReader"
								>
									{translate key="common.pagination.label"}
								</a>
							</div>
							<pkp-button
								ref="downloadReportModalButton"
								@click="$modal.show('downloadReport')"
							>
								{translate key="common.downloadReport"}
							</pkp-button>
							<modal
								close-label="{translate key="common.close"}"
								name="downloadReport"
								title={translate key="common.download"}
								@closed="setFocusToRef('downloadReportModalButton')"
							>
								<p>{translate key="stats.issues.downloadReport.description"}</p>
								<table class="pkpTable pkpStats__reportParams">
									<tr class="pkpTable__row">
										<th>{translate key="stats.dateRange"}</th>
										<td>{{ getDateRangeDescription() }}</th>
									</tr>
									<tr
										v-if="searchPhrase"
										class="pkpTable__row">
										<th>{translate key="common.searchPhrase"}</th>
										<td>{{ searchPhrase }}</th>
									</tr>
								</table>
								<action-panel class="pkpStats__reportAction">
									<h2>{translate key="issue.issues"}</h2>
									<p>
										{translate key="stats.issues.downloadReport.downloadIssues.description"}
									</p>
									<template slot="actions">
										<pkp-button
											@click="downloadReport"
										>
											{translate key="stats.issues.downloadReport.downloadIssues"}
										</pkp-button>
									</template>
								</action-panel>
								<action-panel class="pkpStats__reportAction">
									<h2>{translate key="stats.timeline"}</h2>
									<p>
										{{ getTimelineDescription() }}
									</p>
									<template slot="actions">
										<pkp-button
											@click="downloadReport('timeline')"
										>
											{translate key="stats.timeline.downloadReport.downloadTimeline"}
										</pkp-button>
									</template>
								</action-panel>
							</modal>
						</template>
					</pkp-header>
					<pkp-table
						labelled-by="issueDetailTableLabel"
						:class="tableClasses"
						:columns="tableColumns"
						:rows="items"
						:order-by="orderBy"
						:order-direction="orderDirection"
						@order-by="setOrderBy"
					>
						<search
							slot="thead-title"
							class="pkpStats__titleSearch"
							:search-phrase="searchPhrase"
							search-label="{translate key="stats.issues.searchIssueDescription"}"
							@search-phrase-changed="setSearchPhrase"
						></search>
						<template slot-scope="{ row, rowIndex }">
							<table-cell
								v-for="(column, columnIndex) in tableColumns"
								:key="column.name"
								:column="column"
								:row="row"
								:tabindex="!rowIndex && !columnIndex ? 0 : -1"
							>
								<template v-if="column.name === 'title'">
									<a
										:href="row.issue.publishedUrl"
										class="pkpStats__itemLink"
										target="_blank"
									>
										<span class="pkpStats__itemTitle">{{ row.issue.identification }}</span>
									</a>
								</template>
							</table-cell>
						</template>
					</pkp-table>
					<div v-if="!items.length" class="pkpStats__noRecords">
						<template v-if="isLoadingItems">
							<spinner></spinner>
							{translate key="common.loading"}
						</template>
						<template v-else>
							{translate key="stats.issues.none"}
						</template>
					</div>
					<pagination
						v-if="lastPage > 1"
						id="issueDetailTablePagination"
						:current-page="currentPage"
						:is-loading="isLoadingItems"
						:last-page="lastPage"
						@set-page="setPage"
					></pagination>
				</div>
			</div>
		</div>
	</div>
{/block}

