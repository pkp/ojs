{**
 * footer.tpl
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site footer.
 *
 * $Id$
 *}
{if $displayCreativeCommons}
	{translate key="common.ccLicense"}
{/if}
{if $pageFooter}
<br /><br />
{$pageFooter}
{elseif $currentJournal}{strip}{* Include the ISSN as a default footer, if available. *}
	{if $currentJournal->getSetting('onlineIssn')}{assign var="issn" value=$currentJournal->getSetting('onlineIssn')}
	{elseif $currentJournal->getSetting('printIssn')}{assign var="issn" value=$currentJournal->getSetting('printIssn')}
	{elseif $currentJournal->getSetting('issn')}{assign var="issn" value=$currentJournal->getSetting('issn')}
	{/if}
	{if $issn}<br/><br/>
	        {translate key="journal.issn"}:&nbsp;{$issn|escape}
	{/if}
{/strip}{/if}
{call_hook name="Templates::Common::Footer::PageFooter"}
</div><!-- content -->
</div><!-- main -->
</div><!-- body -->

{get_debug_info}
{if $enableDebugStats}
<div id="footer">
	<div id="footerContent">
		<div class="debugStats">
		{translate key="debug.executionTime"}: {$debugExecutionTime|string_format:"%.4f"}s<br />
		{translate key="debug.databaseQueries"}: {$debugNumDatabaseQueries|escape}<br/>
		{translate key="debug.memoryUsage"}: {$debugMemoryUsage|escape}<br/>
		{if $debugNotes}
			<strong>{translate key="debug.notes"}</strong><br/>
			{foreach from=$debugNotes item=note}
				{translate key=$note[0] params=$note[1]}<br/>
			{/foreach}
		{/if}
		</div>
	</div><!-- footerContent -->
</div><!-- footer -->
{/if}

</div><!-- container -->
</body>
</html>
