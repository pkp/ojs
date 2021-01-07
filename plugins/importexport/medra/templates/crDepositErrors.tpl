<?php
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset|escape}" />
		<style type="text/css">
			table thead th {
				border: 1px solid black; 
				font-size: 18; 
				font-weight: bold; 
				padding: 10px;
			}
			table {
				border-collapse: collapse;
				font-size: 18;
			}
			table tbody td {
				border: 1px solid black; 
				font-size: 18; 
				padding: 10px;
			}
			table tbody tr:nth-child(even) {
				background: #ABCDEF;
			}
			table tbody tr:nth-child(odd) {
				background: #BDB9AA;
			}
		</style>
	</head>
	<body>
		<h2>{translate key="plugins.importexport.common.validationErrors"}</h2>
		<div>{translate key="plugins.importexport.medra.crossref.error.cause"}</div><br/>
		<div>{translate key="plugins.importexport.medra.crossref.error.number"}:{$errNo|escape}</div><br/>
		<div>{translate key="plugins.importexport.medra.crossref.error.details"}:</div><br/>
		<table>
			<thead>
				<tr>
					<th>{translate key="plugins.importexport.medra.crossref.error.code"}</th>
					<th>{translate key="plugins.importexport.medra.crossref.error.element"}</th>
					<th>{translate key="plugins.importexport.medra.crossref.error.description"}</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$errors item=error}
					<tr>
						<td>{$error['code']|escape}</td>
						<td>{$error['reference']|escape}</td>
						<td>{$error['description']|escape}</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
		<h3>{translate key="plugins.importexport.common.invalidXML"}</h3>
		<pre>{$xml|escape}</pre>
	</body>
</html>