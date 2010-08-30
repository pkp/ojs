<?php
/**
 * @file hsClientQueries.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class pidPlugin
 * @ingroup plugins_generic_pid
 *
 * @brief basic Handle Service soap based functions for PID plugin.
 */

/*
 * NuSOAP - Web Services Toolkit for PHP library is free software; 
 * it can be redistributed and/or modified under the terms of the 
 * GNU Lesser General Public License as published by the 
 * Free Software Foundation; 
 * 
 */

require_once('nusoap/lib/nusoap.php');


function hsClientResolve($handle){

	$soapclient =  set_soap_clent(WS_SERVER_ADDR,WS_REMOTE_USERNAME,WS_REMOTE_PASSWORD,WS_APP_PATH.'/ResolveHandleService?wsdl', true);
	$result = $soapclient->call('resolve',array('handle'=>$handle));

	if ($soapclient->fault) {
		echo '<h2>Fault</h2><pre>';
		print_r($result);
		echo '</pre>';
	} else {
		$err = $soapclient->getError();
		if ($err) {
			echo '<h2>Error!</h2><pre>' . $err . '</pre>';
		}
	}
	return $result;
}

function hsClientCreate($location, $ws_remote_url, $ws_remote_usr = '', $ws_remote_pwd = ''){

	$soapclient =  set_soap_clent($ws_remote_url, '/CreateHandleService?wsdl', $ws_remote_usr, $ws_remote_pwd);
	$result = $soapclient->call('create',array($location));

	if ($soapclient->fault) {
		die('<h2>Fault</h2><pre>'.print_r($result).'</pre>');
	} else {
		$err = $soapclient->getError();
		if ($err) {
			die('<h2>Error!</h2><pre>' . $err . '</pre>');
		}
	}
	return $result;
}

function hsClientDelete($handle){

	$soapclient =  set_soap_clent(WS_SERVER_ADDR,WS_REMOTE_USERNAME,WS_REMOTE_PASSWORD,WS_APP_PATH.'/DeleteHandleService?wsdl', true);
	$result = $soapclient->call('delete',array($handle));

	if ($soapclient->fault) {
		echo '<h2>Fault</h2><pre>';
		print_r($result);
		echo '</pre>';
	} else {
		$err = $soapclient->getError();
		if ($err) {
			echo '<h2>Error!</h2><pre>' . $err . '</pre>';
		}
	}
	return $result;
}

function axisClientVersion($ws_server_addr, $ws_remote_usr, $ws_remote_pwd, $ws_app_path){

	$soapclient =  set_soap_clent($ws_server_addr, $ws_remote_usr, $ws_remote_pwd, $ws_app_path.'/Version?wsdl', true);
	$result = $soapclient->call('getVersion',array('handle'=>$handle));

	if ($soapclient->fault) {
		echo '<h2>Fault</h2><pre>';
		print_r($result);
		echo '</pre>';
	} else {
		$err = $soapclient->getError();
		if ($err) {
			echo '<h2>Error!</h2><pre>' . $err . '</pre>';
		}
	}
	return $result;
}

function set_soap_clent($ws_remote_url, $ws_wsdl_file, $user, $password){

	$remote_url = parse_url($ws_remote_url);
	$soapclient = new nusoapclient($remote_url['scheme'].'://'.(($user && $password)?$user.":".$password."@":'').$remote_url['host'].':'.$remote_url['port']."/".$remote_url['path'].'/'.$ws_wsdl_file, true);
	$soapclient->setCredentials($user,$password,'basic');
	$soapclient->useHTTPPersistentConnection();

	return $soapclient;
}
?>