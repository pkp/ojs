OJS 2.3.0 Release Notes
CVS tag: ojs-2_3_0-0
Release date: Oct 7, 2009
=================================

OJS 2.3 introduces a major rewrite of core aspects of PKP applications that
reconciles common code (e.g. shared between OJS, OCS, and the Harvester) into
a separate library called the PKP Web Application Library (WAL). Many parts of
the system have been changed in a way that is transparent to users but that
will vastly improve maintainability and the ease with which PKP can deploy
fixes and new features across multiple applications. Wherever possible, this
has been done in a way that minimizes code breakage e.g. for modified installs
of OJS and custom plugins.

Configuration Changes
---------------------

See config.TEMPLATE.inc.php for a description and examples of all supported
configuration parameters.

New config.inc.php parameters:
	- general:time_format - Allows specification of the time format to use
		(see strftime in the PHP documentation for details)
	- general:restful_urls - Generate RESTful URLs using mod_rewrite. See
		FAQ for details.
	- general:xslt_command: For automatic generation of XHTML output (e.g.
		using the XML galleys plugin), use the specified tool to
		perform the transformation. See config.TEMPLATE.inc.php for
		details.
	- email:display_errors has been moved to debug:display_errors.

New Features
------------
	- #3608# #3694# #3703# #3704# #3941# #3818# #3706# #4155# #4156#
	  #4185# #4547# Implement / integrate PKP Web Application Library (WAL)
	- #3696# Move TinyMCE cache (gzipped JavaScript) into cache dir
	- #3697# Add caching to TimeZone class
	- #3754# Add ArrayItemIterator::fromRangeInfo method
	- #3874# #3782# Revamp OAI metadata format support
	- #3932# Remove precompiled templates from distribution
	- #3935# Add site settings install support
	- #3944# #3693# Split up schema descriptor
	- #4019# Add abstract "controlled vocabulary" support
	- #3700# Allow for image uploads with TinyMCE
	- #3917# Integrate jQuery into codebase
	- #4005# Enable mod_rewrite support and RESTful URLs
	- #3695# Abstract Document/Submission functionality
	- #3745# Add plugin install / management tool
	- #3753# Add ibrowser support for TinyMCE image uploads
	- #3781# Add 'product' attribute to version tables
	- #3807# Integrate phputf8 library
	- #3850# Use PHP's set_error_handler() to present errors more cleanly
	- #3928# Add markup to templates to signify sections and subsections
	- #1442# HTML front-end for OAI metadata
	- #1444# Split up localization file
	- #4099# Add abstract sign-off implementation
	- #4396# Add visual indication of language status (complete/incomplete)
	- #4530# Include "System Offline page"
	- #4205# Overhaul of Handler Validation and Announcement Abstraction
	- #4211# Add XSLT class and settings into WAL
	- #4219# Integrate PHP Quick Profiler for debug logging
	- #3284# Tag-cloud of submission keywords
	- #3543# Photos for Masthead
	- #3871# Add referral tracking plugin
	- #3889# Link error messages to errors
	- #3895# Change "Archive Submission" to "Reject and Archive Submission"
	- #3898# Break templates/article/article.tpl into smaller pieces
	- #3908# Enrollment search needs "starts with" option
	- #3923# add nl_NL locale files
	- #3712# Custom Sidebar Block Plugin
	- #3683# Add TinyMCE support to reviewer comments
	- #3797# Add indications next to User Roles in User Home
	- #3907# Replace inline anchor names with <div id="xx">
	- #3958# Make submission lists sortable
	- #3063# Wording changes for Subscription Types
	- #3652# Merge users should selectively transfer subscription
	- #3900# User list for sub managers should see only journal's users
	- #4170# Enable users to purchase subscriptions
	- #4172# Enable users to renew an existing subscription
	- #4173# Provide Subscription Manager access to payment module
	- #4174# Extend subscription block plugin
	- #3701# Allow for short Announcements
	- #3910# Include social networking features in RT or article view
	- #3922# Allow for opt-in/out status updates/notifications
	- #3984# Update PKP logo in LOCKSS Publisher Manifest page
	- #3993# Upgrade COUNTER protocol to support Release 3
	- #4010# Allow lists to be rearranged by drag and drop
	- #1690# Remove OJS 1.1.x import support
	- #1709# For users who won't be using OJS to publish journal contents
	- #1963# Use of two document icons to indicate when blank vs. not
	- #2292# SubmissionEditHandler::rateReviewer() expects list
	- #1848# W3C Validator complaints
	- #1856# Disable user creation/enrollment for disabled roles
	- #1860# Streamline access for users with a single role
	- #2052# example wording of path
	- #4230# Subscription non-expiry option
	- #4630# SFU links not "linked" properly
	- #4104# Streamline Journal Section selection for one-section journals
	- #4154# Principal Contact fields not available in multilingual form
	- #4217# default paymethod to manual (backport of bug 3825)
	- #4231# Port OCS Reviews plugin to OJS
	- #4242# Allow for registering previous journal names
	- #4274# Display user's salutation where appropriate
	- #4395# Reconcile metadata edit/view templates
	- #4406# Add TinyMCE to comment forms
	- #4438# update instructions in webfeed plugin locale.xml
	- #4520# DOI suffix setup fields (page 1) need proper IDs for labels
	- #4755# General review of english messages
	- #4161# Add notification options to post-install message
	- #4189# Update Lockss URLS
	- #4502# Add sv_SV (Swedish)
	- #4149# Implement abstracted sign-off functionality in applications
	- #4244# Request for further TinyMCE features in limited cases
	- #4591# After creating an issue, go to that issue
	- #4120# Re-order author submission steps
	- #4164# Update submission wording to include OpenOffice
	- #4147# Setup Step 3.5 Registration for Metadata Harvesting
	- #4220# Allow JMs to upload favicons
	- #4450# Automatically scroll to authors on add/delete/reorder
	- #4536# Standardize on sorting code parameters
	- #4538# step 5 alt text for images
	- #4544# Thesis abstract issues
	- #4549# Add SUSHI support
	- #4733# Add Basque locale files

Bug Fixes
---------
	- #3912# Request::getServerHost includes port number
	- #3989# Allow numeric domain names in email validation
	- #4020# Installation and upgrade errors
	- #1816# Smarty string manipulation functions are not multibyte-safe
	- #1812# Numeric IDs read from user input should be cast to integers
	- #3881# SQL for postgres uses an incorrect single quote escape syntax
	- #4032# Disable language block pulldown in case of POST request
	- #4039# Fix breadcrumb / POST form URL problems
	- #4069# Grep and fix for unquoted attributes in templates
	- #4398# Check & correct pass by reference of undeclared variables
	- #4531# Responses for non-existent pages/ops should result in 404s
	- #4610# xml:lang attributes are not RFC4646 compliant
	- #4764# Cache / t_compile permission problems should lead to err msgs
	- #4772# Review & correct problems in the warning log
	- #4108# Fix warning on mbstring check
	- #4209# PHP 4.x/5.x compatibility fixes
	- #4226# PHP4 Pass-by-reference errors
	- #4463# Update RFC2822 compliance for email processing
	- #4509# Announcement breadcrumb issues
	- #3698# Reply to reviewer comments should quote parent title + 'Re:'
	- #3965# No easy way to 'unpublish' an issue; Issue Status misbehaves
	- #3897# rt.tpl has incorrect <i tag
	- #3902# Creative Commons licence needs to be fixed
	- #3916# issues with institutional subscriptions
	- #4117# Subscription IP range field too small
	- #4667# Misc. subscription upgrade issues
	- #4683# Some PayPal payments fail to complete
	- #3657# Bibtex citation export shows html elements in abstract
	- #3752# HTML tags in Issue Title field only work in certain cases
	- #4553# galley file information not coming up on upload
	- #2280# Reconcile index.php/index/user and index.php/journal_path/user
	- #4425# UTF-8 support for xmlschema got lost on ADOdb update
	- #4454# same submission listed multiple times in reviewer home
	- #4153# translation plugin needs to pull up localized data.xml files
	- #4196# there is no way of setting issue data to ISSUE_DEFAULT
	- #4216# Re-ordering back issues across pages
	- #4443# Review Confirmation email doesn't populate To: field
	- #4568# OJS install/Paypal error
	- #4588# Bio Statement field in Articles Report has XML displaying
	- #4606# Missing labels in HTML form
	- #4648# Some labels are missing after cancelling PayPal payment
	- #4664# Announcements upgrade issues
	- #4665# New article status icons incompletely updated
	- #4708# Extend Static Pages content to longtext
	- #4766# Articles report 'status' value not being set
	- #4560# fatal error on viewing XML galley
	- #4561# disabling delayed open access on an issue is not restricting
	- #4608# Subscription form missing gender options
	- #4636# Subscription Manager's Create New User issues
	- #4643# Subscriptions that shouldn't be publicly available show up
	- #4666# Article navigation bar links open in frame
	- #4098# Remove unused column "status" from article_files / paper_files
	- #4076# Password reset emails may come from wrong sender
	- #4419# Locale resubmit on article submission redirects to wrong page
	- #4467# editorDecisionOptions defined multiple times
	- #4506# Site Settings display issues
	- #4513# Issues with Custom Locale plugin
	- #4528# Double slashes in BlockPlugin.inc.php
	- #4534# Submission instruction issues
	- #4550# COUNTER plugin - verify source of views before adding to count
	- #4554# Cannot re-order some sections
	- #4567# Some email templates aren't displayed in translator plugin
	- #4573# Multiple page sorting issues
	- #4581# Native Export fails after clicking 'select all'
	- #4615# Subscription -> Create new user bugs
	- #4618# Review forms no working when using more than 1 language
	- #4619# Delayed open access ambiguity
	- #4646# Search index rebuild fails in PostgreSQL
	- #4671# Different icons used for same task in different places
	- #4690# Static Pages plugin breaks links
	- #4731# Theme tune-ups
	- #4749# Make affiliation field consistent for all forms
	- #4754# Notification block plugin creates empty div at site user home
