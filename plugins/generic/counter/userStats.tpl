{**
 * plugins/generic/counter/userStats.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Counter plugin index
 *}
{strip}
  {assign var="pageTitle" value="plugins.generic.user.counter"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li {if $mode == 'ALL_VIEWINGS'}class="current"{/if}><a href="{url op="userStats" mode="ALL_VIEWINGS"}">{translate key="plugins.generic.user.numberArticlesPerMonth"}</a></li>
	<li {if $mode == 'MOST_VIEWED'}class="current"{/if}><a href="{url op="userStats" mode="MOST_VIEWED"}">{translate key="plugins.generic.user.mostViewed"}</a></li>
</ul>

    {if $mode == 'ALL_VIEWINGS'}
        <br/><br/>

        {foreach from=$results key=year item=yearResult}
        <h4>{$year} Viewings:</h4>
        <table class="listing" width="100%">
           <tr><td colspan="14" class="headseparator">&nbsp;</td></tr>
           <tr class="heading" valign="bottom">
              <td style="width:"22%"">Journal</td>
              {foreach from=$yearResult.months item=i}<td style="width:5%;text-align:center">{$i}</td>{/foreach}
              <td style="text-align:right">Total</td>
           </tr>
           <tr><td colspan="14" class="headseparator">&nbsp;</td></tr>
         {foreach from=$yearResult.results item=i}
            <tr>
               <td> <a href="{url op="userStatsMonthlyBreakDown" year=$year jid=$i.id}">{$i.title}</a></td>
               {foreach from=$i.monthTotal key=k item=j}
                    {if $j > 0 }
                        <td style="text-align:center">
                        <a href="{url op="userStatsMonthlyBreakDown" year=$year month=$k jid=$i.id}">{$j}</a>
                        </td>
                    {else}
                        <td style="text-align:center"> </td>
                    {/else}
                    {/if}
                {/foreach}

               <td style="text-align:right">
                 <a href="{url op="userStatsMonthlyBreakDown" year=$year jid=$i.id}">{$i.yearTotal}</a>
               </td>
            </tr>
            <tr><td colspan="14" class="separator">&nbsp;</td></tr>
         {/foreach}
            <tr><td colspan="14" class="endseparator">&nbsp;</td></tr>
         </table>

         <br/> <br/>

        {/foreach}

    {/if}
    {if $mode == 'MOST_VIEWED'}
        <br/><br/>

        {foreach from=$results key=journal item=journalResults}
          <h4>{$journal}:</h4>
          <table class="listing" width="100%">
            <tr><td colspan="2" class="headseparator">&nbsp;</td></tr>
              <tr class="heading" valign="top">
                <td class="label" width="20%">Article</td>
                <td class="label" width="20%">Count</td>
              </tr>
            <tr><td colspan="2" class="headseparator">&nbsp;</td></tr>
            {foreach from=$journalResults.allCounts key=k item=article}
              <tr valign="top">

                <td class="label" width="70%"><label><a href="{url journal=$journalResults.journalPath page="article" op="view" path=$article.id}">{$k}</a></label></td>
	        <td class="value" width="30%">{$article.views}</td>
              </tr>
              <tr><td colspan="2" class="separator">&nbsp;</td></tr>
            {/foreach}
          <tr><td colspan="2" class="endseparator">&nbsp;</td></tr>
        </table>
        <br/><br/>
      {/foreach}

    {/if}

{include file="common/footer.tpl"}
