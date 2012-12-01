:: Execute this script on Windows to start the solr server. Please read the
:: README file that comes with this plugin first to understand how to install
:: and configure Solr. You'll find usage examples there, too.
::
:: Usage: start.bat

@ECHO OFF
SETLOCAL ENABLEEXTENSIONS

:: Source common variables.
CALL %~dp0script-startup.bat

:: Check whether solr is already running.
IF %SOLR_RUNNING% == true (
  ECHO Solr is already running!
  EXIT /B 1
)

:: The deployment directory
SET DEPLOYMENT_DIR=%PLUGIN_DIR%\embedded
SET JAVA_OPTIONS=-Dsolr.deployment="%DEPLOYMENT_DIR%"

:: Jetty configuration
SET JETTY_CONF="%DEPLOYMENT_DIR%\etc\jetty.xml"
:: Use the following line instead if you want extra logging.
::SET JETTY_CONF="%DEPLOYMENT_DIR%\etc\jetty-logging.xml" "%DEPLOYMENT_DIR%\etc\jetty.xml"

SET JAVA_OPTIONS=%JAVA_OPTIONS% -Djetty.home="%JETTY_HOME%"

:: Solr home
SET SOLR_HOME=%DEPLOYMENT_DIR%\solr
SET JAVA_OPTIONS=%JAVA_OPTIONS% -Dsolr.solr.home="%SOLR_HOME%"

:: Solr index data directory
SET SOLR_DATA=%LUCENE_FILES%\data
IF NOT EXIST "%SOLR_DATA%" (
  MD "%SOLR_DATA%"
)
SET JAVA_OPTIONS=%JAVA_OPTIONS% -Dsolr.data.dir="%SOLR_DATA%"

:: Logging configuration
SET JAVA_OPTIONS=%JAVA_OPTIONS% -Djava.util.logging.config.file="%DEPLOYMENT_DIR%\etc\logging.properties" -Djetty.logs="%LUCENE_FILES%"

:: Temp directory
SET JAVA_OPTIONS=%JAVA_OPTIONS% -Djava.io.tmpdir="%TEMP%"

:: Set a unique identifier so that we can stop the process.
SET PROC_SECRET=%RANDOM%%RANDOM%%RANDOM%%RANDOM%%RANDOM%
SET JAVA_OPTIONS=%JAVA_OPTIONS% -DSTOP.PORT=8079 -DSTOP.KEY=%PROC_SECRET%

:: Start Solr in the background.
START /B java %JAVA_OPTIONS% -jar "%JETTY_HOME%\start.jar" %JETTY_CONF% >> "%LUCENE_FILES%\solr-java.log" 2>&1

:: Remember the identifier of the process we just started.
ECHO %PROC_SECRET% > "%SOLR_PIDFILE%"

ECHO Started solr^.
ENDLOCAL