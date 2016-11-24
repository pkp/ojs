{**
* plugins/generic/externalFeed/templates/homepage.tpl
*
* Copyright (c) 2003-2013 John Willinsky
* Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
*
* Template for external feed content on journal homepage
*}

<div id="externalFeedsHome">

	{foreach from=$entries item=entry}
		<h3>{$entry.feedTitle}</h3>
		
		<table class="externalFeeds">
			<tr>
				<td colspan="2" class="headseparator">&nbsp;</td>
			</tr>
			
			{assign var="separator" value=''}
			
			{foreach from=$entry.items item=item}
				{$separator}
			 	<tr class="title">
					<td colspan="2" class="title">
						<h4>{$item->get_title()}</h4>
					</td>
				</tr>
				<tr class="description">
					<td colspan="2" class="description">{$item->get_description()}</td>
				</tr>
				<tr class="details">
					<td class="posted">
					{translate key="plugins.generic.externalFeed.posted"}: {$item->get_date()|@strtotime|date_format:"%Y-%m-%d"}
					</td>
					<td class="more">
						<a href="{$item->get_permalink()}" target="_blank"> {translate key="plugins.generic.externalFeed.more"}</a>
					</td>
				</tr>
				
				{assign var="separator" value='<tr><td colspan="2" class="separator">&nbsp;</td></tr>'}
			 {/foreach}
			 
			 <tr>
			 	<td colspan="2" class="endseparator">&nbsp;</td>
			 </tr>
		</table>
	{/foreach}
	
</div>
