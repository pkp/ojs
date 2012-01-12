Tests in this folder require the presence of a test server and a working Selenium server.

Please follow the following steps to install the test server on *nix:
- create a MySQL database and import the testserver.sql script
- unpack files.tar.gz and use this files directory
- configure an OJS instance:
  - configure access to the imported test data
  - debug.webtest_base_url = http://...the base url of the test ojs instance...
  - debug.webtest_admin_pw = ojsojs
- optional: change the admin-password of the test server if it is publicly exposed 
- make sure you have got the Firefox browser installed on your machine (requires X, Xvfb can be used if a full X-system is not present)
- install and start a Selenium server

To test DataCite and mEDRA DOI registration configer the OJS instance:
  - debug.webtest_datacite_pw = ask for the datacite password for the user 'TIB.OJSTEST'
  - debug.webtest_medra_pw = ask for the medra password for the user 'TEST_OJS'

Now you can execute all functional tests via PHPUnit (see PHPUnit documentation in the PKP wiki).