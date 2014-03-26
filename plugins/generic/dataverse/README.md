# OJS Dataverse Plugin

The [Dataverse Network Project](http://thedata.org/) and the [Public Knowledge Project](http://pkp.sfu.ca/)  are
 partnering to develop plugin that adds data sharing and preservation to the [Open Journal Systems](http://pkp.sfu.ca/ojs/)
 publication process. For more information about the project, visit http://projects.iq.harvard.edu/ojs-dvn/about-project.

## Dataverse Plugin Guide

Refer to the [Dataverse Plugin Guide](https://docs.google.com/document/d/1QgxtxMaWdSZ8gI3wHDkE5EfP4W3M2Za-4DhmX_x3pY0/edit?disco=AAAAAGd77n8#) 
for an overview of data publication workflows supported by the plugin in OJS.

## Installing the plugin

### System requirements

The Dataverse plugin uses the [SWORD v2 PHP API library](https://github.com/swordapp/swordappv2-php-library/), which requires 
** PHP version 5 ** and the following extensions:

+ cURL
+ SimpleXML
+ Zip

### OJS 2.4.4

The Dataverse plugin is included in OJS 2.4.4. Enable the plugin as usual and configure settings according to the [Dataverse Plugin Guide](https://docs.google.com/document/d/1QgxtxMaWdSZ8gI3wHDkE5EfP4W3M2Za-4DhmX_x3pY0/edit?disco=AAAAAGd77n8#).

### OJS 2.4.3

Download [dataverse-1.1.1.0.tar.gz](https://github.com/jwhitney/dataverse/releases). Use the web plugin installer 
in the journal management pages to install the plugin: click "System Plugins", then "Install a New Plugin" to 
upload the downloaded *.tar.gz file.

**Before enabling & configuring the plugin,** please run the following commands to patch the swordappv2 library
included in OJS 2.4.3. 

From the OJS install directory, test the patch with: 

`patch --dry-run -d lib/pkp/lib/swordappv2 < plugins/generic/dataverse/swordappv2.diff`

If the dry run indicates the patch will apply cleanly, run:

`patch -d lib/pkp/lib/swordappv2 < plugins/generic/dataverse/swordappv2.diff`

Enable and configure the plugin as described in the [guide](https://docs.google.com/document/d/1QgxtxMaWdSZ8gI3wHDkE5EfP4W3M2Za-4DhmX_x3pY0/edit?disco=AAAAAGd77n8#). 

### OJS 2.4.2 & earlier

Download [dataverse-1.0.2.0.tar.gz](https://github.com/jwhitney/dataverse/releases)

If the SWORD plugin is present in your OJS install, at `plugins/generic/sword`, remove it.
In OJS versions 2.4.2 and earlier, the SWORD plugin uses an incompatible version of the swordapp PHP library and **the Dataverse plugin can't be
installed unless it's removed**. 

#### OJS 2.4.0, 2.4.1, 2.4.2

Use the web plugin installer available from the journal management pages: click "System Plugins," 
then "Install a New Plugin" to upload the downloaded *.tar.gz file. 

**Please make sure the web server is able to write to the `plugins` and `lib/pkp/plugins` directories (including subdirectories).** 
Don't forget to secure the directories again after installing the plugin. 

PHP's `upload_max_filesize` and `post_max_size` settings must be large enough to allow the plugin source (about 2.3M) to be uploaded. 

After installation, go to "System Plugins" then "Generic Plugins" to enable and configure the 
Dataverse plugin as described in the [guide](https://docs.google.com/document/d/1QgxtxMaWdSZ8gI3wHDkE5EfP4W3M2Za-4DhmX_x3pY0/edit?disco=AAAAAGd77n8#).

#### OJS 2.3

* Unzip the source files and move the `dataverse` directory to `plugins/generic`. 
* Move the SWORD library files from `plugins/generic/dataverse/lib/swordappv2` to 'lib/pkp/plugins/generic/dataverse/swordappv2`
* From the OJS install directory, run `php tools/dbXMLtoSQL.php -schema execute plugins/generic/dataverse/schema.xml` 
to install database tables used by the plugin.
* Enable and configure the plugin as described in the [guide](https://docs.google.com/document/d/1QgxtxMaWdSZ8gI3wHDkE5EfP4W3M2Za-4DhmX_x3pY0/edit?disco=AAAAAGd77n8#). 

