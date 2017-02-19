#OJS DPS Payment Plugin

This plugin is used in conjunction with OJS (Open Journal System) to enable users to make payments using the DPS payment gateway.

The plugin is installed at ./plugins/paymethod/dps in the OJS installation.

You need to configure the plugin from the control panel inside the app before use. Once it is selected as the payment method, users will be sent to the DPS credit card entry page when they are required to pay.

##Installation
You need to install the PHP xml extension.

On Redhat/Centos:

`sudo yum install php-xml`

##Testing

* [DPS admin page](https://sec.paymentexpress.com/pxmi/logon)
 