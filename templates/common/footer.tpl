{**
 * footer.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site footer.
 *
 * $Id$
 *}

{if $pageFooter}
<br /><br />
{$pageFooter}
{/if}
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
		{translate key="debug.databaseQueries"}: {$debugNumDatabaseQueries}<br/>
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
