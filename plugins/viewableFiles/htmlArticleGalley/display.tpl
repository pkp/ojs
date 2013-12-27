{if $galley}
	{foreach from=$styleUrls item=styleUrl}
		<link href="{$styleUrl|escape}" media="all" type="text/css" rel="stylesheet"/>
	{/foreach}
	{$htmlGalleyContents}
{/if}