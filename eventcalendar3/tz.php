<?php
/*
Copyright (c) 2006, Alex Tingle.  $Revision: 236 $

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

// Deal with timezones differently, depending upon what PHP has to offer.
if(function_exists('date_default_timezone_get')):

  // PHP5
  function ec3_tz_push($tz)
  {
    $old_tz=date_default_timezone_get();
    date_default_timezone_set($tz);
    return $old_tz;
  }
  function ec3_tz_pop($tz)
  {
    date_default_timezone_set($tz);
  }

elseif(ini_get('safe_mode')):

  // PHP4 safe mode.
  function ec3_tz_push($tz)
  {
    return $tz;
  }
  function ec3_tz_pop($tz)
  {
    // do nothing
  }
  $ec3->tz_disabled=true;
  $ec3->tz=getenv('TZ');

else:

  // PHP4 safe mode OFF 
  function ec3_tz_push($tz)
  {
    $old_tz=getenv('TZ');
    putenv("TZ=$tz");
    return $old_tz;
  }
  function ec3_tz_pop($tz)
  {
    putenv("TZ=$tz");
  }

endif;


/** Converts a WordPress timestamp (string in local time) to Unix time. */
function ec3_to_time($timestamp)
{
  global $ec3;
  // Parse $timestamp and extract the Unix time.
  $old_tz=ec3_tz_push($ec3->tz);
  $unix_time = strtotime($timestamp);
  ec3_tz_pop($old_tz);
  // Unix time is seconds since the epoch (in UTC).
  return $unix_time;
}

/** Converts a WordPress timestamp (string in local time) to
 *  string in formatted UTC. */
function ec3_to_utc($timestamp,$fmt='%Y%m%dT%H%M00Z')
{
  $result = gmstrftime($fmt,ec3_to_time($timestamp));
  return $result;
}

/** Formats a Unix time as a time in the current timezone. */
function ec3_strftime($format,$unix_time=0)
{
  global $ec3;
  if(!$unix_time)
      $unix_time=time();
  // Express the Unix time as a string for timezone $ec3->tz.
  $old_tz=ec3_tz_push($ec3->tz);
  $result = strftime($format,$unix_time);
  ec3_tz_pop($old_tz);
  return $result;
}


$ec3->today=ec3_strftime("%Y-%m-%d 00:00:00");


/** Call from within a <select>. Echos grouped options for all timezones. */
function ec3_get_tz_options($selected='')
{
  /** All possible timezones, by group. */
  $groups = array(
    'Africa' => array(
      'Abidjan',
      'Accra',
      'Addis_Ababa',
      'Algiers',
      'Asmera',
      'Bamako',
      'Bangui',
      'Banjul',
      'Bissau',
      'Blantyre',
      'Brazzaville',
      'Bujumbura',
      'Cairo',
      'Casablanca',
      'Ceuta',
      'Conakry',
      'Dakar',
      'Dar_es_Salaam',
      'Djibouti',
      'Douala',
      'El_Aaiun',
      'Freetown',
      'Gaborone',
      'Harare',
      'Johannesburg',
      'Kampala',
      'Khartoum',
      'Kigali',
      'Kinshasa',
      'Lagos',
      'Libreville',
      'Lome',
      'Luanda',
      'Lubumbashi',
      'Lusaka',
      'Malabo',
      'Maputo',
      'Maseru',
      'Mbabane',
      'Mogadishu',
      'Monrovia',
      'Nairobi',
      'Ndjamena',
      'Niamey',
      'Nouakchott',
      'Ouagadougou',
      'Porto-Novo',
      'Sao_Tome',
      'Timbuktu',
      'Tripoli',
      'Tunis',
      'Windhoek'
    ),
    'America' => array(
      'Adak',
      'Anchorage',
      'Anguilla',
      'Antigua',
      'Araguaina',
      'Argentina/Buenos_Aires',
      'Argentina/Catamarca',
      'Argentina/ComodRivadavia',
      'Argentina/Cordoba',
      'Argentina/Jujuy',
      'Argentina/La_Rioja',
      'Argentina/Mendoza',
      'Argentina/Rio_Gallegos',
      'Argentina/San_Juan',
      'Argentina/Tucuman',
      'Argentina/Ushuaia',
      'Aruba',
      'Asuncion',
      'Atikokan',
      'Atka',
      'Bahia',
      'Barbados',
      'Belem',
      'Belize',
      'Blanc-Sablon',
      'Boa_Vista',
      'Bogota',
      'Boise',
      'Buenos_Aires',
      'Cambridge_Bay',
      'Campo_Grande',
      'Cancun',
      'Caracas',
      'Catamarca',
      'Cayenne',
      'Cayman',
      'Chicago',
      'Chihuahua',
      'Coral_Harbour',
      'Cordoba',
      'Costa_Rica',
      'Cuiaba',
      'Curacao',
      'Danmarkshavn',
      'Dawson',
      'Dawson_Creek',
      'Denver',
      'Detroit',
      'Dominica',
      'Edmonton',
      'Eirunepe',
      'El_Salvador',
      'Ensenada',
      'Fort_Wayne',
      'Fortaleza',
      'Glace_Bay',
      'Godthab',
      'Goose_Bay',
      'Grand_Turk',
      'Grenada',
      'Guadeloupe',
      'Guatemala',
      'Guayaquil',
      'Guyana',
      'Halifax',
      'Havana',
      'Hermosillo',
      'Indiana/Indianapolis',
      'Indiana/Knox',
      'Indiana/Marengo',
      'Indiana/Petersburg',
      'Indiana/Vevay',
      'Indiana/Vincennes',
      'Indianapolis',
      'Inuvik',
      'Iqaluit',
      'Jamaica',
      'Jujuy',
      'Juneau',
      'Kentucky/Louisville',
      'Kentucky/Monticello',
      'Knox_IN',
      'La_Paz',
      'Lima',
      'Los_Angeles',
      'Louisville',
      'Maceio',
      'Managua',
      'Manaus',
      'Martinique',
      'Mazatlan',
      'Mendoza',
      'Menominee',
      'Merida',
      'Mexico_City',
      'Miquelon',
      'Moncton',
      'Monterrey',
      'Montevideo',
      'Montreal',
      'Montserrat',
      'Nassau',
      'New_York',
      'Nipigon',
      'Nome',
      'Noronha',
      'North_Dakota/Center',
      'North_Dakota/New_Salem',
      'Panama',
      'Pangnirtung',
      'Paramaribo',
      'Phoenix',
      'Port-au-Prince',
      'Port_of_Spain',
      'Porto_Acre',
      'Porto_Velho',
      'Puerto_Rico',
      'Rainy_River',
      'Rankin_Inlet',
      'Recife',
      'Regina',
      'Rio_Branco',
      'Rosario',
      'Santiago',
      'Santo_Domingo',
      'Sao_Paulo',
      'Scoresbysund',
      'Shiprock',
      'St_Johns',
      'St_Kitts',
      'St_Lucia',
      'St_Thomas',
      'St_Vincent',
      'Swift_Current',
      'Tegucigalpa',
      'Thule',
      'Thunder_Bay',
      'Tijuana',
      'Toronto',
      'Tortola',
      'Vancouver',
      'Virgin',
      'Whitehorse',
      'Winnipeg',
      'Yakutat',
      'Yellowknife'
    ),
    'Antarctica' => array(
      'Casey',
      'Davis',
      'DumontDUrville',
      'Mawson',
      'McMurdo',
      'Palmer',
      'Rothera',
      'South_Pole',
      'Syowa',
      'Vostok'
    ),
    'Arctic' => array('Longyearbyen'),
    'Asia' => array(
      'Aden',
      'Almaty',
      'Amman',
      'Anadyr',
      'Aqtau',
      'Aqtobe',
      'Ashgabat',
      'Ashkhabad',
      'Baghdad',
      'Bahrain',
      'Baku',
      'Bangkok',
      'Beirut',
      'Bishkek',
      'Brunei',
      'Calcutta',
      'Choibalsan',
      'Chongqing',
      'Chungking',
      'Colombo',
      'Dacca',
      'Damascus',
      'Dhaka',
      'Dili',
      'Dubai',
      'Dushanbe',
      'Gaza',
      'Harbin',
      'Hong_Kong',
      'Hovd',
      'Irkutsk',
      'Istanbul',
      'Jakarta',
      'Jayapura',
      'Jerusalem',
      'Kabul',
      'Kamchatka',
      'Karachi',
      'Kashgar',
      'Katmandu',
      'Krasnoyarsk',
      'Kuala_Lumpur',
      'Kuching',
      'Kuwait',
      'Macao',
      'Macau',
      'Magadan',
      'Makassar',
      'Manila',
      'Muscat',
      'Nicosia',
      'Novosibirsk',
      'Omsk',
      'Oral',
      'Phnom_Penh',
      'Pontianak',
      'Pyongyang',
      'Qatar',
      'Qyzylorda',
      'Rangoon',
      'Riyadh',
      'Saigon',
      'Sakhalin',
      'Samarkand',
      'Seoul',
      'Shanghai',
      'Singapore',
      'Taipei',
      'Tashkent',
      'Tbilisi',
      'Tehran',
      'Tel_Aviv',
      'Thimbu',
      'Thimphu',
      'Tokyo',
      'Ujung_Pandang',
      'Ulaanbaatar',
      'Ulan_Bator',
      'Urumqi',
      'Vientiane',
      'Vladivostok',
      'Yakutsk',
      'Yekaterinburg',
      'Yerevan'
    ),
    'Atlantic' => array(
      'Azores',
      'Bermuda',
      'Canary',
      'Cape_Verde',
      'Faeroe',
      'Jan_Mayen',
      'Madeira',
      'Reykjavik',
      'South_Georgia',
      'St_Helena',
      'Stanley',
    ),
    'Australia' => array(
      'ACT',
      'Adelaide',
      'Brisbane',
      'Broken_Hill',
      'Canberra',
      'Currie',
      'Darwin',
      'Hobart',
      'LHI',
      'Lindeman',
      'Lord_Howe',
      'Melbourne',
      'North',
      'NSW',
      'Perth',
      'Queensland',
      'South',
      'Sydney',
      'Tasmania',
      'Victoria',
      'West',
      'Yancowinna'
    ),
    'Europe' => array(
      'Amsterdam',
      'Andorra',
      'Athens',
      'Belfast',
      'Belgrade',
      'Berlin',
      'Bratislava',
      'Brussels',
      'Bucharest',
      'Budapest',
      'Chisinau',
      'Copenhagen',
      'Dublin',
      'Gibraltar',
      'Guernsey',
      'Helsinki',
      'Isle_of_Man',
      'Istanbul',
      'Jersey',
      'Kaliningrad',
      'Kiev',
      'Lisbon',
      'Ljubljana',
      'London',
      'Luxembourg',
      'Madrid',
      'Malta',
      'Mariehamn',
      'Minsk',
      'Monaco',
      'Moscow',
      'Nicosia',
      'Oslo',
      'Paris',
      'Prague',
      'Riga',
      'Rome',
      'Samara',
      'San_Marino',
      'Sarajevo',
      'Simferopol',
      'Skopje',
      'Sofia',
      'Stockholm',
      'Tallinn',
      'Tirane',
      'Tiraspol',
      'Uzhgorod',
      'Vaduz',
      'Vatican',
      'Vienna',
      'Vilnius',
      'Volgograd',
      'Warsaw',
      'Zagreb',
      'Zaporozhye',
      'Zurich'
    ),
    'Indian' => array(
      'Antananarivo',
      'Chagos',
      'Christmas',
      'Cocos',
      'Comoro',
      'Kerguelen',
      'Mahe',
      'Maldives',
      'Mauritius',
      'Mayotte',
      'Reunion'
    ),
    'Pacific' => array(
      'Apia',
      'Auckland',
      'Chatham',
      'Easter',
      'Efate',
      'Enderbury',
      'Fakaofo',
      'Fiji',
      'Funafuti',
      'Galapagos',
      'Gambier',
      'Guadalcanal',
      'Guam',
      'Honolulu',
      'Johnston',
      'Kiritimati',
      'Kosrae',
      'Kwajalein',
      'Majuro',
      'Marquesas',
      'Midway',
      'Nauru',
      'Niue',
      'Norfolk',
      'Noumea',
      'Pago_Pago',
      'Palau',
      'Pitcairn',
      'Ponape',
      'Port_Moresby',
      'Rarotonga',
      'Saipan',
      'Samoa',
      'Tahiti',
      'Tarawa',
      'Tongatapu',
      'Truk',
      'Wake',
      'Wallis',
      'Yap'
    )
  );

  foreach($groups as $group => $zones)
  {
    echo "<optgroup label='$group'>\n";
    foreach($zones as $z)
    {
      echo "<option value='$group/$z'";
      if($selected=="$group/$z")
        echo ' selected="selected"';
      echo ">$z</option>\n";
    }
    echo '</optgroup>\n';
  }
} // end function ec3_get_tz_options()

?>
