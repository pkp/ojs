This plug-in is a wrapper around the solr search server for OJS. It requires a working solr server somewhere on your network.

If you have a single OJS installation or if you whish to run one solr server per OJS installation then you can use the configuration for an embedded solr instance that comes with this plug-in.

As we do not want to unnecessarily blow up our default OJS distribution and want to make sure that you always install the latest release of solr, we do not distribute the solr Java binaries with this plug-in. You'll have to download and install them before you can use the plug-in. The following paragraphs will explain how to do this to transform your OJS server into a solr search server.

1) Make sure your server meets the necessary installation requirements to install solr:
- Operating System: Any operating system that supports J2SE 1.5 or greater (this includes all recent versions of Linux and Windows).
- Disk Space: The disk your OJS installation resides on should have at least 150 MB of free disk space. The disk your "files" directory resides on, should have enough free disk space to accommodate the search index created by solr. This is at least the double of the space occupied by all your galleys and supplementary files in that same folder.
- RAM: Memory requirements depend a lot on the size of your journal. If you have several GB of article galley files and you want best search performance then you'll need a few GB of RAM for the solr server and for the operating system's file cache, too. Smaller installations require much less memory, though. Try starting the embedded server with default settings and only get back to it if you experience performance problems. In most cases, default settings will probably work for you.
- PHP configuration: The minimum PHP version for this plug-in is 5.0.0. The plug-in relies on the PHP curl extension. Please activate it before enabling the plug-in.

2) IMPORTANT - Secure your server:
While we tried to make sure that our solr configuration be secure by default, solr has NOT been designed to be directly exposed to the internet. Please make sure that you have a firewall in place that denies public access to IP port 8983. If for some reason you do not have a firewall in place right now, then make sure you change the default solr admin password immediately:
- Edit plugins/generic/lucene/embedded/etc/realm.properties
- Change the line "admin: ojsojs,content_updater,admin" to "admin: xxxxxxx,content_updater,admin" where xxxxxxx is to be replaced with a password you choose.

3) Install Java:
You'll need a working installation of the Java 2 Platform, Standard Edition (J2SE) Runtime Environment, Version 1.5 or higher. If you are on Linux then install a J2SE compliant Java package. If you are on Windows you may get the latest J2SE version from http://java.com/en/download/index.jsp.

4) Download the Jetty and solr binaries:
- Jetty: Get the latest Jetty 6 binary from http://dist.codehaus.org/jetty/ and unzip it into plugins/generic/lucene/lib in your OJS application directory.

If you are on Linux this would be something like:
     #> cd plugins/generic/lucene/lib
     #> wget http://dist.codehaus.org/jetty/jetty-6.1.26/jetty-6.1.26.zip
     #> unzip jetty-6.1.26.zip

(You may have to install the unzip tool first...)

This should create a folder jetty-6.1.26 in your lib directory. If you are on Linux then please create a symlink pointing to this directory:
     #> ln -s jetty-6.1.26.zip jetty

If you are on Windows then download and unzip the file to the lib folder using your favorite browser and zip tool. Then rename the jetty folder to "jetty".

- solr: Get the latest solr binary from an Apache download mirror and unzip it into plugins/generic/lucene/lib in your OJS application directory.

If you are on Linux this would be something like:
     #> cd plugins/generic/lucene/lib
     #> wget http://www.eu.apache.org/dist/lucene/solr/3.5.0/apache-solr-3.5.0.zip
     #> unzip apache-solr-3.5.0.zip

This should create a folder apache-solr-3.5.0 in your lib directory. If you are on Linux then please create a symlink pointing to this directory:
     #> ln -s apache-solr-3.5.0 solr

On Windows download and unzip the file to the lib folder. Then rename the solr folder to "solr".

5) Execute the installation script plugins/generic/lucene/embedded/bin/install.sh (on Linux) or plugins/generic/lucene/embedded/bin/install.bat (on Windows) to assemble your embedded solr server from the binary files you just downloaded:

On Linux:
     #> cd plugins/generic/lucene/embedded/bin
     #> ./install.sh

On Windows: Go to the bin directory in the Explorer. Then double-click install.bat.


TODO:
- Automate solr/jetty download with an appropriate script.
