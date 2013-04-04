#!/usr/bin/php -q
<?php

// This code demonstrates how to lookup the country by IP Address

include("geoip.inc");

// Uncomment if querying against GeoIP/Lite City.
// include("geoipcity.inc");

$gi = geoip_open("/usr/local/share/GeoIP/GeoIPv6.dat",GEOIP_STANDARD);

echo geoip_country_code_by_addr_v6($gi, "::24.24.24.24") . "\t" .
     geoip_country_name_by_addr_v6($gi, "::24.24.24.24") . "\n";
echo geoip_country_code_by_addr_v6($gi, "::80.24.24.24") . "\t" .
     geoip_country_name_by_addr_v6($gi, "::80.24.24.24") . "\n";

echo geoip_country_code_by_addr_v6($gi, "2001:4860:0:1001::68") . "\t" .
     geoip_country_name_by_addr_v6($gi, "2001:4860:0:1001::68") . "\n";

echo geoip_country_code_by_addr_v6($gi, "2001:67c:26c::") . "\t" .
     geoip_country_name_by_addr_v6($gi, "2001:67c:26c::") . "\n";

echo geoip_country_code_by_addr_v6($gi, "2001:67c:3a0:ffff:ffff:ffff:ffff:ffff") . "\t" .
     geoip_country_name_by_addr_v6($gi, "2001:67c:3a0:ffff:ffff:ffff:ffff:ffff") . "\n";

echo geoip_country_code_by_name_v6($gi, "ipv6.google.com") . "\t" .
     geoip_country_name_by_name_v6($gi, "ipv6.google.com") . "\n";

geoip_close($gi);

?>
