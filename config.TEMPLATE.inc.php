; <?php exit(); // DO NOT DELETE ?>
; DO NOT DELETE THE ABOVE LINE!!!
; Doing so will expose this configuration file through your web site!
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;
; config.TEMPLATE.inc.php
;
; Copyright (c) 2014-2024 Simon Fraser University
; Copyright (c) 2003-2024 John Willinsky
; Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
;
; OJS Configuration settings.
; Rename config.TEMPLATE.inc.php to config.inc.php to use.
;
;
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;


;;;;;;;;;;;;;;;;;;;;
; General Settings ;
;;;;;;;;;;;;;;;;;;;;

[general]

; Set this to On once the system has been installed
; (This is generally done automatically by the installer)
installed = Off

; The canonical URL to the OJS installation (excluding the trailing slash)
base_url = "http://pkp.sfu.ca/ojs"

; Session cookie name
session_cookie_name = OJSSID

; Session cookie path; if not specified, defaults to the detected base path
; session_cookie_path = /

; Number of days to save login cookie for if user selects to remember
; (set to 0 to force expiration at end of current session)
session_lifetime = 30

; SameSite configuration for the cookie, see possible values and explanations
; at https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie/SameSite
; To set the "Secure" attribute for the cookie see the setting force_ssl at the [security] group
session_samesite = Lax

; Enable support for running scheduled tasks
; Set this to On if you have set up the scheduled tasks script to
; execute periodically
scheduled_tasks = Off

; Scheduled tasks will send email about processing
; only in case of errors. Set to off to receive
; all other kind of notification, including success,
; warnings and notices.
scheduled_tasks_report_error_only = On

; Site time zone
; Please refer to lib/pkp/registry/timeZones.xml for a full list of supported
; time zones.
; I.e.:
; <entry key="Europe/Amsterdam" name="Amsterdam" />
; time_zone="Amsterdam"
time_zone = "UTC"

; Short and long date formats
date_format_short = "%Y-%m-%d"
date_format_long = "%B %e, %Y"
datetime_format_short = "%Y-%m-%d %I:%M %p"
datetime_format_long = "%B %e, %Y - %I:%M %p"
time_format = "%I:%M %p"

; Use URL parameters instead of CGI PATH_INFO. This is useful for broken server
; setups that don't support the PATH_INFO environment variable.
; WARNING: This option is DEPRECATED and will be removed in the future.
disable_path_info = Off

; Use fopen(...) for URL-based reads. Modern versions of dspace
; will not accept requests using fopen, as it does not provide a
; User Agent, so this option is disabled by default. If this feature
; is disabled by PHP's configuration, this setting will be ignored.
allow_url_fopen = Off

; Base URL override settings: Entries like the following examples can
; be used to override the base URLs used by OJS. If you want to use a
; proxy to rewrite URLs to OJS, configure your proxy's URL here.
; Syntax: base_url[journal_path] = http://www.myUrl.com
; To override URLs that aren't part of a particular journal, use a
; journal_path of "index".
; Examples:
; base_url[index] = http://www.myUrl.com
; base_url[myJournal] = http://www.myUrl.com/myJournal
; base_url[myOtherJournal] = http://myOtherJournal.myUrl.com

; Generate RESTful URLs using mod_rewrite. This requires the
; rewrite directive to be enabled in your .htaccess or httpd.conf.
; See FAQ for more details.
restful_urls = Off

; Restrict the list of allowed hosts to prevent HOST header injection.
; See docs/README.md for more details. The list should be JSON-formatted.
; An empty string indicates that all hosts should be trusted (not recommended!)
; Example:
; allowed_hosts = '["myjournal.tld", "anotherjournal.tld", "mylibrary.tld"]'
allowed_hosts = ''

; Allow the X_FORWARDED_FOR header to override the REMOTE_ADDR as the source IP
; Set this to "On" if you are behind a reverse proxy and you control the
; X_FORWARDED_FOR header.
; Warning: This defaults to "On" if unset for backwards compatibility.
trust_x_forwarded_for = Off

; Display a message on the site admin and journal manager user home pages if there is an upgrade available
show_upgrade_warning = On

; Set the following parameter to off if you want to work with the uncompiled (non-minified) JavaScript
; source for debugging or if you are working off a development branch without compiled JavaScript.
enable_minified = On

; Provide a unique site ID and OAI base URL to PKP for statistics and security
; alert purposes only.
enable_beacon = On

; Set this to "On" if you would like to only have a single, site-wide Privacy
; Statement, rather than a separate Privacy Statement for each journal. Setting
; this to "Off" will allow you to enter a site-wide Privacy Statement as well
; as separate Privacy Statements for each journal.
sitewide_privacy_statement = Off


;;;;;;;;;;;;;;;;;;;;;
; Database Settings ;
;;;;;;;;;;;;;;;;;;;;;

[database]

driver = mysqli
host = localhost
username = ojs
password = ojs
name = ojs

; Set the non-standard port and/or socket, if used
; port = 3306
; unix_socket = /var/run/mysqld/mysqld.sock

; Database collation
; collation = utf8_general_ci

; Enable database debug output (very verbose!)
debug = Off

;;;;;;;;;;;;;;;;;;
; Cache Settings ;
;;;;;;;;;;;;;;;;;;

[cache]

; Choose the type of object data caching to use. Options are:
; - memcache: Use the memcache server configured below
; - xcache: Use the xcache variable store
; - apc: Use the APC variable store
; - none: Use no caching.
object_cache = none

; Enable memcache support
memcache_hostname = localhost
memcache_port = 11211

; For site visitors who are not logged in, many pages are often entirely
; static (e.g. About, the home page, etc). If the option below is enabled,
; these pages will be cached in local flat files for the number of hours
; specified in the web_cache_hours option. This will cut down on server
; overhead for many requests, but should be used with caution because:
; 1) Things like journal metadata changes will not be reflected in cached
;    data until the cache expires or is cleared, and
; 2) This caching WILL NOT RESPECT DOMAIN-BASED SUBSCRIPTIONS.
; However, for situations like hosting high-volume open access journals, it's
; an easy way of decreasing server load.
;
; When using web_cache, configure a tool to periodically clear out cache files
; such as CRON. For example, configure it to run the following command:
; find .../ojs/cache -maxdepth 1 -name wc-\*.html -mtime +1 -exec rm "{}" ";"
web_cache = Off
web_cache_hours = 1


;;;;;;;;;;;;;;;;;;;;;;;;;
; Localization Settings ;
;;;;;;;;;;;;;;;;;;;;;;;;;

[i18n]

; Default locale
locale = en_US

; Client output/input character set
client_charset = utf-8

; Database connection character set
connection_charset = utf8


;;;;;;;;;;;;;;;;;
; File Settings ;
;;;;;;;;;;;;;;;;;

[files]

; Complete path to directory to store uploaded files
; (This directory should not be directly web-accessible)
; Windows users should use forward slashes
files_dir = files

; Path to the directory to store public uploaded files
; (This directory should be web-accessible and the specified path
; should be relative to the base OJS directory)
; Windows users should use forward slashes
public_files_dir = public

; The maximum allowed size in kilobytes of each user's public files
; directory. This is where user's can upload images through the
; tinymce editor to their bio. Editors can upload images for
; some of the settings.
; Set this to 0 to disallow such uploads.
public_user_dir_size = 5000

; Permissions mask for created files and directories
umask = 0022


;;;;;;;;;;;;;;;;;;;;;;;;;;;;
; Fileinfo (MIME) Settings ;
;;;;;;;;;;;;;;;;;;;;;;;;;;;;

[finfo]
; mime_database_path = /etc/magic.mime


;;;;;;;;;;;;;;;;;;;;;
; Security Settings ;
;;;;;;;;;;;;;;;;;;;;;

[security]

; Force SSL connections site-wide and also sets the "Secure" flag for session cookies
; See https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#secure
force_ssl = Off

; Force SSL connections for login only
force_login_ssl = Off

; This check will invalidate a session if the user's IP address changes.
; Enabling this option provides some additional security, but may cause
; login problems for some users (e.g. if a user IP is changed frequently
; by a server or network configuration).
session_check_ip = On

; The encryption (hashing) algorithm to use for encrypting user passwords
; Valid values are: md5, sha1
; NOTE: This hashing method is deprecated, but necessary to permit gradual
; migration of old password hashes.
encryption = sha1

; The unique salt to use for generating password reset hashes
salt = "YouMustSetASecretKeyHere!!"

; The unique secret used for encoding and decoding API keys
api_key_secret = ""

; The number of seconds before a password reset hash expires (defaults to 7200 / 2 hours)
reset_seconds = 7200

; Allowed HTML tags for fields that permit restricted HTML.
; Use e.g. "img[src,alt],p" to allow "src" and "alt" attributes to the "img"
; tag, and also to permit the "p" paragraph tag. Unspecified attributes will be
; stripped.
allowed_html = "a[href|target|title],em,strong,cite,code,ul,ol,li[class],dl,dt,dd,b,i,u,img[src|alt],sup,sub,br,p"

;Is implicit authentication enabled or not
;implicit_auth = On


;;;;;;;;;;;;;;;;;;
; Email Settings ;
;;;;;;;;;;;;;;;;;;

[email]

; Use SMTP for sending mail instead of mail()
; smtp = On

; SMTP server settings
; smtp_server = mail.example.com
; smtp_port = 25

; Enable SMTP authentication
; Supported smtp_auth: ssl, tls (see PHPMailer SMTPSecure)
; smtp_auth = ssl
; smtp_username = username
; smtp_password = password
;
; Supported smtp_authtype: RAM-MD5, LOGIN, PLAIN, XOAUTH2 (see PHPMailer AuthType)
; (Leave blank to try them in that order)
; smtp_authtype =

; The following are required for smtp_authtype = XOAUTH2 (e.g. GMail OAuth)
; (See https://github.com/PHPMailer/PHPMailer/wiki/Using-Gmail-with-XOAUTH2)
; smtp_oauth_provider = Google
; smtp_oauth_email =
; smtp_oauth_clientid =
; smtp_oauth_clientsecret =
; smtp_oauth_refreshtoken =

; Enable suppressing verification of SMTP certificate in PHPMailer
; Note: this is not recommended per PHPMailer documentation
; smtp_suppress_cert_check = On

; Allow envelope sender to be specified
; (may not be possible with some server configurations)
; allow_envelope_sender = Off

; Default envelope sender to use if none is specified elsewhere
; default_envelope_sender = my_address@my_host.com

; Force the default envelope sender (if present)
; This is useful if setting up a site-wide no-reply address
; The reply-to field will be set with the reply-to or from address.
; force_default_envelope_sender = Off

; Force a DMARC compliant from header (RFC5322.From)
; If any of your users have email addresses in domains not under your control
; you may need to set this to be compliant with DMARC policies published by
; those 3rd party domains.
; Setting this will move the users address into the reply-to field and the
; from field wil be rewritten with the default_envelope_sender.
; To use this you must set force_default_enveloper_sender = On and
; default_envelope_sender must be set to a valid address in a domain you own.
; force_dmarc_compliant_from = Off

; The display name to use with a DMARC compliant from header
; By default the DMARC compliant from will have an empty name but this can
; be changed by adding a text here.
; You can use '%n' to insert the users name from the original from header
; and '%s' to insert the localized sitename.
; dmarc_compliant_from_displayname = '%n via %s'

; Amount of time required between attempts to send non-editorial emails
; in seconds. This can be used to help prevent email relaying via OJS.
time_between_emails = 3600

; Maximum number of recipients that can be included in a single email
; (either as To:, Cc:, or Bcc: addresses) for a non-privileged user
max_recipients = 10

; If enabled, email addresses must be validated before login is possible.
require_validation = Off

; The number of days a user has to validate their account before their access key expires.
validation_timeout = 14


;;;;;;;;;;;;;;;;;;;
; Search Settings ;
;;;;;;;;;;;;;;;;;;;

[search]

; Minimum indexed word length
min_word_length = 3

; The maximum number of search results fetched per keyword. These results
; are fetched and merged to provide results for searches with several keywords.
results_per_keyword = 500

; Paths to helper programs for indexing non-text files.
; Programs are assumed to output the converted text to stdout, and "%s" is
; replaced by the file argument.
; Note that using full paths to the binaries is recommended.
; Uncomment applicable lines to enable (at most one per file type).
; Additional "index[MIME_TYPE]" lines can be added for any mime type to be
; indexed.

; PDF
; index[application/pdf] = "/usr/bin/pstotext -enc UTF-8 -nopgbrk %s - | /usr/bin/tr '[:cntrl:]' ' '"
; index[application/pdf] = "/usr/bin/pdftotext -enc UTF-8 -nopgbrk %s - | /usr/bin/tr '[:cntrl:]' ' '"

; PostScript
; index[application/postscript] = "/usr/bin/pstotext -enc UTF-8 -nopgbrk %s - | /usr/bin/tr '[:cntrl:]' ' '"
; index[application/postscript] = "/usr/bin/ps2ascii %s | /usr/bin/tr '[:cntrl:]' ' '"

; Microsoft Word
; index[application/msword] = "/usr/bin/antiword %s"
; index[application/msword] = "/usr/bin/catdoc %s"


;;;;;;;;;;;;;;;;
; OAI Settings ;
;;;;;;;;;;;;;;;;

[oai]

; Enable OAI front-end to the site
oai = On

; OAI Repository identifier. This setting forms part of OAI-PMH record IDs.
; Changing this setting may affect existing clients and is not recommended.
repository_id = ojs.pkp.sfu.ca

; Maximum number of records per request to serve via OAI
oai_max_records = 100


;;;;;;;;;;;;;;;;;;;;;;
; Interface Settings ;
;;;;;;;;;;;;;;;;;;;;;;

[interface]

; Number of items to display per page; can be overridden on a per-journal basis
items_per_page = 25

; Number of page links to display; can be overridden on a per-journal basis
page_links = 10


;;;;;;;;;;;;;;;;;;;;
; Captcha Settings ;
;;;;;;;;;;;;;;;;;;;;

[captcha]

; Whether or not to enable ReCaptcha
recaptcha = off

; Public key for reCaptcha (see http://www.google.com/recaptcha)
recaptcha_public_key = your_public_key

; Private key for reCaptcha (see http://www.google.com/recaptcha)
recaptcha_private_key = your_private_key

; Whether or not to use Captcha on user registration
captcha_on_register = on

; Whether or not to use Captcha on user login
captcha_on_login = on

; Whether or not to use Captcha on password reset
captcha_on_password_reset = on

; Validate the hostname in the ReCaptcha response
recaptcha_enforce_hostname = Off

;;;;;;;;;;;;;;;;;;;;;
; External Commands ;
;;;;;;;;;;;;;;;;;;;;;

[cli]

; These are paths to (optional) external binaries used in
; certain plug-ins or advanced program features.

; Using full paths to the binaries is recommended.

; tar (used in backup plugin, translation packaging)
tar = /bin/tar

; On systems that do not have libxsl/xslt libraries installed, or for those who
; require a specific XSLT processor, you may enter the complete path to the
; XSLT renderer tool, with any required arguments. Use %xsl to substitute the
; location of the XSL stylesheet file, and %xml for the location of the XML
; source file; eg:
; /usr/bin/java -jar ~/java/xalan.jar -HTML -IN %xml -XSL %xsl
xslt_command = ""


;;;;;;;;;;;;;;;;;;
; Proxy Settings ;
;;;;;;;;;;;;;;;;;;

[proxy]

; The HTTP proxy configuration to use
; http_proxy = "http://username:password@192.168.1.1:8080"
; https_proxy = "https://username:password@192.168.1.1:8080"


;;;;;;;;;;;;;;;;;;
; Debug Settings ;
;;;;;;;;;;;;;;;;;;

[debug]

; Display a stack trace when a fatal error occurs.
; Note that this may expose private information and should be disabled
; for any production system.
show_stacktrace = Off

; Display an error message when something goes wrong.
display_errors = Off

; Display deprecation warnings
deprecation_warnings = Off

; Log web service request information for debugging
log_web_service_info = Off
