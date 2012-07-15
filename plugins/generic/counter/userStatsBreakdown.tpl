{**
 * plugins/generic/counter/userStatsMonthlyBreakdown.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Counter plugin index
 *}
{strip}
  {assign var="pageTitle" value="plugins.generic.user.counter.$type"}
{include file="common/header.tpl"}
{/strip}

<br/>
<h3>{$journalTitle}  ({$date} {$year} ) :</h3>
  <table class="listing" width="100%">
    <thead>
      <tr><td colspan="2" class="headseparator">&nbsp;</td></tr>
      <tr valign="top">
        <td class="label" width="20%">Article</td>
        <td class="label" width="20%">Count</td>
      </tr>
      <tr><td colspan="2" class="headseparator">&nbsp;</td></tr>
    </thead>
    <tbody>
    {foreach from=$results key=year item=result}
      <tr valign="top">
        <td class="label" width="70%"><label><a href="{url journal=$journalPath page="article" op="view" path=$result.articleId}">{$result.title}</a></label></td>
	<td class="value" width="30%">{$result.total_viewings}</td>
      </tr>
      <tr><td colspan="2" class="separator">&nbsp;</td></tr>
    {/foreach}
    <tr><td colspan="2" class="endseparator">&nbsp;</td></tr>
    </tbody>
  </table>
        
{include file="common/footer.tpl"}