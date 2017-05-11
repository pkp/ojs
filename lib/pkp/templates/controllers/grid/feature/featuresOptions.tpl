{**
 * controllers/grid/feature/featuresOptions.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Grid features js options.
 *}
{ldelim}
	{foreach name=features from=$features item=feature}
		{$feature->getId()|json_encode}: {ldelim}
			JSClass: {$feature->getJSClass()|json_encode},
			options: {ldelim}
				{foreach name=featureOptions from=$feature->getOptions() key=optionName item=optionValue}
					{$optionName}: {if $optionValue}'{$optionValue|escape:javascript}'{else}false{/if}{if !$smarty.foreach.featureOptions.last},{/if}
				{/foreach}
			{rdelim}
		{rdelim}{if !$smarty.foreach.features.last},{/if}
	{/foreach}
{rdelim}
