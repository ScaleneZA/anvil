<?php
class DateHelper
{
    const SELECT_PAST = 0;
    const SELECT_FUTURE = 1;
    const SELECT_BOTH = 2;
            
    /*
     * Create a date selector which uses javascript to prevent the selection of invalid dates by ensuring that
     * the correct number of days for the currently selected month are shown - not 31 for evey month.
     */
    function createDateSelect($field_name, $enabled=true, $mode = DateHelper::SELECT_FUTURE, $years_to_show = 2, $default_date = null)
    {
        ob_start();
        
        $disabled = '';
        if(!$enabled) {
            $disabled = ' disabled';
        }
        
        echo DateHelper::getDateSelectJavascript();
        
        // get the default date
        if($default_date == null) {
            $default_date = date('Y-m-d');
        }
        $selected_year = substr($default_date, 0, 4);
        $selected_month = substr($default_date, 5, 2);
        $selected_day = substr($default_date, 8, 2);				
        
        // populate the year array
        $year_array = array();		
        $this_year = date('Y');
        
        switch ($mode) {
            case DateHelper::SELECT_PAST:
                $year_array = DateHelper::addPreviousYearsToArray($year_array, $this_year, $years_to_show);
            break;
            case DateHelper::SELECT_FUTURE:
                $year_array = DateHelper::addFollowingYearsToArray($year_array, $this_year, $years_to_show);
            break;
            case DateHelper::SELECT_BOTH:
                $year_array = DateHelper::addPreviousYearsToArray($year_array, $this_year, $years_to_show);
                $year_array = DateHelper::addFollowingYearsToArray($year_array, $this_year, $years_to_show);
            break;		
        }				
        
        // populate the month array
        $month_array = array('01','02','03','04','05','06','07','08','09','10','11','12');
        
        // populate the day array
        $day_array = array();		
        
        // create the elements
        // YEAR
        echo ("<select id=\"{$field_name}['Y']\" name=\"{$field_name}['Y']\" onchange=\"updateDateSelectAndCallAdditionalOnChange('{$field_name}');\" title=\"{$title}\" {$disabled}>");
        foreach ($year_array as $year) {
            echo ("<option value='{$year}'");
            
            if($year == $selected_year) {
                echo (" selected");								
            }
            
            echo (">{$year}</option>");
        }
        echo ("</select>");
        
        // MONTH
        echo ("<select id=\"{$field_name}['m']\" name=\"{$field_name}['m']\" onchange=\"updateDateSelectAndCallAdditionalOnChange('{$field_name}');\" title=\"{$title}\" {$disabled}>");
        foreach ($month_array as $month) {
            echo ("<option value='{$month}'");
            
            if($month == $selected_month) {
                echo (" selected");
            }
            
            echo (">{$month}</option>");
        }
        echo ("</select>");
        
        // DAY
        // to be populated by javascript
        echo ("<select id=\"{$field_name}['d']\" name=\"{$field}['d']\" onchange=\"updateDateSelectAndCallAdditionalOnChange('{$field_name}');\" title=\"{$title}\" {$disabled}>");
        
         $days_in_month = cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year) ; 
        for ($i = 1; $i <= $days_in_month; $i++) {
            
            $day = $i;
            if($day < 10) {
                $day = '0' . $day;
            }
            
            echo ("<option value='{$day}'");
                
            if($i == $selected_day) {
                echo (" selected");
            }
                
                echo (">{$day}</option>");
        }			
    
        echo ("</select>");			
        
        echo ("<input type='hidden' name='{$field_name}' id='{$field_name}' value='{$default_date}'>");
            
        echo ("<script language='javascript'>");		
        echo ("
                function updateDateSelectAndCallAdditionalOnChange(field_name){
                    updateDateSelect(field_name);
        ");

        echo("
                }
            </script>
            ");

        $select = ob_get_contents();
        ob_end_clean();
        return $select;
    }
    
    /**
    * Javascript necessary for the date selector to work
    * This is a seperate function - it is necessary to call this manually when using AJAX.
    */
    function getDateSelectJavascript()
    {
        ob_start();
        // JAVASCRIPT
        echo ("<script language='javascript'>");		
        echo ("


                function updateDateSelect(field_name)
                {					
                    var year = document.getElementById(field_name + \"['Y']\").value;
                    var month =  document.getElementById(field_name + \"['m']\").value - 1;
                    var day =  document.getElementById(field_name + \"['d']\").value;
                    
                    var days_in_month = daysInMonth(month, year);
                    
                    var options_html = '';
                    
                    for(var i = 1; i <= days_in_month; i++ ) {
                        var display_day;
                        if(Number(i) < 10) {
                            display_day = '0' + i;
                        } else {
                            display_day = i;
                        }
                        options_html += \"<option value='\" + display_day + \"'\";
                        
                        if(i == (Number(day))) {
                            options_html += \" selected\";
                        }
                        
                        options_html += \">\" + display_day + \"</option>\\n\";
                    }
                    
                    document.getElementById(field_name + \"['d']\" ).innerHTML = options_html;	

                    // update the hidden element
                    month += 1;
                    if(month < 10) {
                        month = '0' + month;
                    }
                    if(day < 10) {
                        day = '0' + day;
                    }
                    document.getElementById(field_name).value = (year + '-' + month + '-' + day);
                }


                // courtesy of http://snippets.dzone.com/posts/show/2099
                function daysInMonth(iMonth, iYear)
                {
                    return 32 - new Date(iYear, iMonth, 32).getDate();
                }
              ");
        echo ("</script>");					
        
        $script = ob_get_contents();
        ob_end_clean();
        
        return $script;
    }
        
    private function addPreviousYearsToArray($array, $this_year, $years_to_add)
    {
        for($i = ($this_year - $years_to_add); $i <= $this_year; $i++) {
            $array[] = $i;
        }
        
        return $array;
    }
    
    private function addFollowingYearsToArray($array, $this_year, $years_to_add)
    {
        for($i = $this_year; $i <= ($this_year + $years_to_add); $i++) {
            $array[] = $i;
        }
        
        return $array;
    }

}
?>
