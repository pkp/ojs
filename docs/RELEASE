OJS 2.4.8-5 Release Notes
CVS tag: ojs-2_4_8-5
Release date: May 23, 2019
==============================

Configuration Changes
---------------------

See config.TEMPLATE.inc.php for a description and examples of all supported
configuration parameters.

New config.inc.php parameters:
	- lockss:pln_network - PLN staging server host, where content deposits for
		the LOCKSS network will be sent. See http://pkp.sfu.ca/pkp-lockss
		for more details.

	- locks:pln_status_docs - The URL of a page describing the PLN deposit
		processing status table, and the terms used in it.

Bug Fixes
---------
	#345: PKPRequest::getBasePath() returns incorrect results with mod_rewrite on subdirectory
	#366: Journal statistics from previous years not available if no object was published
	#550: Disabled journals exposed in site-wide OAI
	#619: Can't SWORD deposit into DSpace 3.2
	#622: copyAccessLogFileTool reports "Success" even when shell command fails
	#623: copyAccessLogFileTool confuses Solaris with *.*
	#625: Change in documentation for page numbers in setup
	#739: Allow new COUNTER Releases and Reports
	#760: Plugins scheduled tasks files hour setting are using a non existing hour
	#804: Crossref translations contain untranslated English and unused keys
	#808: In the Crossref Issue Export, messaging does not match function
	#816: Pdf links not displayed in search results depending on locale
	#817: lib/password_compat includes .git file in release tarball
	#818: Native export fails when missing copyright holders
	#819: piwik plugin doesn't play well with others
	#822: Wrong URL when exporting and registering DOIs via command line
	#831: Password reset fails with addition of expiration token
	#842: Missing page title for site registration
	#843: Implement statistics dimensions settings
	#845: Allow inheritance of DOI export -> Crossref export -> EZID export
	#847: File download is not being logged anymore
	#848: ReportPlugin::report() parameter $request is missing in UsageStatsReportPlugin::report()
	#855: DataCite export link for issues is missing
	#859: Enable exclude and clear of issue objects DOIs also if DOI shouldn't be assigned to the issues
	#862: Implement better logging for web service requests
	#872: OJS: DOI plugins' options have to be renamed
	#873: The version.xml descriptor has invalid XML in OJS 2.4.7
	#880: Archived usage stats log files are taking too much disk space
	#881: Incorrect notification signature
	#884: Permit X in ONIX-formatted ISSNs
	#885: Browse plugin drops whitespace in before HTML header
	#889: Fatal error in editing submission metadata on missing section
	#890: UsageStats settings form enhancements
	#891: Fix METS export XML error message
	#895: Backport *8636* NOTIFICATION_TYPE_WARNING Content
	#897: ThesisFeedPlugin is missing request type check
	#899: Google Analytics setting references "boolean instead of "bool"
	#917: Update references to Codehaus
	#919: Notify users fails when notifying "All published authors"
	#935: Poor SQL performance for ReviewFormDAO::getActiveByAssocId()
	#947: Use of proxy settings is not consistent
	#959: es_ES translation of plugins.generic.usageStats.usageStatsLoaderName seems off
	#999: Stats processing is missing log entries that have pub ids
	#1001: Usage event plugin should not use public ids
	#1007: Custom report generator leaves Issue Galleys untranslated
	#1009: Remove OpenAds plugin
	#1010: Time filters options in custom report generator are not working
	#1013: Ajax form submit controls doesn't get enabled after form submission
	#1015: Let custom report generator knows about optional dimensions
	#1036: Merging users messes up signoffs table
	#1039: Correct announcement publication and expiry date comparisons
	#1054: Split review reminder emails to separate request and review reminders
	#1062: localized article cover images
	#1087: Crossref plugin doesn't support newer content-types
	#1088: Not all issues displayed for the DOI export/registration
	#1092: OAI-PMH interface strips HTML, but leaves HTML entities
	#1097: Escaped HTML is slipping into Crossref exports
	#1653: Native Import's articles' copyright holder does not default locale
	#1616: One-click reviewer access plus attachment add causes apparently empty recipient list
	#1607: PLN Deposits fail if metadata contains HTML entities
	#1596: Mail::getReplyToString() is noisy
	#1585: Crossref plugin tries to register objects without DOIs
	#1564: status update for submission archive only happens when email is sent
	#1560: update all locales before OJS 2.4.8-1 release
	#1559: do not consider file size in mEDRA DOI registration for remote galleys
	#1558: multiple DOI XML file registrations
	#1555: first Scheduled Task Log filename is untranslated
	#1544: da_DK email template OFR_REVIEW_REMINDER_LATE contains empty subject
	#1543: DOI automatic registration
	#1534: PLN Plugin Missing Deposit Type Prevents Deposits
	#1533: PLN Plugin SQL and Non-Aggregated Columns
	#1522: TinyMCE missing on manager/editUser
	#1507: RECAPTCHA breaks on SSL if SSL is optional
	#1498: Improve Crossref statuses
	#1496: "Primary contact" used in preamble to terms of use
	#1493: Mailing list warning and potential message duplication
	#1487: Timed view reports wrong numbers when metrics data refers to non existing galley
	#1464: Extend issue and article publication year to minimum year - 50
	#1449: CrossRef minor improvements
	#1444: Fix admin.mergerUsers.noneEnrolled locale key typo
	#1433: SPF fixes should be reverted and altered
	#1401: PKP PLN check for scheduled_tasks is missing parameter
	#1381: alling mkdirtree when open_basedir prevents access to a folder triggers infinite recursion
	#1367: Consider articles with no publication date for the Crossref export/registration
	#1353: Navbar user home should stay in journal context for single journal installs
	#1303: Reload Default Email Templates misses deleted templates and deletes plugin data
	#1238: Article publication date incorrectly set to "today"
	#1187: consider proxy settings for external feed plugin
	#1183: make the proxy use for web serviece requests optional
	#1145: Native import/export DTD tweaks
	#1142: 2.4.3 Patch command picks wrong file
	#1130: Typo in Google Analytics Plugin
	#1129: .git file in ojs 2.4.8 patch (plugins/reports/counter/classes/COUNTER)
	#1074: Disallow numeric public issue and article ids
	#1013: Ajax form submit controls doesn't get enabled after form submission
	#1001: Usage event plugin should not use public ids
	#862: Implement better logging for web service requests
	#1621: Missing $request param in comment form emails
	#1665: Make object for review cover images adaptable to the window size
	#1720: Homogenise max_length for session_id columns in OJS and OCS
	#1749: remove PHP 5.3 code from OJS 2.4.8
	#1788: Fix incorrect datetime value
	#1820: Title parameter in multiple locale.xml files is $param instead of $title
	#1877: DataCite export's "publisher" should be clarified
	#1894: Dataverse plugin overrides NOTIFICATION_TYPE_ERROR message
	#1901: Update Crossref DOI display according to their new guidelines
	#1905: Acron really, really wants to run... (when perhaps it shouldn't)
	#1929: locale key parameter missing
	#1959: PubmedNlm30CitationSchemaFilterTest and CURL SSL Version
	#2068: DOI Export Plugin assertion "register" is noisy
	#2119: BibTeX plugin missing comma between pages and doi element
	#2145: Correct SubmissionProofreaderHandler warning
	#2156: OJS install/upgrade documentation is slightly inaccurate
	#2164: Bad link in Crossref settings form after pkp/pkp-lib#1498
	#2232: Smarty doesn't understand Validation::isLoggedIn() in if statements
	#2300: Form method="GET" (default) does not work with `path_info_disabled` URLs.
	#2327: ArticleSearchIndex::_updateTextIndex() clears prior indexed content
	#2348: Public Profile link from comments is broken
	#333: CrossRef XML Export - Ability to handle roman numerals.
	#2003: [OJS3] Filename problems
	#2076: Review page counting code: MEDRA and EndNote
	#2220: [OJS 2.x] jbimages plugin appears on registration page
	#2258: Crossref 4.3.6 schema URL changed
	#2272: Exclude content from disabled journals in search results
	#2442: Sidebar search does not work outside of search page
	#2547: OJS 2.4.x: Article Event log message is untranslated
	#2608: Enable ReCAPTCHA v2 as an option
	#2634: [Ojs 2.4.x] Statistics considerably reduced
	#2660: Permit HTTPS support in ORCIDs
	#2693: OAI appears to be willing to provide records before the earliest record date
	#2739: Change precedence of Google Scholar date options
	#2850: remote galleys and supp files display in all layout.tpl files
	#2852: fix spanish loclae for tinyMCE plugin
	#2856: [OJS] Changed code of plugins/blocks/navigation/block.tpl disabled Lucene Autocomplete
	#3062: wrong element attributes in oai_marc
	#3211: Correct typo of namespace in oai-nlm metadata plugin (OJS 2.4.8)
	#2243: Add status check to PublishedArticleDAO getters
	#2683: Remove password value from user block
	#3371: Return only distinct interests
	#3425: Fix ReCAPTCHA, correct magic numbers
	#3529: Backport 3.x abstract word count to 2.4.x
	#3944: Properly set content-type in JSON responses
	#4167: Allow arbitrary URL for piwik, not just http protocol
	#4180: Adapt OJS 2.x for PHP7
	#4180: Remove PHP4 considerations (mostly reference abuse)
	#4263: fix missing user id in event log for one-click reviews
	#4349: Fix PHP warning on Crossref Export for empty pages
	#4350: Capture current Crossref deposit status before update.
	#4366: Check article publication status in DOI/Crossref Export plugins

