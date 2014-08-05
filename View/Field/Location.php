<?php

namespace Lightning\View\Field;

use Lightning\Tools\Database;
use Lightning\View\Field;

class Location {
    /**
     * Create a query condition that will select all zipcodes within a distance of another zip code.
     *
     * @param string $zip
     *   A US zip code.
     * @param $distance
     *   The distance to search in miles.
     *
     * @return string
     *   The query condition.
     */
    public static function zipQuery($zip, $distance) {
        if($start = Database::getInstance()->assoc1("select * from zipcode where zip = '$zip'")){

            $lat1 = $start['lat'];
            $lon1 = $start['long'];
            //earth's radius in miles
            $r = 3959;

            //compute max and min latitudes / longitudes for search square
            $latN = rad2deg(asin(sin(deg2rad($lat1)) * cos($distance / $r) + cos(deg2rad($lat1)) * sin($distance / $r) * cos(deg2rad(0))));
            $latS = rad2deg(asin(sin(deg2rad($lat1)) * cos($distance / $r) + cos(deg2rad($lat1)) * sin($distance / $r) * cos(deg2rad(180))));
            $lonE = rad2deg(deg2rad($lon1) + atan2(sin(deg2rad(90)) * sin($distance / $r) * cos(deg2rad($lat1)), cos($distance / $r) - sin(deg2rad($lat1)) * sin(deg2rad($latN))));
            $lonW = rad2deg(deg2rad($lon1) + atan2(sin(deg2rad(270)) * sin($distance / $r) * cos(deg2rad($lat1)), cos($distance / $r) - sin(deg2rad($lat1)) * sin(deg2rad($latN))));

            return "(lat <= $latN AND lat >= $latS AND `long` <= $lonE AND `long` >= $lonW)";
        }
        return "";
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
        $states = array(
            'AL' => 'Alabama','AK' => 'Alaska','AZ' => 'Arizona','AR' => 'Arkansas','CA' => 'California',
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
        );
        foreach($states as $abbr => $state){
            if($default == $abbr) {
                $output .= '<option value="' . $abbr . '" SELECTED>' . $state . '</option>';
            }
            else {
                $output .= '<option value="' . $abbr . '">' . $state . '</option>';
            }
        }
        $output .= '</select>';
        return $output;
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
    public static function countryPop($id, $extra="", $default="US"){
        $list = array(
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
        );
        $output = "<select name='{$id}' id='{$id}' {$extra}>";
        $output .= "<option value=''></option>";
        foreach($list as $key=>$value){
            $output .= "<option value='$key'";
            if($key == $default)
                $output .= " selected='selected' ";
            $output .= ">$value</option>";
        }
        $output .= "</select>";
        return $output;
    }
}