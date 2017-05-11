[database]
driver = mysql
host = localhost
username = ojs
password = ojs
name = ojs

persistent = On
debug = Off

[general]
installed = On
base_url = "http://pkp.sfu.ca/ojs"
session_cookie_name = OJSSID
session_lifetime = 30
scheduled_tasks = Off

date_format_trunc = "%m-%d"
date_format_short = "%Y-%m-%d"
date_format_long = "%B %e, %Y"
datetime_format_short = "%Y-%m-%d %I:%M %p"
datetime_format_long = "%B %e, %Y - %I:%M %p"

disable_path_info = Off

; base_url[index] = http://www.myUrl.com
; base_url[myJournal] = http://www.myUrl.com/myJournal
; base_url[myOtherJournal] = http://myOtherJournal.myUrl.com

[cache]
cache = file
memcache_hostname = localhost
memcache_port = 11211
web_cache = Off
web_cache_hours = 1

[i18n]
locale = en_US
client_charset = utf-8
connection_charset = utf8
database_charset = utf8
charset_normalization = On

[files]
files_dir = files
public_files_dir = public
umask = 0022

[finfo]
mime_database_path = /etc/magic.mime

[security]
force_ssl = Off
force_login_ssl = Off
session_check_ip = On
encryption = md5
allowed_html = "<a> <em> <strong> <cite> <code> <ul> <ol> <li> <dl> <dt> <dd> <b> <i> <u> <img> <sup> <sub> <br> <p>"
;implicit_auth = On
;implicit_auth_header_first_name = HTTP_TDL_GIVENNAME
;implicit_auth_header_last_name = HTTP_TDL_SN
;implicit_auth_header_email = HTTP_TDL_MAIL
;implicit_auth_header_phone = HTTP_TDL_TELEPHONENUMBER
;implicit_auth_header_initials = HTTP_TDL_METADATA_INITIALS
;implicit_auth_header_mailing_address = HTTP_TDL_METADATA_TDLHOMEPOSTALADDRESS
;implicit_auth_header_uin = HTTP_TDL_TDLUID
;implicit_auth_admin_list = "100000040@tdl.org 85B7FA892DAA90F7@utexas.edu 100000012@tdl.org"
;implicit_auth_wayf_url = "/Shibboleth.sso/wayf"

[email]
; smtp = On
; smtp_server = mail.example.com
; smtp_port = 25
; smtp_auth = PLAIN
; smtp_username = username
; smtp_password = password
; allow_envelope_sender = Off
; default_envelope_sender = my_address@my_host.com
time_between_emails = 3600
max_recipients = 10
require_validation = Off
validation_timeout = 14
display_errors = On

[search]
min_word_length = 3
results_per_keyword = 500
result_cache_hours = 1
; index[application/pdf] = "/usr/bin/pstotext %s"
; index[application/pdf] = "/usr/bin/pdftotext %s -"
; index[application/postscript] = "/usr/bin/pstotext %s"
; index[application/postscript] = "/usr/bin/ps2ascii %s"
; index[application/msword] = "/usr/bin/antiword %s"
; index[application/msword] = "/usr/bin/catdoc %s"

[oai]
oai = On
repository_id = ojs.pkp.sfu.ca

[interface]
items_per_page = 25
page_links = 10

[captcha]
captcha = off
captcha_on_register = on
captcha_on_comments = on
font_location = /usr/share/fonts/truetype/freefont/FreeSerif.ttf

[proxy]
; http_host = localhost
; http_port = 80
; proxy_username = username
; proxy_password = password

[debug]
show_stacktrace = On
