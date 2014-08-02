<?

namespace Lightning\View;

class Field {
    public static function datePop($field, $value, $allow_zero, $first_year = 0){
        if(!$allow_zero && ($value == 0 || $value == '')){
            $date = array(date("m"),date("d"),date("Y"));
        } else $date = explode("/",jdtogregorian($value));
        $output = self::monthPop($field."_m",$date[0],$allow_zero);
        $output .= " / ";
        $output .= self::dayPop($field."_d",$date[1],$allow_zero);
        $output .= " / ";
        $output .= self::yearPop($field."_y",$date[2],$allow_zero, $first_year);
        return $output;
    }

    public static function timePop($field, $value, $allow_zero){
        if(!$allow_zero && empty($value)){
            $time = explode("/",date("h/i/a",time()));
            $h = $time[0];
            $i = $time[1];
            $a = $time[2];
            if($a == "PM") $h += 12;
            $value = ($h*60)+$i;
        } else {
            $i = $value%60;
            $h = ($value-$i)/60;
            if($h > 12){
                $a = "PM";
                $h -= 12;
            } else {
                $a = "AM";
            }
        }

        $output = self::hourPop($field."_h", $h, $allow_zero) . ':';
        $output .= self::minutePop($field . '_i', ($value==0 || $value='') ? '' : $i, $allow_zero);
        $output .= ' ' . self::APPop($field . '_a', $a, false);
        return $output;
    }

    public static function dateTimePop($field, $value, $allow_zero, $first_year = 0){
        if(!$allow_zero && ($value == 0 || $value == ''))
            $value = time();
        $time = explode("/",date("m/d/Y/h/i/s/a",$value));
        $output = self::monthPop($field."_m",$time[0],$allow_zero) . ' / ';
        $output .= self::dayPop($field."_d",$time[1],$allow_zero) . ' / ';
        $output .= self::yearPop($field."_y",$time[2],$allow_zero, $first_year) . ' at ';
        $output .= self::hourPop($field."_h", $time[3], $allow_zero) . ':';
        $output .= self::minutePop($field."_i", $time[4], $allow_zero) . ' ';
        $output .= self::APPop($field."_a", $time[6], $allow_zero);
        return $output;
    }

    public static function hourPop($field, $value="", $allow_zero = false){
        $output = "<select name='$field'>";
        if($allow_zero)
            $output .= "<option value=''></option>";
        for($i = 1; $i <= 12; $i++){
            $output .= "<option value='{$i}'";
            if($value == $i)
                $output .= " SELECTED";
            $output .= ">{$i}</option>";
        }
        $output .= "</select>";
        return $output;
    }

    public static function minutePop($field, $value="", $allow_zero = false){
        $output = "<select name='$field'>";
        if($allow_zero)
            $output .= "<option value=''></option>";
        for($i = 0; $i <= 45; $i=$i+15){
            $output .= "<option value='{$i}'";
            if($value >= $i && $value < $i+15)
                $output .= " SELECTED";
            $output .= ">".str_pad($i,2,0,STR_PAD_LEFT)."</option>";
        }
        $output .= "</select>";
        return $output;
    }

    public static function APPop($field, $value="", $allow_zero = false){
        $output = "<select name='$field'>";
        if($allow_zero)
            $output .= "<option value=''></option>";
        $output .= "<option value='AM'";
        if(strtoupper($value) == "AM") $output .= " SELECTED";
        $output .= ">AM</option>";
        $output .= "<option value='PM'";
        if(strtoupper($value) == "PM") $output .= " SELECTED";
        $output .= ">PM</option>";
        $output .= "</select>";
        return $output;
    }

    public static function dayPop($field, $day=0, $allow_zero = false){
        $output = "<select name='$field' id='$field'>";
        if($allow_zero)
            $output .= "<option value=''></option>";
        else if ($day==0)
            $day = date("d");
        for($i = 1; $i <= 31; $i++){
            $output .= "<option value='{$i}'";
            if($day == $i)
                $output .= " SELECTED";
            $output .= ">{$i}</option>";
        }
        $output .= "</select>";
        return $output;
    }

    public static function monthPop($field, $month=0, $allow_zero = false, $js = ""){
        $output = "<select name='$field' id='$field' {$js}>";
        $months = array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
        if($allow_zero)
            $output .= "<option value=''></option>";
        else if ($month==0)
            $month = date("m");
        for($i = 1; $i <= 12; $i++){
            $output .= "<option value='{$i}'";
            if($month == $i)
                $output .= " SELECTED";
            $output .= ">{$months[$i-1]}</option>";
        }
        $output .= "</select>";
        return $output;
    }

    public static function yearPop($field, $year=0, $allow_zero = false, $first_year=0, $js = ""){
        $output = "<select name='$field' id='$field' {$js}>";
        if($allow_zero)
            $output .= "<option value=''></option>";
        else if ($year==0)
            $year = date("Y");
        $start_year = date("Y")-1;
        if($year>0) $start_year = min($year,$start_year);
        if($first_year > 0)$start_year = min($first_year,$start_year);
        for($i = $start_year; $i <= date("Y", time())+10; $i++){
            $output .= "<option value='{$i}'";
            if($year == $i)
                $output .= " SELECTED";
            $output .= ">{$i}</option>";
        }
        $output .= "</select>";
        return $output;
    }

    public function statePop($name, $default = '', $extra = '') {
        $output = '<select name="' . $name . '" id="' . $name . '" ' . $extra . '>';
        $output .= '<option value=""></option>';
        $states = array('AL' => 'Alabama','AK' => 'Alaska','AZ' => 'Arizona','AR' => 'Arkansas','CA' => 'California','CO' => 'Colorado','CT' => 'Connecticut','DE' => 'Delaware','DC' => 'District of Columbia','FL' => 'Florida','GA' => 'Georgia','HI' => 'Hawaii','ID' => 'Idaho','IL' => 'Illinois','IN' => 'Indiana','IA' => 'Iowa','KS' => 'Kansas','KY' => 'Kentucky','LA' => 'Louisiana','ME' => 'Maine','MD' => 'Maryland','MA' => 'Massachusetts','MI' => 'Michigan','MN' => 'Minnesota','MS' => 'Mississippi','MO' => 'Missouri','MT' => 'Montana','NE' => 'Nebraska','NV' => 'Nevada','NH' => 'New Hampshire','NJ' => 'New Jersey','NM' => 'New Mexico','NY' => 'New York','NC' => 'North Carolina','ND' => 'North Dakota','OH' => 'Ohio','OK' => 'Oklahoma','OR' => 'Oregon','PA' => 'Pennsylvania','RI' => 'Rhode Island','SC' => 'South Carolina','SD' => 'South Dakota','TN' => 'Tennessee','TX' => 'Texas','UT' => 'Utah','VT' => 'Vermont','VA' => 'Virginia','WA' => 'Washington','WV' => 'West Virginia','WI' => 'Wisconsin','WY' => 'Wyoming');
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
}
