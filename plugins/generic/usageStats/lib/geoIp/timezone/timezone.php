<?php 
function get_time_zone($country,$region) {
  switch ($country) { 
case "US":
    switch ($region) { 
  case "AL":
      $timezone = "America/Chicago";
      break;
  case "AK":
      $timezone = "America/Anchorage";
      break;
  case "AZ":
      $timezone = "America/Phoenix";
      break;
  case "AR":
      $timezone = "America/Chicago";
      break;
  case "CA":
      $timezone = "America/Los_Angeles";
      break;
  case "CO":
      $timezone = "America/Denver";
      break;
  case "CT":
      $timezone = "America/New_York";
      break;
  case "DE":
      $timezone = "America/New_York";
      break;
  case "DC":
      $timezone = "America/New_York";
      break;
  case "FL":
      $timezone = "America/New_York";
      break;
  case "GA":
      $timezone = "America/New_York";
      break;
  case "HI":
      $timezone = "Pacific/Honolulu";
      break;
  case "ID":
      $timezone = "America/Denver";
      break;
  case "IL":
      $timezone = "America/Chicago";
      break;
  case "IN":
      $timezone = "America/Indianapolis";
      break;
  case "IA":
      $timezone = "America/Chicago";
      break;
  case "KS":
      $timezone = "America/Chicago";
      break;
  case "KY":
      $timezone = "America/New_York";
      break;
  case "LA":
      $timezone = "America/Chicago";
      break;
  case "ME":
      $timezone = "America/New_York";
      break;
  case "MD":
      $timezone = "America/New_York";
      break;
  case "MA":
      $timezone = "America/New_York";
      break;
  case "MI":
      $timezone = "America/New_York";
      break;
  case "MN":
      $timezone = "America/Chicago";
      break;
  case "MS":
      $timezone = "America/Chicago";
      break;
  case "MO":
      $timezone = "America/Chicago";
      break;
  case "MT":
      $timezone = "America/Denver";
      break;
  case "NE":
      $timezone = "America/Chicago";
      break;
  case "NV":
      $timezone = "America/Los_Angeles";
      break;
  case "NH":
      $timezone = "America/New_York";
      break;
  case "NJ":
      $timezone = "America/New_York";
      break;
  case "NM":
      $timezone = "America/Denver";
      break;
  case "NY":
      $timezone = "America/New_York";
      break;
  case "NC":
      $timezone = "America/New_York";
      break;
  case "ND":
      $timezone = "America/Chicago";
      break;
  case "OH":
      $timezone = "America/New_York";
      break;
  case "OK":
      $timezone = "America/Chicago";
      break;
  case "OR":
      $timezone = "America/Los_Angeles";
      break;
  case "PA":
      $timezone = "America/New_York";
      break;
  case "RI":
      $timezone = "America/New_York";
      break;
  case "SC":
      $timezone = "America/New_York";
      break;
  case "SD":
      $timezone = "America/Chicago";
      break;
  case "TN":
      $timezone = "America/Chicago";
      break;
  case "TX":
      $timezone = "America/Chicago";
      break;
  case "UT":
      $timezone = "America/Denver";
      break;
  case "VT":
      $timezone = "America/New_York";
      break;
  case "VA":
      $timezone = "America/New_York";
      break;
  case "WA":
      $timezone = "America/Los_Angeles";
      break;
  case "WV":
      $timezone = "America/New_York";
      break;
  case "WI":
      $timezone = "America/Chicago";
      break;
  case "WY":
      $timezone = "America/Denver";
      break;
  } 
  break;
case "CA":
    switch ($region) { 
  case "AB":
      $timezone = "America/Edmonton";
      break;
  case "BC":
      $timezone = "America/Vancouver";
      break;
  case "MB":
      $timezone = "America/Winnipeg";
      break;
  case "NB":
      $timezone = "America/Moncton";
      break;
  case "NL":
      $timezone = "America/St_Johns";
      break;
  case "NT":
      $timezone = "America/Yellowknife";
      break;
  case "NS":
      $timezone = "America/Halifax";
      break;
  case "NU":
      $timezone = "America/Iqaluit";
      break;
  case "ON":
      $timezone = "America/Montreal";
      break;
  case "PE":
      $timezone = "America/Halifax";
      break;
  case "QC":
      $timezone = "America/Moncton";
      break;
  case "SK":
      $timezone = "America/Regina";
      break;
  case "YT":
      $timezone = "America/Whitehorse";
      break;
  } 
  break;
case "AU":
    switch ($region) { 
  case "01":
      $timezone = "Australia/Sydney";
      break;
  case "02":
      $timezone = "Australia/Sydney";
      break;
  case "03":
      $timezone = "Australia/Darwin";
      break;
  case "04":
      $timezone = "Australia/Brisbane";
      break;
  case "05":
      $timezone = "Australia/Adelaide";
      break;
  case "06":
      $timezone = "Australia/Hobart";
      break;
  case "07":
      $timezone = "Australia/Melbourne";
      break;
  case "08":
      $timezone = "Australia/Perth";
      break;
  } 
  break;
case "AS":
    $timezone = "Pacific/Pago_Pago";
    break;
case "CI":
    $timezone = "Africa/Abidjan";
    break;
case "GH":
    $timezone = "Africa/Accra";
    break;
case "DZ":
    $timezone = "Africa/Algiers";
    break;
case "ER":
    $timezone = "Africa/Asmara";
    break;
case "ML":
    $timezone = "Africa/Bamako";
    break;
case "CF":
    $timezone = "Africa/Bangui";
    break;
case "GM":
    $timezone = "Africa/Banjul";
    break;
case "GW":
    $timezone = "Africa/Bissau";
    break;
case "CG":
    $timezone = "Africa/Brazzaville";
    break;
case "BI":
    $timezone = "Africa/Bujumbura";
    break;
case "EG":
    $timezone = "Africa/Cairo";
    break;
case "MA":
    $timezone = "Africa/Casablanca";
    break;
case "GN":
    $timezone = "Africa/Conakry";
    break;
case "SN":
    $timezone = "Africa/Dakar";
    break;
case "DJ":
    $timezone = "Africa/Djibouti";
    break;
case "SL":
    $timezone = "Africa/Freetown";
    break;
case "BW":
    $timezone = "Africa/Gaborone";
    break;
case "ZW":
    $timezone = "Africa/Harare";
    break;
case "ZA":
    $timezone = "Africa/Johannesburg";
    break;
case "UG":
    $timezone = "Africa/Kampala";
    break;
case "UM":
    $timezone = "Pacific/Wake";
    break;
case "SD":
    $timezone = "Africa/Khartoum";
    break;
case "RW":
    $timezone = "Africa/Kigali";
    break;
case "NG":
    $timezone = "Africa/Lagos";
    break;
case "GA":
    $timezone = "Africa/Libreville";
    break;
case "TG":
    $timezone = "Africa/Lome";
    break;
case "AO":
    $timezone = "Africa/Luanda";
    break;
case "AQ":
    $timezone = "Antarctica/South_Pole";
    break;
case "ZM":
    $timezone = "Africa/Lusaka";
    break;
case "GQ":
    $timezone = "Africa/Malabo";
    break;
case "MZ":
    $timezone = "Africa/Maputo";
    break;
case "LS":
    $timezone = "Africa/Maseru";
    break;
case "SZ":
    $timezone = "Africa/Mbabane";
    break;
case "SO":
    $timezone = "Africa/Mogadishu";
    break;
case "LR":
    $timezone = "Africa/Monrovia";
    break;
case "KE":
    $timezone = "Africa/Nairobi";
    break;
case "TD":
    $timezone = "Africa/Ndjamena";
    break;
case "NE":
    $timezone = "Africa/Niamey";
    break;
case "MR":
    $timezone = "Africa/Nouakchott";
    break;
case "BF":
    $timezone = "Africa/Ouagadougou";
    break;
case "ST":
    $timezone = "Africa/Sao_Tome";
    break;
case "LY":
    $timezone = "Africa/Tripoli";
    break;
case "TN":
    $timezone = "Africa/Tunis";
    break;
case "AI":
    $timezone = "America/Anguilla";
    break;
case "AG":
    $timezone = "America/Antigua";
    break;
case "AW":
    $timezone = "America/Aruba";
    break;
case "BB":
    $timezone = "America/Barbados";
    break;
case "BZ":
    $timezone = "America/Belize";
    break;
case "CO":
    $timezone = "America/Bogota";
    break;
case "VE":
    $timezone = "America/Caracas";
    break;
case "KY":
    $timezone = "America/Cayman";
    break;
case "CR":
    $timezone = "America/Costa_Rica";
    break;
case "DM":
    $timezone = "America/Dominica";
    break;
case "SV":
    $timezone = "America/El_Salvador";
    break;
case "GD":
    $timezone = "America/Grenada";
    break;
case "FR":
    $timezone = "Europe/Paris";
    break;
case "GP":
    $timezone = "America/Guadeloupe";
    break;
case "GT":
    $timezone = "America/Guatemala";
    break;
case "GY":
    $timezone = "America/Guyana";
    break;
case "CU":
    $timezone = "America/Havana";
    break;
case "JM":
    $timezone = "America/Jamaica";
    break;
case "BO":
    $timezone = "America/La_Paz";
    break;
case "PE":
    $timezone = "America/Lima";
    break;
case "NI":
    $timezone = "America/Managua";
    break;
case "MQ":
    $timezone = "America/Martinique";
    break;
case "UY":
    $timezone = "America/Montevideo";
    break;
case "MS":
    $timezone = "America/Montserrat";
    break;
case "BS":
    $timezone = "America/Nassau";
    break;
case "PA":
    $timezone = "America/Panama";
    break;
case "SR":
    $timezone = "America/Paramaribo";
    break;
case "PR":
    $timezone = "America/Puerto_Rico";
    break;
case "KN":
    $timezone = "America/St_Kitts";
    break;
case "LC":
    $timezone = "America/St_Lucia";
    break;
case "VC":
    $timezone = "America/St_Vincent";
    break;
case "HN":
    $timezone = "America/Tegucigalpa";
    break;
case "YE":
    $timezone = "Asia/Aden";
    break;
case "JO":
    $timezone = "Asia/Amman";
    break;
case "TM":
    $timezone = "Asia/Ashgabat";
    break;
case "IQ":
    $timezone = "Asia/Baghdad";
    break;
case "BH":
    $timezone = "Asia/Bahrain";
    break;
case "AZ":
    $timezone = "Asia/Baku";
    break;
case "TH":
    $timezone = "Asia/Bangkok";
    break;
case "LB":
    $timezone = "Asia/Beirut";
    break;
case "KG":
    $timezone = "Asia/Bishkek";
    break;
case "BN":
    $timezone = "Asia/Brunei";
    break;
case "IN":
    $timezone = "Asia/Kolkata";
    break;
case "MN":
    switch ($region) { 
  case "06":
      $timezone = "Asia/Choibalsan";
      break;
  case "11":
      $timezone = "Asia/Ulaanbaatar";
      break;
  case "17":
      $timezone = "Asia/Choibalsan";
      break;
  case "19":
      $timezone = "Asia/Hovd";
      break;
  case "20":
      $timezone = "Asia/Ulaanbaatar";
      break;
  case "21":
      $timezone = "Asia/Ulaanbaatar";
      break;
  case "25":
      $timezone = "Asia/Ulaanbaatar";
      break;
  } 
  break;
case "MO":
    $timezone = "Asia/Macau";
    break;
case "LK":
    $timezone = "Asia/Colombo";
    break;
case "BD":
    $timezone = "Asia/Dhaka";
    break;
case "AE":
    $timezone = "Asia/Dubai";
    break;
case "TJ":
    $timezone = "Asia/Dushanbe";
    break;
case "HK":
    $timezone = "Asia/Hong_Kong";
    break;
case "TR":
    $timezone = "Europe/Istanbul";
    break;
case "IL":
    $timezone = "Asia/Jerusalem";
    break;
case "AF":
    $timezone = "Asia/Kabul";
    break;
case "PK":
    $timezone = "Asia/Karachi";
    break;
case "NP":
    $timezone = "Asia/Kathmandu";
    break;
case "KW":
    $timezone = "Asia/Kuwait";
    break;
case "MO":
    $timezone = "Asia/Macao";
    break;
case "PH":
    $timezone = "Asia/Manila";
    break;
case "OM":
    $timezone = "Asia/Muscat";
    break;
case "CY":
    $timezone = "Asia/Nicosia";
    break;
case "KP":
    $timezone = "Asia/Pyongyang";
    break;
case "QA":
    $timezone = "Asia/Qatar";
    break;
case "MM":
    $timezone = "Asia/Rangoon";
    break;
case "SA":
    $timezone = "Asia/Riyadh";
    break;
case "KR":
    $timezone = "Asia/Seoul";
    break;
case "SG":
    $timezone = "Asia/Singapore";
    break;
case "TW":
    $timezone = "Asia/Taipei";
    break;
case "GE":
    $timezone = "Asia/Tbilisi";
    break;
case "BT":
    $timezone = "Asia/Thimphu";
    break;
case "BV":
    $timezone = "Antarctica/Syowa";
    break;
case "JP":
    $timezone = "Asia/Tokyo";
    break;
case "LA":
    $timezone = "Asia/Vientiane";
    break;
case "AM":
    $timezone = "Asia/Yerevan";
    break;
case "BM":
    $timezone = "Atlantic/Bermuda";
    break;
case "CV":
    $timezone = "Atlantic/Cape_Verde";
    break;
case "FO":
    $timezone = "Atlantic/Faroe";
    break;
case "FM":
    $timezone = "Pacific/Pohnpei";
    break;
case "IS":
    $timezone = "Atlantic/Reykjavik";
    break;
case "GS":
    $timezone = "Atlantic/South_Georgia";
    break;
case "SH":
    $timezone = "Atlantic/St_Helena";
    break;
case "CL":
    $timezone = "Chile/Santiago";
    break;
case "NL":
    $timezone = "Europe/Amsterdam";
    break;
case "AD":
    $timezone = "Europe/Andorra";
    break;
case "GR":
    $timezone = "Europe/Athens";
    break;
case "YU":
    $timezone = "Europe/Belgrade";
    break;
case "DE":
    $timezone = "Europe/Berlin";
    break;
case "SK":
    $timezone = "Europe/Bratislava";
    break;
case "BE":
    $timezone = "Europe/Brussels";
    break;
case "RO":
    $timezone = "Europe/Bucharest";
    break;
case "HU":
    $timezone = "Europe/Budapest";
    break;
case "DK":
    $timezone = "Europe/Copenhagen";
    break;
case "IE":
    $timezone = "Europe/Dublin";
    break;
case "GI":
    $timezone = "Europe/Gibraltar";
    break;
case "FI":
    $timezone = "Europe/Helsinki";
    break;
case "SI":
    $timezone = "Europe/Ljubljana";
    break;
case "GB":
    $timezone = "Europe/London";
    break;
case "LU":
    $timezone = "Europe/Luxembourg";
    break;
case "MT":
    $timezone = "Europe/Malta";
    break;
case "BY":
    $timezone = "Europe/Minsk";
    break;
case "MC":
    $timezone = "Europe/Monaco";
    break;
case "NO":
    $timezone = "Europe/Oslo";
    break;
case "CZ":
    $timezone = "Europe/Prague";
    break;
case "LV":
    $timezone = "Europe/Riga";
    break;
case "IT":
    $timezone = "Europe/Rome";
    break;
case "SM":
    $timezone = "Europe/San_Marino";
    break;
case "BA":
    $timezone = "Europe/Sarajevo";
    break;
case "MK":
    $timezone = "Europe/Skopje";
    break;
case "BG":
    $timezone = "Europe/Sofia";
    break;
case "SE":
    $timezone = "Europe/Stockholm";
    break;
case "EE":
    $timezone = "Europe/Tallinn";
    break;
case "AL":
    $timezone = "Europe/Tirane";
    break;
case "LI":
    $timezone = "Europe/Vaduz";
    break;
case "VA":
    $timezone = "Europe/Vatican";
    break;
case "AT":
    $timezone = "Europe/Vienna";
    break;
case "LT":
    $timezone = "Europe/Vilnius";
    break;
case "PL":
    $timezone = "Europe/Warsaw";
    break;
case "HR":
    $timezone = "Europe/Zagreb";
    break;
case "IR":
    $timezone = "Asia/Tehran";
    break;
case "MG":
    $timezone = "Indian/Antananarivo";
    break;
case "CX":
    $timezone = "Indian/Christmas";
    break;
case "CC":
    $timezone = "Indian/Cocos";
    break;
case "KM":
    $timezone = "Indian/Comoro";
    break;
case "MV":
    $timezone = "Indian/Maldives";
    break;
case "MU":
    $timezone = "Indian/Mauritius";
    break;
case "YT":
    $timezone = "Indian/Mayotte";
    break;
case "RE":
    $timezone = "Indian/Reunion";
    break;
case "FJ":
    $timezone = "Pacific/Fiji";
    break;
case "TV":
    $timezone = "Pacific/Funafuti";
    break;
case "GU":
    $timezone = "Pacific/Guam";
    break;
case "NR":
    $timezone = "Pacific/Nauru";
    break;
case "NU":
    $timezone = "Pacific/Niue";
    break;
case "NF":
    $timezone = "Pacific/Norfolk";
    break;
case "PW":
    $timezone = "Pacific/Palau";
    break;
case "PN":
    $timezone = "Pacific/Pitcairn";
    break;
case "CK":
    $timezone = "Pacific/Rarotonga";
    break;
case "WS":
    $timezone = "Pacific/Apia";
    break;
case "KI":
    $timezone = "Pacific/Tarawa";
    break;
case "TO":
    $timezone = "Pacific/Tongatapu";
    break;
case "WF":
    $timezone = "Pacific/Wallis";
    break;
case "TZ":
    $timezone = "Africa/Dar_es_Salaam";
    break;
case "VN":
    $timezone = "Asia/Ho_Chi_Minh";
    break;
case "KH":
    $timezone = "Asia/Phnom_Penh";
    break;
case "CM":
    $timezone = "Africa/Douala";
    break;
case "DO":
    $timezone = "America/Santo_Domingo";
    break;
case "TL":
    $timezone = "Asia/Jakarta";
    break;
case "ET":
    $timezone = "Africa/Addis_Ababa";
    break;
case "FX":
    $timezone = "Europe/Paris";
    break;
case "HT":
    $timezone = "America/Port-au-Prince";
    break;
case "CH":
    $timezone = "Europe/Zurich";
    break;
case "AN":
    $timezone = "America/Curacao";
    break;
case "BJ":
    $timezone = "Africa/Porto-Novo";
    break;
case "EH":
    $timezone = "Africa/El_Aaiun";
    break;
case "FK":
    $timezone = "Atlantic/Stanley";
    break;
case "GF":
    $timezone = "America/Cayenne";
    break;
case "IO":
    $timezone = "Indian/Chagos";
    break;
case "MD":
    $timezone = "Europe/Chisinau";
    break;
case "MP":
    $timezone = "Pacific/Saipan";
    break;
case "MW":
    $timezone = "Africa/Blantyre";
    break;
case "NA":
    $timezone = "Africa/Windhoek";
    break;
case "NC":
    $timezone = "Pacific/Noumea";
    break;
case "PG":
    $timezone = "Pacific/Port_Moresby";
    break;
case "PM":
    $timezone = "America/Miquelon";
    break;
case "PS":
    $timezone = "Asia/Gaza";
    break;
case "PY":
    $timezone = "America/Asuncion";
    break;
case "SB":
    $timezone = "Pacific/Guadalcanal";
    break;
case "SC":
    $timezone = "Indian/Mahe";
    break;
case "SJ":
    $timezone = "Arctic/Longyearbyen";
    break;
case "SY":
    $timezone = "Asia/Damascus";
    break;
case "TC":
    $timezone = "America/Grand_Turk";
    break;
case "TF":
    $timezone = "Indian/Kerguelen";
    break;
case "TK":
    $timezone = "Pacific/Fakaofo";
    break;
case "TT":
    $timezone = "America/Port_of_Spain";
    break;
case "VG":
    $timezone = "America/Tortola";
    break;
case "VI":
    $timezone = "America/St_Thomas";
    break;
case "VU":
    $timezone = "Pacific/Efate";
    break;
case "RS":
    $timezone = "Europe/Belgrade";
    break;
case "ME":
    $timezone = "Europe/Podgorica";
    break;
case "AX":
    $timezone = "Europe/Mariehamn";
    break;
case "GG":
    $timezone = "Europe/Guernsey";
    break;
case "IM":
    $timezone = "Europe/Isle_of_Man";
    break;
case "JE":
    $timezone = "Europe/Jersey";
    break;
case "BL":
    $timezone = "America/St_Barthelemy";
    break;
case "MF":
    $timezone = "America/Marigot";
    break;
case "AR":
    switch ($region) { 
  case "01":
      $timezone = "America/Argentina/Buenos_Aires";
      break;
  case "02":
      $timezone = "America/Argentina/Catamarca";
      break;
  case "03":
      $timezone = "America/Argentina/Tucuman";
      break;
  case "04":
      $timezone = "America/Argentina/Rio_Gallegos";
      break;
  case "05":
      $timezone = "America/Argentina/Cordoba";
      break;
  case "06":
      $timezone = "America/Argentina/Tucuman";
      break;
  case "07":
      $timezone = "America/Argentina/Buenos_Aires";
      break;
  case "08":
      $timezone = "America/Argentina/Buenos_Aires";
      break;
  case "09":
      $timezone = "America/Argentina/Tucuman";
      break;
  case "10":
      $timezone = "America/Argentina/Jujuy";
      break;
  case "11":
      $timezone = "America/Argentina/San_Luis";
      break;
  case "12":
      $timezone = "America/Argentina/La_Rioja";
      break;
  case "13":
      $timezone = "America/Argentina/Mendoza";
      break;
  case "14":
      $timezone = "America/Argentina/Buenos_Aires";
      break;
  case "15":
      $timezone = "America/Argentina/San_Luis";
      break;
  case "16":
      $timezone = "America/Argentina/Mendoza";
      break;
  case "17":
      $timezone = "America/Argentina/Salta";
      break;
  case "18":
      $timezone = "America/Argentina/San_Juan";
      break;
  case "19":
      $timezone = "America/Argentina/San_Luis";
      break;
  case "20":
      $timezone = "America/Argentina/Rio_Gallegos";
      break;
  case "21":
      $timezone = "America/Argentina/Buenos_Aires";
      break;
  case "22":
      $timezone = "America/Argentina/Tucuman";
      break;
  case "23":
      $timezone = "America/Argentina/Ushuaia";
      break;
  case "24":
      $timezone = "America/Argentina/Tucuman";
      break;
  } 
  break;
case "BR":
    switch ($region) { 
  case "01":
      $timezone = "America/Rio_Branco";
      break;
  case "02":
      $timezone = "America/Maceio";
      break;
  case "03":
      $timezone = "America/Belem";
      break;
  case "04":
      $timezone = "America/Manaus";
      break;
  case "05":
      $timezone = "America/Bahia";
      break;
  case "06":
      $timezone = "America/Fortaleza";
      break;
  case "07":
      $timezone = "America/Sao_Paulo";
      break;
  case "08":
      $timezone = "America/Sao_Paulo";
      break;
  case "11":
      $timezone = "America/Campo_Grande";
      break;
  case "13":
      $timezone = "America/Belem";
      break;
  case "14":
      $timezone = "America/Cuiaba";
      break;
  case "15":
      $timezone = "America/Sao_Paulo";
      break;
  case "16":
      $timezone = "America/Belem";
      break;
  case "17":
      $timezone = "America/Recife";
      break;
  case "18":
      $timezone = "America/Sao_Paulo";
      break;
  case "20":
      $timezone = "America/Fortaleza";
      break;
  case "21":
      $timezone = "America/Sao_Paulo";
      break;
  case "22":
      $timezone = "America/Recife";
      break;
  case "23":
      $timezone = "America/Sao_Paulo";
      break;
  case "24":
      $timezone = "America/Porto_Velho";
      break;
  case "25":
      $timezone = "America/Boa_Vista";
      break;
  case "26":
      $timezone = "America/Sao_Paulo";
      break;
  case "27":
      $timezone = "America/Sao_Paulo";
      break;
  case "28":
      $timezone = "America/Maceio";
      break;
  case "29":
      $timezone = "America/Campo_Grande";
      break;
  case "30":
      $timezone = "America/Recife";
      break;
  case "31":
      $timezone = "America/Araguaina";
      break;
  } 
  break;
case "CD":
    switch ($region) { 
  case "01":
      $timezone = "Africa/Kinshasa";
      break;
  case "02":
      $timezone = "Africa/Kinshasa";
      break;
  case "03":
      $timezone = "Africa/Kinshasa";
      break;
  case "04":
      $timezone = "Africa/Lubumbashi";
      break;
  case "05":
      $timezone = "Africa/Lubumbashi";
      break;
  case "06":
      $timezone = "Africa/Kinshasa";
      break;
  case "07":
      $timezone = "Africa/Lubumbashi";
      break;
  case "08":
      $timezone = "Africa/Kinshasa";
      break;
  case "09":
      $timezone = "Africa/Lubumbashi";
      break;
  case "10":
      $timezone = "Africa/Lubumbashi";
      break;
  case "11":
      $timezone = "Africa/Lubumbashi";
      break;
  case "12":
      $timezone = "Africa/Lubumbashi";
      break;
  } 
  break;
case "CN":
    switch ($region) { 
  case "01":
      $timezone = "Asia/Shanghai";
      break;
  case "02":
      $timezone = "Asia/Shanghai";
      break;
  case "03":
      $timezone = "Asia/Shanghai";
      break;
  case "04":
      $timezone = "Asia/Shanghai";
      break;
  case "05":
      $timezone = "Asia/Harbin";
      break;
  case "06":
      $timezone = "Asia/Chongqing";
      break;
  case "07":
      $timezone = "Asia/Shanghai";
      break;
  case "08":
      $timezone = "Asia/Harbin";
      break;
  case "09":
      $timezone = "Asia/Shanghai";
      break;
  case "10":
      $timezone = "Asia/Shanghai";
      break;
  case "11":
      $timezone = "Asia/Chongqing";
      break;
  case "12":
      $timezone = "Asia/Shanghai";
      break;
  case "13":
      $timezone = "Asia/Urumqi";
      break;
  case "14":
      $timezone = "Asia/Kashgar";
      break;
  case "15":
      $timezone = "Asia/Chongqing";
      break;
  case "16":
      $timezone = "Asia/Chongqing";
      break;
  case "18":
      $timezone = "Asia/Chongqing";
      break;
  case "19":
      $timezone = "Asia/Harbin";
      break;
  case "20":
      $timezone = "Asia/Harbin";
      break;
  case "21":
      $timezone = "Asia/Chongqing";
      break;
  case "22":
      $timezone = "Asia/Harbin";
      break;
  case "23":
      $timezone = "Asia/Shanghai";
      break;
  case "24":
      $timezone = "Asia/Chongqing";
      break;
  case "25":
      $timezone = "Asia/Shanghai";
      break;
  case "26":
      $timezone = "Asia/Chongqing";
      break;
  case "28":
      $timezone = "Asia/Shanghai";
      break;
  case "29":
      $timezone = "Asia/Chongqing";
      break;
  case "30":
      $timezone = "Asia/Chongqing";
      break;
  case "31":
      $timezone = "Asia/Chongqing";
      break;
  case "32":
      $timezone = "Asia/Chongqing";
      break;
  case "33":
      $timezone = "Asia/Chongqing";
      break;
  } 
  break;
case "EC":
    switch ($region) { 
  case "01":
      $timezone = "Pacific/Galapagos";
      break;
  case "02":
      $timezone = "America/Guayaquil";
      break;
  case "03":
      $timezone = "America/Guayaquil";
      break;
  case "04":
      $timezone = "America/Guayaquil";
      break;
  case "05":
      $timezone = "America/Guayaquil";
      break;
  case "06":
      $timezone = "America/Guayaquil";
      break;
  case "07":
      $timezone = "America/Guayaquil";
      break;
  case "08":
      $timezone = "America/Guayaquil";
      break;
  case "09":
      $timezone = "America/Guayaquil";
      break;
  case "10":
      $timezone = "America/Guayaquil";
      break;
  case "11":
      $timezone = "America/Guayaquil";
      break;
  case "12":
      $timezone = "America/Guayaquil";
      break;
  case "13":
      $timezone = "America/Guayaquil";
      break;
  case "14":
      $timezone = "America/Guayaquil";
      break;
  case "15":
      $timezone = "America/Guayaquil";
      break;
  case "17":
      $timezone = "America/Guayaquil";
      break;
  case "18":
      $timezone = "America/Guayaquil";
      break;
  case "19":
      $timezone = "America/Guayaquil";
      break;
  case "20":
      $timezone = "America/Guayaquil";
      break;
  case "22":
      $timezone = "America/Guayaquil";
      break;
  case "24":
      $timezone = "America/Guayaquil";
      break;
  } 
  break;
case "ES":
    switch ($region) { 
  case "07":
      $timezone = "Europe/Madrid";
      break;
  case "27":
      $timezone = "Europe/Madrid";
      break;
  case "29":
      $timezone = "Europe/Madrid";
      break;
  case "31":
      $timezone = "Europe/Madrid";
      break;
  case "32":
      $timezone = "Europe/Madrid";
      break;
  case "34":
      $timezone = "Europe/Madrid";
      break;
  case "39":
      $timezone = "Europe/Madrid";
      break;
  case "51":
      $timezone = "Africa/Ceuta";
      break;
  case "52":
      $timezone = "Europe/Madrid";
      break;
  case "53":
      $timezone = "Atlantic/Canary";
      break;
  case "54":
      $timezone = "Europe/Madrid";
      break;
  case "55":
      $timezone = "Europe/Madrid";
      break;
  case "56":
      $timezone = "Europe/Madrid";
      break;
  case "57":
      $timezone = "Europe/Madrid";
      break;
  case "58":
      $timezone = "Europe/Madrid";
      break;
  case "59":
      $timezone = "Europe/Madrid";
      break;
  case "60":
      $timezone = "Europe/Madrid";
      break;
  } 
  break;
case "GL":
    switch ($region) { 
  case "01":
      $timezone = "America/Thule";
      break;
  case "02":
      $timezone = "America/Godthab";
      break;
  case "03":
      $timezone = "America/Godthab";
      break;
  } 
  break;
case "ID":
    switch ($region) { 
  case "01":
      $timezone = "Asia/Pontianak";
      break;
  case "02":
      $timezone = "Asia/Makassar";
      break;
  case "03":
      $timezone = "Asia/Jakarta";
      break;
  case "04":
      $timezone = "Asia/Jakarta";
      break;
  case "05":
      $timezone = "Asia/Jakarta";
      break;
  case "06":
      $timezone = "Asia/Jakarta";
      break;
  case "07":
      $timezone = "Asia/Jakarta";
      break;
  case "08":
      $timezone = "Asia/Jakarta";
      break;
  case "09":
      $timezone = "Asia/Jayapura";
      break;
  case "10":
      $timezone = "Asia/Jakarta";
      break;
  case "11":
      $timezone = "Asia/Pontianak";
      break;
  case "12":
      $timezone = "Asia/Makassar";
      break;
  case "13":
      $timezone = "Asia/Pontianak";
      break;
  case "14":
      $timezone = "Asia/Makassar";
      break;
  case "15":
      $timezone = "Asia/Jakarta";
      break;
  case "16":
      $timezone = "Asia/Makassar";
      break;
  case "17":
      $timezone = "Asia/Makassar";
      break;
  case "18":
      $timezone = "Asia/Makassar";
      break;
  case "19":
      $timezone = "Asia/Pontianak";
      break;
  case "20":
      $timezone = "Asia/Makassar";
      break;
  case "21":
      $timezone = "Asia/Makassar";
      break;
  case "22":
      $timezone = "Asia/Makassar";
      break;
  case "23":
      $timezone = "Asia/Makassar";
      break;
  case "24":
      $timezone = "Asia/Jakarta";
      break;
  case "25":
      $timezone = "Asia/Pontianak";
      break;
  case "26":
      $timezone = "Asia/Pontianak";
      break;
  case "28":
      $timezone = "Asia/Makassar";
      break;
  case "29":
      $timezone = "Asia/Makassar";
      break;
  case "30":
      $timezone = "Asia/Jakarta";
      break;
  case "31":
      $timezone = "Asia/Makassar";
      break;
  case "32":
      $timezone = "Asia/Jakarta";
      break;
  case "33":
      $timezone = "Asia/Jakarta";
      break;
  case "34":
      $timezone = "Asia/Makassar";
      break;
  case "35":
      $timezone = "Asia/Pontianak";
      break;
  case "36":
      $timezone = "Asia/Jayapura";
      break;
  case "37":
      $timezone = "Asia/Pontianak";
      break;
  case "38":
      $timezone = "Asia/Makassar";
      break;
  case "39":
      $timezone = "Asia/Jayapura";
      break;
  case "40":
      $timezone = "Asia/Pontianak";
      break;
  case "41":
      $timezone = "Asia/Makassar";
      break;
  } 
  break;
case "KZ":
    switch ($region) { 
  case "01":
      $timezone = "Asia/Almaty";
      break;
  case "02":
      $timezone = "Asia/Almaty";
      break;
  case "03":
      $timezone = "Asia/Qyzylorda";
      break;
  case "04":
      $timezone = "Asia/Aqtobe";
      break;
  case "05":
      $timezone = "Asia/Qyzylorda";
      break;
  case "06":
      $timezone = "Asia/Aqtau";
      break;
  case "07":
      $timezone = "Asia/Oral";
      break;
  case "08":
      $timezone = "Asia/Qyzylorda";
      break;
  case "09":
      $timezone = "Asia/Aqtau";
      break;
  case "10":
      $timezone = "Asia/Qyzylorda";
      break;
  case "11":
      $timezone = "Asia/Almaty";
      break;
  case "12":
      $timezone = "Asia/Almaty";
      break;
  case "13":
      $timezone = "Asia/Aqtobe";
      break;
  case "14":
      $timezone = "Asia/Qyzylorda";
      break;
  case "15":
      $timezone = "Asia/Almaty";
      break;
  case "16":
      $timezone = "Asia/Aqtobe";
      break;
  case "17":
      $timezone = "Asia/Almaty";
      break;
  } 
  break;
case "MX":
    switch ($region) { 
  case "01":
      $timezone = "America/Bahia_Banderas";
      break;
  case "02":
      $timezone = "America/Tijuana";
      break;
  case "03":
      $timezone = "America/Mazatlan";
      break;
  case "04":
      $timezone = "America/Merida";
      break;
  case "05":
      $timezone = "America/Merida";
      break;
  case "06":
      $timezone = "America/Chihuahua";
      break;
  case "07":
      $timezone = "America/Monterrey";
      break;
  case "08":
      $timezone = "America/Bahia_Banderas";
      break;
  case "09":
      $timezone = "America/Mexico_City";
      break;
  case "10":
      $timezone = "America/Mazatlan";
      break;
  case "11":
      $timezone = "America/Mexico_City";
      break;
  case "12":
      $timezone = "America/Mexico_City";
      break;
  case "13":
      $timezone = "America/Mexico_City";
      break;
  case "14":
      $timezone = "America/Bahia_Banderas";
      break;
  case "15":
      $timezone = "America/Ojinaga";
      break;
  case "16":
      $timezone = "America/Mexico_City";
      break;
  case "17":
      $timezone = "America/Mexico_City";
      break;
  case "18":
      $timezone = "America/Bahia_Banderas";
      break;
  case "19":
      $timezone = "America/Monterrey";
      break;
  case "20":
      $timezone = "America/Mexico_City";
      break;
  case "21":
      $timezone = "America/Mexico_City";
      break;
  case "22":
      $timezone = "America/Mexico_City";
      break;
  case "23":
      $timezone = "America/Cancun";
      break;
  case "24":
      $timezone = "America/Mexico_City";
      break;
  case "25":
      $timezone = "America/Mazatlan";
      break;
  case "26":
      $timezone = "America/Hermosillo";
      break;
  case "27":
      $timezone = "America/Merida";
      break;
  case "28":
      $timezone = "America/Matamoros";
      break;
  case "29":
      $timezone = "America/Mexico_City";
      break;
  case "30":
      $timezone = "America/Mexico_City";
      break;
  case "31":
      $timezone = "America/Merida";
      break;
  case "32":
      $timezone = "America/Bahia_Banderas";
      break;
  } 
  break;
case "MY":
    switch ($region) { 
  case "01":
      $timezone = "Asia/Kuala_Lumpur";
      break;
  case "02":
      $timezone = "Asia/Kuala_Lumpur";
      break;
  case "03":
      $timezone = "Asia/Kuala_Lumpur";
      break;
  case "04":
      $timezone = "Asia/Kuala_Lumpur";
      break;
  case "05":
      $timezone = "Asia/Kuala_Lumpur";
      break;
  case "06":
      $timezone = "Asia/Kuala_Lumpur";
      break;
  case "07":
      $timezone = "Asia/Kuala_Lumpur";
      break;
  case "08":
      $timezone = "Asia/Kuala_Lumpur";
      break;
  case "09":
      $timezone = "Asia/Kuala_Lumpur";
      break;
  case "11":
      $timezone = "Asia/Kuching";
      break;
  case "12":
      $timezone = "Asia/Kuala_Lumpur";
      break;
  case "13":
      $timezone = "Asia/Kuala_Lumpur";
      break;
  case "14":
      $timezone = "Asia/Kuala_Lumpur";
      break;
  case "15":
      $timezone = "Asia/Kuching";
      break;
  case "16":
      $timezone = "Asia/Kuching";
      break;
  } 
  break;
case "NZ":
    switch ($region) { 
  case "85":
      $timezone = "Pacific/Auckland";
      break;
  case "E7":
      $timezone = "Pacific/Auckland";
      break;
  case "E8":
      $timezone = "Pacific/Auckland";
      break;
  case "E9":
      $timezone = "Pacific/Auckland";
      break;
  case "F1":
      $timezone = "Pacific/Auckland";
      break;
  case "F2":
      $timezone = "Pacific/Auckland";
      break;
  case "F3":
      $timezone = "Pacific/Auckland";
      break;
  case "F4":
      $timezone = "Pacific/Auckland";
      break;
  case "F5":
      $timezone = "Pacific/Auckland";
      break;
  case "F6":
      $timezone = "Pacific/Auckland";
      break;
  case "F7":
      $timezone = "Pacific/Chatham";
      break;
  case "F8":
      $timezone = "Pacific/Auckland";
      break;
  case "F9":
      $timezone = "Pacific/Auckland";
      break;
  case "G1":
      $timezone = "Pacific/Auckland";
      break;
  case "G2":
      $timezone = "Pacific/Auckland";
      break;
  case "G3":
      $timezone = "Pacific/Auckland";
      break;
  } 
  break;
case "PT":
    switch ($region) { 
  case "02":
      $timezone = "Europe/Lisbon";
      break;
  case "03":
      $timezone = "Europe/Lisbon";
      break;
  case "04":
      $timezone = "Europe/Lisbon";
      break;
  case "05":
      $timezone = "Europe/Lisbon";
      break;
  case "06":
      $timezone = "Europe/Lisbon";
      break;
  case "07":
      $timezone = "Europe/Lisbon";
      break;
  case "08":
      $timezone = "Europe/Lisbon";
      break;
  case "09":
      $timezone = "Europe/Lisbon";
      break;
  case "10":
      $timezone = "Atlantic/Madeira";
      break;
  case "11":
      $timezone = "Europe/Lisbon";
      break;
  case "13":
      $timezone = "Europe/Lisbon";
      break;
  case "14":
      $timezone = "Europe/Lisbon";
      break;
  case "16":
      $timezone = "Europe/Lisbon";
      break;
  case "17":
      $timezone = "Europe/Lisbon";
      break;
  case "18":
      $timezone = "Europe/Lisbon";
      break;
  case "19":
      $timezone = "Europe/Lisbon";
      break;
  case "20":
      $timezone = "Europe/Lisbon";
      break;
  case "21":
      $timezone = "Europe/Lisbon";
      break;
  case "22":
      $timezone = "Europe/Lisbon";
      break;
  case "23":
      $timezone = "Atlantic/Azores";
      break;
  } 
  break;
case "RU":
    switch ($region) { 
  case "01":
      $timezone = "Europe/Volgograd";
      break;
  case "02":
      $timezone = "Asia/Irkutsk";
      break;
  case "03":
      $timezone = "Asia/Novokuznetsk";
      break;
  case "04":
      $timezone = "Asia/Novosibirsk";
      break;
  case "05":
      $timezone = "Asia/Vladivostok";
      break;
  case "06":
      $timezone = "Europe/Moscow";
      break;
  case "07":
      $timezone = "Europe/Volgograd";
      break;
  case "08":
      $timezone = "Europe/Yekaterinburg";
      break;
  case "09":
      $timezone = "Europe/Moscow";
      break;
  case "10":
      $timezone = "Europe/Moscow";
      break;
  case "11":
      $timezone = "Asia/Irkutsk";
      break;
  case "12":
      $timezone = "Europe/Volgograd";
      break;
  case "13":
      $timezone = "Asia/Yekaterinburg";
      break;
  case "14":
      $timezone = "Asia/Irkutsk";
      break;
  case "15":
      $timezone = "Asia/Anadyr";
      break;
  case "16":
      $timezone = "Europe/Samara";
      break;
  case "17":
      $timezone = "Europe/Volgograd";
      break;
  case "18":
      $timezone = "Asia/Krasnoyarsk";
      break;
  case "20":
      $timezone = "Asia/Irkutsk";
      break;
  case "21":
      $timezone = "Europe/Moscow";
      break;
  case "22":
      $timezone = "Europe/Volgograd";
      break;
  case "23":
      $timezone = "Europe/Kaliningrad";
      break;
  case "24":
      $timezone = "Europe/Volgograd";
      break;
  case "25":
      $timezone = "Europe/Moscow";
      break;
  case "26":
      $timezone = "Asia/Kamchatka";
      break;
  case "27":
      $timezone = "Europe/Volgograd";
      break;
  case "28":
      $timezone = "Europe/Moscow";
      break;
  case "29":
      $timezone = "Asia/Novokuznetsk";
      break;
  case "30":
      $timezone = "Asia/Sakhalin";
      break;
  case "31":
      $timezone = "Asia/Krasnoyarsk";
      break;
  case "32":
      $timezone = "Asia/Omsk";
      break;
  case "33":
      $timezone = "Europe/Samara";
      break;
  case "34":
      $timezone = "Asia/Yekaterinburg";
      break;
  case "35":
      $timezone = "Asia/Yekaterinburg";
      break;
  case "36":
      $timezone = "Asia/Magadan";
      break;
  case "37":
      $timezone = "Europe/Moscow";
      break;
  case "38":
      $timezone = "Europe/Volgograd";
      break;
  case "39":
      $timezone = "Asia/Krasnoyarsk";
      break;
  case "40":
      $timezone = "Asia/Yekaterinburg";
      break;
  case "41":
      $timezone = "Europe/Moscow";
      break;
  case "42":
      $timezone = "Europe/Moscow";
      break;
  case "43":
      $timezone = "Europe/Moscow";
      break;
  case "44":
      $timezone = "Asia/Magadan";
      break;
  case "45":
      $timezone = "Europe/Samara";
      break;
  case "46":
      $timezone = "Europe/Samara";
      break;
  case "47":
      $timezone = "Europe/Moscow";
      break;
  case "48":
      $timezone = "Europe/Moscow";
      break;
  case "49":
      $timezone = "Europe/Moscow";
      break;
  case "50":
      $timezone = "Asia/Yekaterinburg";
      break;
  case "51":
      $timezone = "Europe/Moscow";
      break;
  case "52":
      $timezone = "Europe/Moscow";
      break;
  case "53":
      $timezone = "Asia/Novosibirsk";
      break;
  case "54":
      $timezone = "Asia/Omsk";
      break;
  case "55":
      $timezone = "Europe/Samara";
      break;
  case "56":
      $timezone = "Europe/Moscow";
      break;
  case "57":
      $timezone = "Europe/Samara";
      break;
  case "58":
      $timezone = "Asia/Yekaterinburg";
      break;
  case "59":
      $timezone = "Asia/Vladivostok";
      break;
  case "60":
      $timezone = "Europe/Kaliningrad";
      break;
  case "61":
      $timezone = "Europe/Volgograd";
      break;
  case "62":
      $timezone = "Europe/Moscow";
      break;
  case "63":
      $timezone = "Asia/Yakutsk";
      break;
  case "64":
      $timezone = "Asia/Sakhalin";
      break;
  case "65":
      $timezone = "Europe/Samara";
      break;
  case "66":
      $timezone = "Europe/Moscow";
      break;
  case "67":
      $timezone = "Europe/Volgograd";
      break;
  case "68":
      $timezone = "Europe/Volgograd";
      break;
  case "69":
      $timezone = "Europe/Moscow";
      break;
  case "70":
      $timezone = "Europe/Volgograd";
      break;
  case "71":
      $timezone = "Asia/Yekaterinburg";
      break;
  case "72":
      $timezone = "Europe/Moscow";
      break;
  case "73":
      $timezone = "Europe/Samara";
      break;
  case "74":
      $timezone = "Asia/Yakutsk";
      break;
  case "75":
      $timezone = "Asia/Novosibirsk";
      break;
  case "76":
      $timezone = "Europe/Moscow";
      break;
  case "77":
      $timezone = "Europe/Moscow";
      break;
  case "78":
      $timezone = "Asia/Yekaterinburg";
      break;
  case "79":
      $timezone = "Asia/Krasnoyarsk";
      break;
  case "80":
      $timezone = "Asia/Yekaterinburg";
      break;
  case "81":
      $timezone = "Europe/Samara";
      break;
  case "82":
      $timezone = "Asia/Irkutsk";
      break;
  case "83":
      $timezone = "Europe/Moscow";
      break;
  case "84":
      $timezone = "Europe/Volgograd";
      break;
  case "85":
      $timezone = "Europe/Moscow";
      break;
  case "86":
      $timezone = "Europe/Moscow";
      break;
  case "87":
      $timezone = "Asia/Omsk";
      break;
  case "88":
      $timezone = "Europe/Moscow";
      break;
  case "89":
      $timezone = "Asia/Vladivostok";
      break;
  case "90":
      $timezone = "Asia/Yekaterinburg";
      break;
  case "91":
      $timezone = "Asia/Krasnoyarsk";
      break;
  case "92":
      $timezone = "Asia/Kamchatka";
      break;
  case "93":
      $timezone = "Asia/Irkutsk";
      break;
  case "CI":
      $timezone = "Europe/Volgograd";
      break;
  case "JA":
      $timezone = "Asia/Sakhalin";
      break;
  } 
  break;
case "UA":
    switch ($region) { 
  case "01":
      $timezone = "Europe/Kiev";
      break;
  case "02":
      $timezone = "Europe/Kiev";
      break;
  case "03":
      $timezone = "Europe/Uzhgorod";
      break;
  case "04":
      $timezone = "Europe/Zaporozhye";
      break;
  case "05":
      $timezone = "Europe/Zaporozhye";
      break;
  case "06":
      $timezone = "Europe/Uzhgorod";
      break;
  case "07":
      $timezone = "Europe/Zaporozhye";
      break;
  case "08":
      $timezone = "Europe/Simferopol";
      break;
  case "09":
      $timezone = "Europe/Kiev";
      break;
  case "10":
      $timezone = "Europe/Zaporozhye";
      break;
  case "11":
      $timezone = "Europe/Simferopol";
      break;
  case "12":
      $timezone = "Europe/Kiev";
      break;
  case "13":
      $timezone = "Europe/Kiev";
      break;
  case "14":
      $timezone = "Europe/Zaporozhye";
      break;
  case "15":
      $timezone = "Europe/Uzhgorod";
      break;
  case "16":
      $timezone = "Europe/Zaporozhye";
      break;
  case "17":
      $timezone = "Europe/Simferopol";
      break;
  case "18":
      $timezone = "Europe/Zaporozhye";
      break;
  case "19":
      $timezone = "Europe/Kiev";
      break;
  case "20":
      $timezone = "Europe/Simferopol";
      break;
  case "21":
      $timezone = "Europe/Kiev";
      break;
  case "22":
      $timezone = "Europe/Uzhgorod";
      break;
  case "23":
      $timezone = "Europe/Kiev";
      break;
  case "24":
      $timezone = "Europe/Uzhgorod";
      break;
  case "25":
      $timezone = "Europe/Uzhgorod";
      break;
  case "26":
      $timezone = "Europe/Zaporozhye";
      break;
  case "27":
      $timezone = "Europe/Kiev";
      break;
  } 
  break;
case "UZ":
    switch ($region) { 
  case "01":
      $timezone = "Asia/Tashkent";
      break;
  case "02":
      $timezone = "Asia/Samarkand";
      break;
  case "03":
      $timezone = "Asia/Tashkent";
      break;
  case "05":
      $timezone = "Asia/Samarkand";
      break;
  case "06":
      $timezone = "Asia/Tashkent";
      break;
  case "07":
      $timezone = "Asia/Samarkand";
      break;
  case "08":
      $timezone = "Asia/Samarkand";
      break;
  case "09":
      $timezone = "Asia/Samarkand";
      break;
  case "10":
      $timezone = "Asia/Samarkand";
      break;
  case "12":
      $timezone = "Asia/Samarkand";
      break;
  case "13":
      $timezone = "Asia/Tashkent";
      break;
  case "14":
      $timezone = "Asia/Tashkent";
      break;
  } 
  break;
case "SH":
    $timezone = "Atlantic/St_Helena";
    break;
case "CC":
    $timezone = "Indian/Cocos";
    break;
case "TF":
    $timezone = "Indian/Kerguelen";
    break;
case "SJ":
    $timezone = "Arctic/Longyearbyen";
    break;
case "GS":
    $timezone = "Atlantic/South_Georgia";
    break;
case "CX":
    $timezone = "Indian/Christmas";
    break;
case "PN":
    $timezone = "Pacific/Pitcairn";
    break;
case "MF":
    $timezone = "America/Marigot";
    break;
case "BL":
    $timezone = "America/St_Barthelemy";
    break;
case "EH":
    $timezone = "Africa/El_Aaiun";
    break;
case "TL":
    $timezone = "Asia/Dili";
    break;
case "PF":
    $timezone = "Pacific/Marquesas";
    break;
case "SX":
    $timezone = "America/Curacao";
    break;
case "BQ":
    $timezone = "America/Curacao";
    break;
case "CW":
    $timezone = "America/Curacao";
    break;
  } 
  return $timezone; 
} 
?> 
