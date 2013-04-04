#!/usr/bin/php -q
<?php

// This code demonstrates how to lookup the country and region by IP Address
// It is designed to work with GeoIP Organization or GeoIP ISP available from MaxMind

include("geoip.inc");

$giasn = geoip_open("/usr/local/share/GeoIP/GeoIPASNumv6.dat",GEOIP_STANDARD);

$ip = '2001:4860:0:1001::68';
$asn = geoip_name_by_addr_v6($giasn,$ip);
print "$ip has asn " . $asn . "\n";

geoip_close($giasn);

?>

