<!-- templates/subscription/subscriptionForm.tpl -->

{**
 * subscriptionForm.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common subscription fields
 *
 * $Id$
 *}
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="status" required="true" key="manager.subscriptions.form.status"}</td>
	<td width="80%" class="value"><select name="status" id="status" class="selectMenu">
	{html_options_translate options=$validStatus selected=$status}
	</select></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="typeId" required="true" key="manager.subscriptions.form.typeId"}</td>
	<td class="value"><select name="typeId" id="typeId" class="selectMenu">
		{iterate from=subscriptionTypes item=subscriptionType}
		<option value="{$subscriptionType->getTypeId()}"{if $typeId == $subscriptionType->getTypeId()} selected="selected"{/if}>{$subscriptionType->getSummaryString()|escape}</option>
		{/iterate}
	</select></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="dateStart" key="manager.subscriptions.form.dateStart"}</td>
	<td class="value" id="dateStart">{html_select_date prefix="dateStart" all_extra="class=\"selectMenu\"" start_year="$yearOffsetPast" end_year="$yearOffsetFuture" time="$dateStart"}</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="dateEnd" key="manager.subscriptions.form.dateEnd"}</td>
	<td class="value" id="dateEnd">
		{html_select_date prefix="dateEnd" start_year="$yearOffsetPast" all_extra="class=\"selectMenu\"" end_year="$yearOffsetFuture" time="$dateEnd"}
		<input type="hidden" name="dateEndHour" value="23" />
		<input type="hidden" name="dateEndMinute" value="59" />
		<input type="hidden" name="dateEndSecond" value="59" />
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="membership" key="manager.subscriptions.form.membership"}</td>
	<td class="value">
		<input type="text" name="membership" value="{$membership|escape}" id="membership" size="30" maxlength="40" class="textField" />
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="referenceNumber" key="manager.subscriptions.form.referenceNumber"}</td>
	<td class="value">
		<input type="text" name="referenceNumber" value="{$referenceNumber|escape}" id="referenceNumber" size="30" maxlength="40" class="textField" />
	</td>
</tr>

<!-- / templates/subscription/subscriptionForm.tpl -->

