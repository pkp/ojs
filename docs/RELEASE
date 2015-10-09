OJS 2.4.7 Release Notes
GIT tag: ojs-2_4_7-0
Release date: October 9, 2015
================================

Note: While we transition our issue management system from Bugzilla
(http://pkp.sfu.ca/bugzilla) to Github Issues
(http://www.github.com/pkp/pkp-lib/issues), this list will contain a mixture
of IDs from both systems. Bugzilla entries will be prefixed with "bz" and
Github issues will be prefixed with "i".

Configuration Changes
---------------------

New config.inc.php parameters:
	- security:salt
	- security:reset_seconds

New Features
------------
	i807: Add iParadigms functionality to CrossRef plugin
	i380: Implement beacon
	i427: Add bulk editing tools for email templates
	i685: Data Privacy Option for Statistic Plugin
	i741: Make Google Analytics available at the Site level
	i768: Enable webservice support for PUT

Bug Fixes
---------
	i274: Counter report includes hard-coded English
	i336: COMPONENT_ROUTER_PATHINFO_MARKER request raises error in Request::getRequestedPage()
	i347: Prep OJS for automated releases
	i384: Issue cover settings are too locale specific
	i391: Add DOI to reference export formats
	i393: Fatal error calling DataObject::getLocalizedData() by reference if only non-locale specific data exists.
	i417: getPublishedArticleIdsByJournal() can return unpublished objects
	i428: Untranslated locale key in comment for authors emailing comments
	i429: Announcements don't respect date_posted ("published") setting
	i437: Strict ValidatorInSet type checking breaks subscription type creation
	i443: Exclude disabled users from mass mailouts
	i447: Improve WCAG / Section 508 compliance
	i448: Reader fees not included in About
	i449: Javascripts blocked when OJS is served through HTTPS
	i451: Translate ALM plugin to Brazilian portuguese
	i452: ReferralDAO may query insert ID without insert
	i460: Stats can't process Issues with unique identifiers
	i464: Error on favicon upload, when determining the favicon type
	i465: Empty copyright holder
	i472: Consider other license URL on the article page
	i482: Articles from journals not intented for public display appear in search results
	i486: OJS native import/export DTD: copyright_holder is multilingual
	i496: Broken links on Editorial Policies page
	i497: Unexpected behavior using "+" operator to append items to arrays in subclasses
	i499: Call superclass when overriding methods in Form and DAO subclasses
	i509: Public galley ID validation
	i511: Payment description HTML support inconsistent
	i521: Author signature appended to notification email when an author uploads a revision
	i522: Link to current issue does not redirect to public id
	i523: PDF not shown due to jQuery conflict when ALM plugin is activated
	i526: Backport ContextDAO::getBySetting() to JournalDAO
	i529: ISSN Validator doesn't handle 0 or X checkdigits
	i534: Default theme css is overwriting default OJS general styles
	i535: View report fails if stats refer to an object that's not available anymore
	i536: Already Paid option missing for manual payment if waiver text not set
	i540: Make search queries use GET parameters
	i546: Translate plugin creates new files with default translations
	i551: Months with 4 or less letters in ABNT citations are handled incorrectly
	i552: Getting stats for all articles slows down access if used in a high traffic site
	i557: PostgreSQL sequence re-creation on upgrade
	i559: additional article author metadata
	i560: fix for the single step exclusion of URNs
	i563: Updates/corrections needed to ALM Plugin
	i571: Avoid sending duplicate emails to multiple recipients
	i574: Notification subscription (mailing list) source is confusing
	i577: Display galley pubIds on abstract and indexing metadata page
	i578: Add missing int casts to IssueDAO
	i588: Sorting Archives list by status as Section Editor leads to DB error
	i589: Problem with pagination in DOAJ plugin
	i590: Get localized journal description
	i634: Update PubMED XML export to include affiliation info, keywords
	i653: Native import causes fatal error during Issue cleanup after failure
	i680: site.tpl nest at least two <p> in journal description
	i682: Set cookie request method is using the wrong cookie path sometimes
	i690: force_login_ssl setting should not allow HTTPS form on HTTP page
	i691: Empty "Edit Profile Form" when Administrator that is not a Journal Manager edit an user
	i692: Cancelled reviews are counted as completed ones in reviewer stats
	i695: Improve password hashes and reset
	i702: Switching form language while registering as a new user doesn't work
	i705: Payments records are not moved to target user when merging
	i712: Crossref plugin scheduled task is not using the correct task interface
	i715: Tweak author disambiguation scheme
	i720: Submission metadata omit authors setting is not working when notifying users and including TOC
	i721: Tiny MCE corrupts DOAJ XML
	i722: Prevent email sending interaction with acron plugin
	i724: OAI requests for nonexistent journals result in site-wide OAI URL behavior
	i729: assign an id to the objects for review nav item
	i730: Automatic DOI registration to DataCite
	i731: missing unregistered articles URL in crossref plugin settings
	i732: consider the expire parameter for the cookies
	i734: Usage stats loader task doesn't warn about not being able to move files
	i737: "Patch" upgrading should not be a recommended path
	i745: Passwords should not be emailed
	i761: Position footer hooks consistently
	i764: Duplicate missing keys in translator plugin
	i766: Remove "from" display name use from forced envelope sender setting
	i771: Webservice requests need a bit more error checking
	i774: Clear div necessary in the object for review details view
	i784: Support replacing missing translation with the english text
	i794: XML Writer could support Comments
	i798: DOIExportDom fails to return cached objects
	i799: DataCite and CrossRef scheduled tasks error e-mails

