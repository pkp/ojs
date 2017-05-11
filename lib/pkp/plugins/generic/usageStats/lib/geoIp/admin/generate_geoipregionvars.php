#!/usr/bin/php -q
<?php
# Copyright 2003 Maxmind LLC All Rights Reserved
print "\$iso = array(\n";
$iso = get_iso_3166_2_subcountry_codes();
$keys = array_keys($iso);
$values = array_values($iso);
for ($a0 = 0;$a0 < sizeof($keys);$a0++){
  print "\"" . $keys[$a0] . "\" => array(\n";
  $keys2 = array_keys($values[$a0]);
  $values2 = array_values($values[$a0]);
  for ($a1 = 0;$a1 < sizeof($keys2);$a1++){
    print "\"" . $keys2[$a1] . "\" => \"" . $values2[$a1] . "\"";
  if ($a1 < sizeof($keys2)-1){print ",\n";}
  }
  if ($a0 < sizeof($keys)-1){
  print "),\n";}
  else{
  print ")\n";}
}
print "\$fips = array(\n";
$fips = get_fips_10_4_subcountry_codes();
$keys = array_keys($fips);
$values = array_values($fips);
for ($a0 = 0;$a0 < sizeof($keys);$a0++){
  print "\"" . $keys[$a0] . "\" => array(\n";
  $keys2 = array_keys($values[$a0]);
  $values2 = array_values($values[$a0]);
  for ($a1 = 0;$a1 < sizeof($keys2);$a1++){
    #setsubstr($value2[$a1],strlen($value2[$a1])-1,1,",");
    print "\"" . $keys2[$a1] . "\" => \"" . $values2[$a1] . "\"";
  if ($a1 < sizeof($keys2)-1){print ",\n";}
  }
  if ($a0 < sizeof($keys)-1){
  print "),\n";}
  else{
  print ")\n";}
}
print ");\n";
function get_iso_3166_2_subcountry_codes(){
  $f = fopen("../iso3166_2","r");
    $str = fgets($f,4096);
  while (!feof($f)){
    $str = fgets($f,4096);
    $substrs = explode(",",$str);
    list($country,$region,$name) = $substrs;
    if (count($substrs) > 3){
    for ($a0 = 3;$a0 < count($substrs);$a0++){ 
      $name = $name .",". $substrs[$a0];
    }
    }
    if ($name){
    $name = substr($name,1,strlen($name)-3);
    $a[$country][$region] = $name;}
  }
  fclose($f);
  return $a;
}

function get_fips_10_4_subcountry_codes(){
  $f = fopen("../fips10_4","r");
    $str = fgets($f,4096);
  while (!feof($f)){
    $str = fgets($f,4096);
    $substrs = explode(",",$str);
    list($country,$region,$name) = $substrs; 
    if (count($substrs) > 3){
    for ($a0 = 3;$a0 < count($substrs);$a0++){ 
      $name = $name .",". $substrs[$a0];
    }
    }
    if ($name){
    $name = substr($name,1,strlen($name)-3);
    $a[$country][$region] = $name;}
  }
  fclose($f);
  return $a;
}

?>
