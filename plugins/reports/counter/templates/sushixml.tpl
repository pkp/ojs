{**
 * plugins/reports/counter/templates/sushixml.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * SUSHI response XML
 *}
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"
  xmlns:xsi="http://www.w3.org/1999/XMLSchema-instance"
  xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"
  xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"
  xmlns:xsd="http://www.w3.org/1999/XMLSchema">
  <SOAP-ENV:Body>
  <ReportResponse
    ID="ID0"
    xsi:schemaLocation="http://www.niso.org/schemas/sushi/counter http://www.niso.org/schemas/sushi/counter_sushi3_0.xsd"
    Created="{$smarty.now|date_format:"%Y-%m-%dT%H:%M:%SZ"}"
    xmlns="http://www.niso.org/schemas/sushi/counter"
    xmlns:s="http://www.niso.org/schemas/sushi"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <s:Requestor>
      <s:ID>{$requestorID|escape:"html"}</s:ID>
      <s:Name>{$requestorName|escape:"html"}</s:Name>
      <s:Email>{$requestorEmail|escape:"html"}</s:Email>
    </s:Requestor>
    <s:CustomerReference>
      <s:ID>{$customerReferenceID|escape:"html"}</s:ID>
    </s:CustomerReference>
    <s:ReportDefinition Name="JR1" Release="3">
      <s:Filters>
        <s:UsageDateRange>
          <s:Begin>{$usageDateBegin|escape:"html"}</s:Begin>
          <s:End>{$usageDateEnd|escape:"html"}</s:End>
        </s:UsageDateRange>
      </s:Filters>
    </s:ReportDefinition>
    <Report>
{include file=$templatePath|concat:"reportxml.tpl" omitWrapper='true'}
    </Report>
  </ReportResponse>
</SOAP-ENV:Body>
</SOAP-ENV:Envelope>
