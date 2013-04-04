#!/usr/bin/php -q
<?php

include("geoip.inc");

$gi = geoip_open("/usr/local/share/GeoIP/GeoIPNetSpeedCell.dat",GEOIP_STANDARD);

$netspeed = geoip_name_by_addr($gi,"24.24.24.24");

print $netspeed . "\n";

geoip_close($gi);

?>
