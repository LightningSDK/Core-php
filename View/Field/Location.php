<?php

namespace Lightning\View\Field;

use Lightning\Tools\Database;
use Lightning\View\Field;

class Location {
    /**
     * Create a query condition that will select all latitudes and longitudes within a range.
     *
     * @param string $zip
     *   A US zip code.
     * @param $miles
     *   The distance to search in miles.
     *
     * @return string
     *   The query condition.
     */
    public static function zipQuery($zip, $miles) {
        if ($start = self::zipToCoordinates($zip)) {
            return self::areaQuery($start['lat'], $start['long'], $miles);
        }
        return [];
    }

    /**
     * Convert a zipcode to a long/lat array.
     *
     * @param string $zip
     *   The zip code.
     *
     * @return array
     *   The longitude and latitude.
     */
    public static function zipToCoordinates($zip) {
        return Database::getInstance()->selectRow('zipcode', ['zip' => $zip]);
    }

    /**
     * Create a query in a range of longitudes and latitudes within a distance from a point.
     *
     * @param float $lat
     *   Starting latitude
     * @param float $long
     *   Starting longitude
     * @param float $miles
     *   Number of miles to search
     *
     * @return array
     *   The query condition.
     */
    public static function areaQuery($lat, $long, $miles) {
        //earth's radius in miles
        $r = 3959;

        //compute max and min latitudes / longitudes for search square
        $latN = rad2deg(asin(sin(deg2rad($lat)) * cos($miles / $r) + cos(deg2rad($lat)) * sin($miles / $r) * cos(deg2rad(0))));
        $latS = rad2deg(asin(sin(deg2rad($lat)) * cos($miles / $r) + cos(deg2rad($lat)) * sin($miles / $r) * cos(deg2rad(180))));
        $lonE = rad2deg(deg2rad($long) + atan2(sin(deg2rad(90)) * sin($miles / $r) * cos(deg2rad($lat)), cos($miles / $r) - sin(deg2rad($lat)) * sin(deg2rad($latN))));
        $lonW = rad2deg(deg2rad($long) + atan2(sin(deg2rad(270)) * sin($miles / $r) * cos(deg2rad($lat)), cos($miles / $r) - sin(deg2rad($lat)) * sin(deg2rad($latN))));

        return [
            'lat' => ['BETWEEN', $latS, $latN],
            'long' => ['BETWEEN', $lonW, $lonE],
        ];
    }

    /**
     * Create a popup select field for US states.
     *
     * @param string $name
     *   The field name.
     * @param string $default
     *   The default selection.
     * @param string $extra
     *   Extra parameter data.
     *
     * @return string
     *   The full HTML.
     */
    public static function statePop($name, $default = '', $extra = '') {
        $output = '<select name="' . $name . '" id="' . $name . '" ' . $extra . '>';
        $output .= '<option value=""></option>';
        $states = self::getStateOptions();
        foreach($states as $abbr => $state) {
            if ($default == $abbr) {
                $output .= '<option value="' . $abbr . '" SELECTED>' . $state . '</option>';
            }
            else {
                $output .= '<option value="' . $abbr . '">' . $state . '</option>';
            }
        }
        $output .= '</select>';
        return $output;
    }

    public static function getStateOptions($country = 'US') {
        static $states = [
            'US' => [
                'AA' => 'AA', 'AE' => 'AE', 'AP' => 'AP',
                'AL' => 'Alabama', 'AK' => 'Alaska','AZ' => 'Arizona','AR' => 'Arkansas','CA' => 'California',
                'CO' => 'Colorado','CT' => 'Connecticut','DE' => 'Delaware','DC' => 'District of Columbia',
                'FL' => 'Florida','GA' => 'Georgia','HI' => 'Hawaii','ID' => 'Idaho','IL' => 'Illinois',
                'IN' => 'Indiana','IA' => 'Iowa','KS' => 'Kansas','KY' => 'Kentucky','LA' => 'Louisiana',
                'ME' => 'Maine','MD' => 'Maryland','MA' => 'Massachusetts','MI' => 'Michigan',
                'MN' => 'Minnesota','MS' => 'Mississippi','MO' => 'Missouri','MT' => 'Montana',
                'NE' => 'Nebraska','NV' => 'Nevada','NH' => 'New Hampshire','NJ' => 'New Jersey',
                'NM' => 'New Mexico','NY' => 'New York','NC' => 'North Carolina','ND' => 'North Dakota',
                'OH' => 'Ohio','OK' => 'Oklahoma','OR' => 'Oregon','PA' => 'Pennsylvania',
                'RI' => 'Rhode Island','SC' => 'South Carolina','SD' => 'South Dakota',
                'TN' => 'Tennessee','TX' => 'Texas','UT' => 'Utah','VT' => 'Vermont','VA' => 'Virginia',
                'WA' => 'Washington','WV' => 'West Virginia','WI' => 'Wisconsin','WY' => 'Wyoming'
            ],
            'CA' => [
                'AB' => 'Alberta', 'BC' => 'British Columbia', 'MB' => 'Manitoba', 'NB' => 'New Brunswick',
                'NL' => 'Newfoundland and Labrador', 'NS' => 'Nova Scotia', 'NT' => 'Northwest Territories',
                'NU' => 'Nunavut', 'ON' => 'Ontario', 'PE' => 'Prince Edward Island', 'QC' => 'Quebec',
                'SK' => 'Saskatchewan', 'YT' => 'Yukon'
            ],
            'MX' => [
                'AG' => 'Aguascalientes', 'BC' => 'Baja California', 'BS' => 'Baja California Sur',
                'CM' => 'Campeche', 'CS' => 'Chiapas', 'CH' => 'Chihuahua', 'CO' => 'Coahuila',
                'CL' => 'Colima', 'DF' => 'Mexico City', 'DG' => 'Durango', 'GT' => 'Guanajuato',
                'GR' => 'Guerrero', 'HG' => 'Hidalgo', 'JA' => 'Jalisco', 'EM' => 'México',
                'MI' => 'Michoacán', 'MO' => 'Morelos', 'NA' => 'Nayarit', 'NL' => 'Nuevo León',
                'OA' => 'Oaxaca', 'PU' => 'Puebla', 'QT' => 'Querétaro', 'QR' => 'Quintana Roo',
                'SL' => 'San Luis Potosí', 'SI' => 'Sinaloa', 'SO' => 'Sonora', 'TB' => 'Tabasco',
                'TM' => 'Tamaulipas', 'TL' => 'Tlaxcala', 'VE' => 'Veracruz', 'YU' => 'Yucatán',
                'ZA' => 'Zacatecas',
            ],
            'AU' => [
                'NSW' => 'New South Wales', 'QLD' => 'Queensland', 'SA' => 'South Australia',
                'TAS' => 'Tasmania', 'VIC' => 'Victoria', 'WA' => 'Western Australia',
                'ACT' => 'Australian Capital Territory', 'NT' => 'Northern Territory',
            ]
        ];

        if ($country === null) {
            return $states;
        } elseif (isset($states[$country])) {
            return $states[$country];
        } else {
            return null;
        }
    }

    /**
     * Create a country selection popup.
     *
     * @param string $id
     *   The name of the field.
     * @param string $extra
     *   Extra parameters for the HTML.
     * @param string $default
     *   The default selection.
     *
     * @return string
     *   The full HTML code.
     */
    public static function countryPop($id, $extra="", $default="US") {
        $output = "<select name='{$id}' id='{$id}' {$extra}>";
        $output .= "<option value=''></option>";
        foreach(self::getCountryOptions() as $key=>$value) {
            $output .= "<option value='$key'";
            if ($key == $default)
                $output .= " selected='selected' ";
            $output .= ">$value</option>";
        }
        $output .= "</select>";
        return $output;
    }

    public static function getCountryOptions() {
        return [
            "US"=>"United States","CA"=>"Canada","UK"=>"United Kingdom","AC"=>"Ascension Island",
            "AF"=>"Afghanistan","AL"=>"Albania","DZ"=>"Algeria","AD"=>"Andorra","AO"=>"Angola",
            "AI"=>"Anguilla","AQ"=>"Antarctica","AG"=>"Antigua And Barbuda","AR"=>"Argentina Republic",
            "AM"=>"Armenia","AW"=>"Aruba","AU"=>"Australia","AT"=>"Austria","AZ"=>"Azerbaijan",
            "BS"=>"Bahamas","BH"=>"Bahrain","BD"=>"Bangladesh","BB"=>"Barbados","BY"=>"Belarus",
            "BE"=>"Belgium","BZ"=>"Belize","BJ"=>"Benin","BM"=>"Bermuda","BT"=>"Bhutan",
            "BO"=>"Bolivia","BA"=>"Bosnia And Herzegovina","BW"=>"Botswana","BV"=>"Bouvet Island",
            "BR"=>"Brazil","IO"=>"British Indian Ocean Terr","VG"=>"British Virgin Islands",
            "BN"=>"Brunei Darussalam","BG"=>"Bulgaria","BF"=>"Burkina Faso","BI"=>"Burundi",
            "KH"=>"Cambodia","CM"=>"Cameroon","CV"=>"Cape Verde","KY"=>"Cayman Islands",
            "CF"=>"Central African Republic","TD"=>"Chad","CL"=>"Chile","CN"=>"China",
            "CX"=>"Christmas Islands","CC"=>"Cocos Islands","CO"=>"Colombia","KM"=>"Comoras",
            "CG"=>"Congo","CD"=>"Congo, Democratic Republic","CK"=>"Cook Islands","CR"=>"Costa Rica",
            "CI"=>"Cote D Ivoire","HR"=>"Croatia","CU"=>"Cuba","CY"=>"Cyprus","CZ"=>"Czech Republic",
            "DK"=>"Denmark","DJ"=>"Djibouti","DM"=>"Dominica","DO"=>"Dominican Republic",
            "TP"=>"East Timor","EC"=>"Ecuador","EG"=>"Egypt","SV"=>"El Salvador","GQ"=>"Equatorial Guinea",
            "EE"=>"Estonia","ET"=>"Ethiopia","FK"=>"Falkland Islands","FO"=>"Faroe Islands","FJ"=>"Fiji",
            "FI"=>"Finland","FR"=>"France","FX"=>"France Metropolitan","GF"=>"French Guiana",
            "PF"=>"French Polynesia","TF"=>"French Southern Territories","GA"=>"Gabon","GM"=>"Gambia",
            "GE"=>"Georgia","DE"=>"Germany","GH"=>"Ghana","GI"=>"Gibralter","GR"=>"Greece",
            "GL"=>"Greenland","GD"=>"Grenada","GP"=>"Guadeloupe","GU"=>"Guam","GT"=>"Guatemala",
            "GN"=>"Guinea","GW"=>"Guinea-bissau","GY"=>"Guyana","HT"=>"Haiti",
            "HM"=>"Heard & Mcdonald Island","HN"=>"Honduras","HK"=>"Hong Kong","HU"=>"Hungary",
            "IS"=>"Iceland","IN"=>"India","ID"=>"Indonesia","IR"=>"Iran, Islamic Republic Of",
            "IQ"=>"Iraq","IE"=>"Ireland","IM"=>"Isle Of Man","IL"=>"Israel","IT"=>"Italy",
            "JM"=>"Jamaica","JP"=>"Japan","JO"=>"Jordan","KZ"=>"Kazakhstan","KE"=>"Kenya",
            "KI"=>"Kiribati","KP"=>"Korea, Dem. Peoples Rep Of","KR"=>"Korea, Republic Of",
            "KW"=>"Kuwait","KG"=>"Kyrgyzstan","LA"=>"Lao People's Dem. Republic","LV"=>"Latvia",
            "LB"=>"Lebanon","LS"=>"Lesotho","LR"=>"Liberia","LY"=>"Libyan Arab Jamahiriya",
            "LI"=>"Liechtenstein","LT"=>"Lithuania","LU"=>"Luxembourg","MO"=>"Macao","MK"=>"Macedonia",
            "MG"=>"Madagascar","MW"=>"Malawi","MY"=>"Malaysia","MV"=>"Maldives","ML"=>"Mali",
            "MT"=>"Malta","MH"=>"Marshall Islands","MQ"=>"Martinique","MR"=>"Mauritania",
            "MU"=>"Mauritius","YT"=>"Mayotte","MX"=>"Mexico","FM"=>"Micronesia",
            "MD"=>"Moldava, Republic Of","MC"=>"Monaco","MN"=>"Mongolia","ME"=>"Montenegro",
            "MS"=>"Montserrat","MA"=>"Morocco","MZ"=>"Mozambique","MM"=>"Myanmar","NA"=>"Namibia",
            "NR"=>"Nauru","NP"=>"Nepal","AN"=>"Netherlands Antilles","NL"=>"Netherlands, The",
            "NC"=>"New Caledonia","NZ"=>"New Zealand","NI"=>"Nicaragua","NE"=>"Niger","NG"=>"Nigeria",
            "NU"=>"Niue","NF"=>"Norfolk Island","MP"=>"Northern Mariana Islands","NO"=>"Norway",
            "OM"=>"Oman","PK"=>"Pakistan","PW"=>"Palau","PS"=>"Palastine","PA"=>"Panama",
            "PG"=>"Papua New Guinea","PY"=>"Paraguay","PE"=>"Peru","PH"=>"Phillipines",
            "PN"=>"Pitcairn","PL"=>"Poland","PT"=>"Portugal","PR"=>"Puerto Rico","QA"=>"Qatar",
            "RE"=>"Reunion","RO"=>"Romania","RU"=>"Russian Federation","RW"=>"Rwanda","WS"=>"Samoa",
            "SM"=>"San Marino","ST"=>"Sao Tome/principe","SA"=>"Saudi Arabia","SN"=>"Senegal",
            "SP"=>"Serbia","SC"=>"Seychelles","SL"=>"Sierra Leone","SG"=>"Singapore","SK"=>"Slovakia",
            "SI"=>"Slovenia","SB"=>"Solomon Islands","SO"=>"Somalia","AS"=>"Somoa,gilbert,ellice Islands",
            "ZA"=>"South Africa","GS"=>"South Georgia, South Sandwich Islands","SU"=>"Soviet Union",
            "ES"=>"Spain","LK"=>"Sri Lanka","SH"=>"St. Helena","KN"=>"St. Kitts And Nevis",
            "LC"=>"St. Lucia","PM"=>"St. Pierre And Miquelon","VC"=>"St. Vincent & The Grenadines",
            "SD"=>"Sudan","SR"=>"Suriname","SJ"=>"Svalbard And Jan Mayen","SZ"=>"Swaziland",
            "SE"=>"Sweden","CH"=>"Switzerland","SY"=>"Syrian Arab Republic","TW"=>"Taiwan",
            "TJ"=>"Tajikistan","TZ"=>"Tanzania, United Republic Of","TH"=>"Thailand","TG"=>"Togo",
            "TK"=>"Tokelau","TO"=>"Tonga","TT"=>"Trinidad And Tobago","TN"=>"Tunisia","TR"=>"Turkey",
            "TM"=>"Turkmenistan","TC"=>"Turks And Calcos Islands","TV"=>"Tuvalu","UG"=>"Uganda",
            "UA"=>"Ukraine","AE"=>"United Arab Emirates","UM"=>"United States Minor Outl.is.",
            "UY"=>"Uruguay","UZ"=>"Uzbekistan","VU"=>"Vanuatu","VA"=>"Vatican City State",
            "VE"=>"Venezuela","VN"=>"Viet Nam","VI"=>"Virgin Islands (USA)",
            "WF"=>"Wallis And Futuna Islands","EH"=>"Western Sahara","YE"=>"Yemen",
            "YU"=>"Yugoslavia","ZR"=>"Zaire","ZM"=>"Zambia","ZW"=>"Zimbabwe"
        ];
    }
}
