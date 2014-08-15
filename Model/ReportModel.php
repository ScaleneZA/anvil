<?php
require_once('Model/BaseModel.php');
class ReportModel extends BaseModel
{
    function getSummaryReportData($project_id, $company_id)
    {
        $sql = "
            SELECT SUM(task_user_hours_done.hours) AS hours 
            FROM task_user_hours_done
            INNER JOIN task ON task.id = task_user_hours_done.task_id
            INNER JOIN feature ON feature.id = task.feature_id
            INNER JOIN project ON project.id = feature.project_id
            LEFT JOIN `release` ON `release`.id = feature.release_id
            WHERE project.company_id = {$company_id}
            AND project.id = {$project_id}
        ";
        
        $total_hours = $this->executeQuery($sql);
        $return_array['total_hours'] = $total_hours[0]['hours'] ? $total_hours[0]['hours'] : 0;
        
        $today_hours = $this->executeQuery($sql. " AND task_user_hours_done.date = CURDATE() ");
        $return_array['today_hours'] = $today_hours[0]['hours'] ? $today_hours[0]['hours'] : 0;
        
        require_once('Model/ProjectModel.php');
        $ProjectModel = new ProjectModel();
        $releases = $ProjectModel->getReleaseArray($project_id);

        foreach($releases AS $release_id=>$release_title){
            $release_data = $this->executeQuery($sql. " AND task_user_hours_done.release_id = {$release_id} ORDER BY release.title");
            $return_array['release_data'][$release_id]['total_hours'] = $release_data[0]['hours'] ? $release_data[0]['hours'] : '0';
            $return_array['release_data'][$release_id]['title'] = $release_title;
        }
        
        return $return_array;
    }
    
    function getBurnDownChartData($project_id, $release_id, $company_id)
    {
        return false;
    }
    
    function getHoursComparisonData($project_id, $release_id, $company_id)
    {
        $sql = "
            FROM task_user_hours_done
            INNER JOIN task ON task.id = task_user_hours_done.task_id
            INNER JOIN feature ON feature.id = task.feature_id
            INNER JOIN project ON project.id = feature.project_id
            LEFT JOIN `release` ON `release`.id = task_user_hours_done.release_id
            WHERE project.company_id = {$company_id}
            AND project.id = {$project_id}
            AND task_user_hours_done.release_id = {$release_id}
        ";
        
        $min_max = $this->executeQuery("
            SELECT MIN(task_user_hours_done.date) AS min_date, MAX(task_user_hours_done.date) as max_date ".$sql
            );
            
        $min_date_str = $min_max[0]['min_date'];
        $min_date['year'] = substr($min_date_str, 0, 4);
        $min_date['month'] = substr($min_date_str, 5, 2);
        $min_date['day'] = substr($min_date_str, 8);
        //print_r($min_date);

        $max_date_str = $min_max[0]['max_date'];
        $max_date['year'] = substr($max_date_str, 0, 4);
        $max_date['month'] = substr($max_date_str, 5, 2);
        $max_date['day'] = substr($max_date_str, 8);
        //print_r($max_date);
        //exit;
          
        $start_date = mktime(0,0,0,$min_date['month'],$min_date['day'],$min_date['year']);
        $end_date = mktime(0,0,0,$max_date['month'],$max_date['day'],$max_date['year']);
        
        $difference = $end_date-$start_date; //Calcuates Difference
        $daysago = floor($difference /60/60/24); //Calculates Days Old


        //echo "Total Days Between: ".$daysago;exit;
        
        $date = $start_date;

        $i = 0;
        while ($i <= $daysago) {
            $today = date('Y-m-d',$date);
            $today .= ' 8:00AM';
            $date_array[$today] = 0;
            
            $date = $date + 86400; 
            $i++;
        }
        
        //Do final array assignment
        $total_hours = 0;
        foreach($date_array AS $date=>$hours)
        {
            $tmp_date = substr($date,0,10);
            $hours = $this->executeQuery("
                SELECT SUM(task_user_hours_done.hours) AS hours
                {$sql}
                AND task_user_hours_done.date = '{$tmp_date}'
            ");
         
            $hours = $hours[0]['hours'] ? $hours[0]['hours'] : 0;
            $total_hours += $hours;
            
            $date_array[$date] = $total_hours;
        }
        
        $return_array['actual_data'] = $date_array;
        $return_array['max_hours'] =$total_hours;
        
        //print_r($return_array);exit;
        return $return_array;
    }
    
}