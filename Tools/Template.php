<?php

namespace Lightning\Tools;

use Lightning\Tools\Configuration;

/**
 * Class Template
 *
 * The HTML template controller.
 */
class Template extends Singleton {

    private $template = 'template.tpl.php';
    private $template_dir;
    private $page;
    private $vars;

    /**
     * Initialize the template object.
     */
    public function __construct(){
        $this->template_dir = HOME_PATH . '/Source/Templates/';
//    if(Configuration::get('user_mobile')){
//      require_once HOME_PATH . '/include/class_mobile.php';
//      $detect = new \Mobile_Detect();
//      if ($detect->isMobile()){
//        $this->template = 'template_mobile.tpl.php';
//        $this->assign("mobile",true);
//      }
//    }
    }

    public function __get($var){
        if(isset($this->vars[$var]))
            return $this->vars[$var];
        else
            return NULL;
    }

    /**
     * Render a template and it's main page content.
     *
     * @param string $page
     *   The page to render within the template.
     * @param bool $return_as_string
     *   When TRUE, the output will be returned instead of output.
     *
     * @return string
     *   The rendered content.
     */
    public function render($page = "", $return_as_string = FALSE){
        extract($this->vars);
        if(!empty($page)) {
            $this->page = $page;
        }

        if ($return_as_string) {
            // Setup an output buffer
            ob_start();
        }

        include $this->template_dir . $this->template;

        if ($return_as_string) {
            // Setup an output buffer
            return ob_get_clean();
        }
        exit;
    }

    public function set_page($page = ""){
        $this->page = $page;
    }

    public function set_default_page($page) {
        if (empty($this->page)) {
            $this->page = $page;
        }
    }

    public function set($name,$var){
        $this->vars[$name] = $var;
    }

    public function copy($object, $vars){
        foreach($vars as $v){
            $this->set($v, $object->get($v));
        }
    }

    public function setReference($name,&$var){
        $this->vars[$name] =& $var;
    }

    public function setTemplate($template){
        $this->template = $template.".tpl.php";
    }

    public function _include($page){
        extract($this->vars);
        include $this->template_dir.$page.".tpl.php";
    }

    // THIS FUNCTION WILL PRINT A QUESTION MARK WITH A TOOL TIP

    public function help($help_string, $image='/images/qmark.png', $id='', $class='', $url=NULL){
        if($url){
            echo "<a href='{$url}'>";
        }
        echo "<img src='{$image}' border='0' class='help {$class}' id='{$id}' />";
        echo "<div class='tooltip'>{$help_string}</div>";
        if($url){
            echo "</a>";
        }
    }


    // SPECIAL FORM FIELDS

    function get_date($field){
        if($_POST[$field.'_m'] != '' && $_POST[$field.'_d'] != '' && $_POST[$field.'_y'] != '')
            return gregoriantojd($_POST[$field.'_m'], $_POST[$field.'_d'], $_POST[$field.'_y']);
        else
            return 0;
    }

    function get_time($field){
        if($_POST[$field.'_h'] != '')
            $time = ($_POST[$field.'_h']*60)+$_POST[$field.'_m']+(($_POST[$field.'_a']=="PM") ? 720 : 0);
        else
            $time = -1;
        if($time > 1440) $time -= 1440;
        return $time;
    }

    function today(){
        return gregoriantojd (date("m"),date("d"),date("Y"));
    }


    function hour_menu($field, $value="", $allow_zero = false){
        echo "<select name='$field'>";
        if($allow_zero)
            echo "<option value=''></option>";
        for($i = 1; $i <= 12; $i++){
            echo "<option value='{$i}'";
            if($value == $i)
                echo " SELECTED";
            echo ">{$i}</option>";
        }
        echo "</select>";
    }

    function select_country($id, $extra="", $default=""){
        $list = array("US"=>"United States","CA"=>"Canada","UK"=>"United Kingdom","AC"=>"Ascension Island","AF"=>"Afghanistan","AL"=>"Albania","DZ"=>"Algeria","AD"=>"Andorra","AO"=>"Angola","AI"=>"Anguilla","AQ"=>"Antarctica","AG"=>"Antigua And Barbuda","AR"=>"Argentina Republic","AM"=>"Armenia","AW"=>"Aruba","AU"=>"Australia","AT"=>"Austria","AZ"=>"Azerbaijan","BS"=>"Bahamas","BH"=>"Bahrain","BD"=>"Bangladesh","BB"=>"Barbados","BY"=>"Belarus","BE"=>"Belgium","BZ"=>"Belize","BJ"=>"Benin","BM"=>"Bermuda","BT"=>"Bhutan","BO"=>"Bolivia","BA"=>"Bosnia And Herzegovina","BW"=>"Botswana","BV"=>"Bouvet Island","BR"=>"Brazil","IO"=>"British Indian Ocean Terr","VG"=>"British Virgin Islands","BN"=>"Brunei Darussalam","BG"=>"Bulgaria","BF"=>"Burkina Faso","BI"=>"Burundi","KH"=>"Cambodia","CM"=>"Cameroon","CV"=>"Cape Verde","KY"=>"Cayman Islands","CF"=>"Central African Republic","TD"=>"Chad","CL"=>"Chile","CN"=>"China","CX"=>"Christmas Islands","CC"=>"Cocos Islands","CO"=>"Colombia","KM"=>"Comoras","CG"=>"Congo","CD"=>"Congo, Democratic Republic","CK"=>"Cook Islands","CR"=>"Costa Rica","CI"=>"Cote D Ivoire","HR"=>"Croatia","CU"=>"Cuba","CY"=>"Cyprus","CZ"=>"Czech Republic","DK"=>"Denmark","DJ"=>"Djibouti","DM"=>"Dominica","DO"=>"Dominican Republic","TP"=>"East Timor","EC"=>"Ecuador","EG"=>"Egypt","SV"=>"El Salvador","GQ"=>"Equatorial Guinea","EE"=>"Estonia","ET"=>"Ethiopia","FK"=>"Falkland Islands","FO"=>"Faroe Islands","FJ"=>"Fiji","FI"=>"Finland","FR"=>"France","FX"=>"France Metropolitan","GF"=>"French Guiana","PF"=>"French Polynesia","TF"=>"French Southern Territories","GA"=>"Gabon","GM"=>"Gambia","GE"=>"Georgia","DE"=>"Germany","GH"=>"Ghana","GI"=>"Gibralter","GR"=>"Greece","GL"=>"Greenland","GD"=>"Grenada","GP"=>"Guadeloupe","GU"=>"Guam","GT"=>"Guatemala","GN"=>"Guinea","GW"=>"Guinea-bissau","GY"=>"Guyana","HT"=>"Haiti","HM"=>"Heard & Mcdonald Island","HN"=>"Honduras","HK"=>"Hong Kong","HU"=>"Hungary","IS"=>"Iceland","IN"=>"India","ID"=>"Indonesia","IR"=>"Iran, Islamic Republic Of","IQ"=>"Iraq","IE"=>"Ireland","IM"=>"Isle Of Man","IL"=>"Israel","IT"=>"Italy","JM"=>"Jamaica","JP"=>"Japan","JO"=>"Jordan","KZ"=>"Kazakhstan","KE"=>"Kenya","KI"=>"Kiribati","KP"=>"Korea, Dem. Peoples Rep Of","KR"=>"Korea, Republic Of","KW"=>"Kuwait","KG"=>"Kyrgyzstan","LA"=>"Lao People's Dem. Republic","LV"=>"Latvia","LB"=>"Lebanon","LS"=>"Lesotho","LR"=>"Liberia","LY"=>"Libyan Arab Jamahiriya","LI"=>"Liechtenstein","LT"=>"Lithuania","LU"=>"Luxembourg","MO"=>"Macao","MK"=>"Macedonia","MG"=>"Madagascar","MW"=>"Malawi","MY"=>"Malaysia","MV"=>"Maldives","ML"=>"Mali","MT"=>"Malta","MH"=>"Marshall Islands","MQ"=>"Martinique","MR"=>"Mauritania","MU"=>"Mauritius","YT"=>"Mayotte","MX"=>"Mexico","FM"=>"Micronesia","MD"=>"Moldava, Republic Of","MC"=>"Monaco","MN"=>"Mongolia","ME"=>"Montenegro","MS"=>"Montserrat","MA"=>"Morocco","MZ"=>"Mozambique","MM"=>"Myanmar","NA"=>"Namibia","NR"=>"Nauru","NP"=>"Nepal","AN"=>"Netherlands Antilles","NL"=>"Netherlands, The","NC"=>"New Caledonia","NZ"=>"New Zealand","NI"=>"Nicaragua","NE"=>"Niger","NG"=>"Nigeria","NU"=>"Niue","NF"=>"Norfolk Island","MP"=>"Northern Mariana Islands","NO"=>"Norway","OM"=>"Oman","PK"=>"Pakistan","PW"=>"Palau","PS"=>"Palastine","PA"=>"Panama","PG"=>"Papua New Guinea","PY"=>"Paraguay","PE"=>"Peru","PH"=>"Phillipines","PN"=>"Pitcairn","PL"=>"Poland","PT"=>"Portugal","PR"=>"Puerto Rico","QA"=>"Qatar","RE"=>"Reunion","RO"=>"Romania","RU"=>"Russian Federation","RW"=>"Rwanda","WS"=>"Samoa","SM"=>"San Marino","ST"=>"Sao Tome/principe","SA"=>"Saudi Arabia","SN"=>"Senegal","SP"=>"Serbia","SC"=>"Seychelles","SL"=>"Sierra Leone","SG"=>"Singapore","SK"=>"Slovakia","SI"=>"Slovenia","SB"=>"Solomon Islands","SO"=>"Somalia","AS"=>"Somoa,gilbert,ellice Islands","ZA"=>"South Africa","GS"=>"South Georgia, South Sandwich Islands","SU"=>"Soviet Union","ES"=>"Spain","LK"=>"Sri Lanka","SH"=>"St. Helena","KN"=>"St. Kitts And Nevis","LC"=>"St. Lucia","PM"=>"St. Pierre And Miquelon","VC"=>"St. Vincent & The Grenadines","SD"=>"Sudan","SR"=>"Suriname","SJ"=>"Svalbard And Jan Mayen","SZ"=>"Swaziland","SE"=>"Sweden","CH"=>"Switzerland","SY"=>"Syrian Arab Republic","TW"=>"Taiwan","TJ"=>"Tajikistan","TZ"=>"Tanzania, United Republic Of","TH"=>"Thailand","TG"=>"Togo","TK"=>"Tokelau","TO"=>"Tonga","TT"=>"Trinidad And Tobago","TN"=>"Tunisia","TR"=>"Turkey","TM"=>"Turkmenistan","TC"=>"Turks And Calcos Islands","TV"=>"Tuvalu","UG"=>"Uganda","UA"=>"Ukraine","AE"=>"United Arab Emirates","UM"=>"United States Minor Outl.is.","UY"=>"Uruguay","UZ"=>"Uzbekistan","VU"=>"Vanuatu","VA"=>"Vatican City State","VE"=>"Venezuela","VN"=>"Viet Nam","VI"=>"Virgin Islands (USA)","WF"=>"Wallis And Futuna Islands","EH"=>"Western Sahara","YE"=>"Yemen","YU"=>"Yugoslavia","ZR"=>"Zaire","ZM"=>"Zambia","ZW"=>"Zimbabwe");
        echo "<select name='{$id}' id='{$id}' {$extra}>";
        echo "<option value=''></option>";
        foreach($list as $key=>$value){
            echo "<option value='$key'";
            if($key == $default)
                echo " selected='selected' ";
            echo ">$value</option>";
        }
        echo "</select>";
    }
}
