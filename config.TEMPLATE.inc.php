; <?php exit(); // DO NOT DELETE ?>
; DO NOT DELETE THE ABOVE LINE!!!
; Doing so will expose this configuration file through your web site!
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;
; config.TEMPLATE.inc.php
;
; Copyright (c) 2003-2004 The Public Knowledge Project
; Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
;
; OJS Configuration settings.
; Rename config.TEMPLATE.inc.php to config.inc.php to use.
;
; $Id$
;
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;


;;;;;;;;;;;;;;;;;;;;
; General Settings ;
;;;;;;;;;;;;;;;;;;;;

[general]

; Set this to true once the system has been installed
; (This is generally done automatically by the installer)
installed = false

; Complete path to directory to store uploaded files
; (This directory should not be directly web-accessible)
files_dir = /home/journal/journal_files

; Number of days to save login cookie for if user selects to remember
; (set to 0 to force expiration at end of current session)
session_lifetime = 30

; Short and long date formats
date_format_short = "%m/%d/%Y"
date_format_long = "%B %e, %Y"
datetime_format_short = "%m/%d/%Y %I:%M %p"
datetime_format_long = "%B %e, %Y - %I:%M %p"


;;;;;;;;;;;;;;;;;;;;;
; Database Settings ;
;;;;;;;;;;;;;;;;;;;;;

[database]

driver = mysql
host = localhost
username = ojs
password = ojs
name = ojs
pconnect = true		; Enable persistent connections (recommended)
debug = false		; Enable database debug output (very verbose!)


;;;;;;;;;;;;;;;;;;;;;;;;;
; Localization Settings ;
;;;;;;;;;;;;;;;;;;;;;;;;;

[i18n]

; Default locale
locale = en_US


;;;;;;;;;;;;;;;;;;;;;
; Security Settings ;
;;;;;;;;;;;;;;;;;;;;;

[security]

; Force SSL connections site-wide
force_ssl = false

; Force SSL connections for logins only
force_login_ssl = false

; This check will invalidate a session if the user's IP address changes.
; Enabling this option provides some amount of additional security, but may
; cause problems for users behind a proxy farm (e.g., AOL).
session_check_ip = true

; The encryption (hashing) algorithm to use for encrypting user passwords
; Valid values are: md5, sha1
; Note that sha1 requires PHP >= 4.3.0
encryption = md5
