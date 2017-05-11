{**
 * templates/controllers/listbuilder/listbuilderOptions.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Listbuilder java script handler options.
 *}


gridId: {$grid->getId()|json_encode},
fetchRowUrl: {url|json_encode op='fetchRow' params=$gridRequestArgs escape=false},
fetchOptionsUrl: {url|json_encode op='fetchOptions' params=$gridRequestArgs escape=false},
availableOptions: {$availableOptions|json_encode},
{if $grid->getSaveType() == $smarty.const.LISTBUILDER_SAVE_TYPE_INTERNAL}
	saveUrl: {url|json_encode op='save' params=$gridRequestArgs escape=false},
	saveFieldName: null,
{else}{* LISTBUILDER_SAVE_TYPE_EXTERNAL *}
	saveUrl: null,
	saveFieldName: {$grid->getSaveFieldName()|json_encode},
{/if}
sourceType: {$grid->getSourceType()|json_encode},
bodySelector: '#{$gridActOnId|escape:javascript}',
features: {include file='controllers/grid/feature/featuresOptions.tpl' features=$features},
