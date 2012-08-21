FEATURE: index administration

SCENARIO: re-index all journals (GUI)
  GIVEN I am on the lucene plugin search page
   WHEN I leave the "all journals" default option of the
        re-indexing section unchanged
    AND I click the "Re-Index" button
   THEN all articles of all journals of the installation will
        be deleted from the index and then re-indexed.

SCENARIO: re-index one journal (GUI)
  GIVEN I am on the lucene plugin search page
   WHEN I select one journal from the journal selector
        in the re-indexing section
    AND I click the "Re-Index" button
   THEN all articles of that journal will be deleted from
        the index and then re-indexed.

SCENARIO: re-index one journal (CLI)
  GIVEN I am on the command line
   WHEN I execute the tools/rebuildSearchIndex.php script
    AND I enter the path of a journal as command line argument
   THEN all articles of that journal will be deleted from
        the index and then re-indexed.

SCENARIO: re-index all journals (CLI)
  GIVEN I am on the command line
   WHEN I execute the tools/rebuildSearchIndex.php script
        without parameters
   THEN all articles of all journals of the installation will
        be deleted from the index and then re-indexed.

SCENARIO: solr process admin button (solr not running)
  GIVEN I am in an environment that allows execution of solr server
        process management shell scripts from within PHP
    AND I configured a solr server endpoint on "localhost"
    AND solr binaries have been installed within the plugin's
        "lib" directory
    AND no solr process is running on the local machine
   WHEN I open up the lucene plugin search settings page
   THEN I'll see a button "Start Server".
   
SCENARIO: start embedded solr server (warning)
  GIVEN I see the button "Start Server" on the lucene plugin
        search settings page
   WHEN I click on this button
   THEN I'll see a confirmation dialog warning me that
        I always have to start the embedded server from
        the same OJS installation if there are several
        installations on the same host.

SCENARIO: start embedded solr server
  GIVEN I see the confirmation dialog warning me about
        starting an embedded solr server from one installation
        only
   WHEN I click on the "OK" button
   THEN a solr server process will be started.

SCENARIO: solr process admin button (solr running)
  GIVEN I am in an environment that allows execution of solr server
        process management shell scripts from within PHP
    AND I configured a solr server endpoint on "localhost"
    AND a solr process is running on the local machine
    AND the PID-file of the process is in the installation's
        files direcory
   WHEN I open up the lucene plugin search settings page
   THEN I'll see a button "Stop Server".

SCENARIO: stop embedded solr server
  GIVEN I see the button "Stop Server" on the lucene plugin
        search settings page
   WHEN I click on this button
   THEN the running solr process will be stopped.