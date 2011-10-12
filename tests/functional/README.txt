Tests in this folder require the presence of a test server and a working Selenium server.

Please follow the following steps to install the test server on *nix:
- create a MySQL database and import the testserver.sql script
- configure an OJS instance:
  - configure access to the imported test data
  - oai.repository_id = "ojs.ojs-test.cedis.fu-berlin.de"
  - debug.webtest_base_url = "http://...the base url of the test ojs instance..."
- optional: change the admin-password of the test server if it is publicly exposed 
- make sure you have got the Firefox browser installed on your machine (requires X, Xvfb can be used if a full X-system is not present)
- install and start a Selenium server

Now you can execute all functional tests via PHPUnit (see PHPUnit documentation in the PKP wiki).