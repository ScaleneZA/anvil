<?php
require_once('Model/BaseModel.php');
class ProjectModel extends BaseModel
{
    function saveTaskHours($task_id, $release_id, $user_email, $date, $hours)
    {
        $hours_already_done = $this->executeQuery("
            SELECT id, hours
            FROM task_user_hours_done
            WHERE user_email = '{$user_email}'
            AND task_id = '{$task_id}'
            AND release_id = '{$release_id}'
            AND date = '{$date}'
        ");
        
        if($hours_already_done){
            $this->deleteTaskHours($hours_already_done[0]['id']);
            $hours += $hours_already_done[0]['hours'];
        }
        
        return $this->executeUpdateQuery("
            INSERT INTO task_user_hours_done
            (hours, date, user_email, task_id, release_id)
            VALUES(
                '{$hours}',
                '{$date}',
                '{$user_email}',
                '{$task_id}',
                '{$release_id}'
            )
        ");
    }
    
    function getTaskHours($task_id)
    {
        return $this->executeQuery("
            SELECT task_user_hours_done.*, task.estimated_hours
            FROM task
            LEFT JOIN task_user_hours_done ON task.id = task_user_hours_done.task_id
            WHERE task.id = {$task_id}
            ORDER BY date
        ");
    }
    
    function deleteTaskHours($id)
    {
        return $this->executeUpdateQuery("
            DELETE FROM task_user_hours_done
            WHERE id = {$id};
        ");
    }

    function getNewTasksForRelease($release_id, $task_id_string_exclude)
    {
        $new_tasks = $this->executeQuery("
            SELECT task.id FROM task 
            INNER JOIN feature ON feature.id = task.feature_id
            WHERE feature.release_id = {$release_id}
            AND task.id NOT IN ({$task_id_string_exclude})
        ");
        if(!is_array($new_tasks)){
            return;
        }
        foreach($new_tasks AS $task){
            $return_array[$task['id']] = $this->getTaskInformation($task['id']);
        }
        
        return $return_array;
    }

    function getReleaseTaskTimeStamp($release_id)
    {
        $time_stamp = $this->executeQuery("
            SELECT task_time_stamp
            FROM `release`
            WHERE id = {$release_id}
        ");
        
        return empty($time_stamp[0]['task_time_stamp']) ? '0' : $time_stamp[0]['task_time_stamp'];
    }
    
    function getTeamsForProject($project_id)
    {
        $data_array = $this->executeQuery("
            SELECT team_id AS id
            FROM team_project
            WHERE project_id = {$project_id}
        ");
        
        return $data_array;
    }

    function getReleasesForProject($project_id, $include_date=false, $order_desc=false, $include_archived=true)
    {
        if($include_date){
            $title_string = " CONCAT(title, ' | ', start_date) AS title";
        }else{
            $title_string = " title ";
        }
        
        if(!$include_archived){
            $archive_sql = " AND archived = 0 ";
        }
        
        $sql = "
            SELECT id, {$title_string} FROM `release`
            WHERE project_id = {$project_id}
            {$archive_sql}
            ";
            
        if($order_desc){
            $sql .= " ORDER BY id DESC";
        }
        
        $data_array = $this->executeQuery($sql);
            
        if(is_array($data_array)){
            foreach($data_array AS $field_array){
                $release_array[$field_array['id']] = $field_array['title'];
            }
        }
        return $release_array;
    }

    /*
     * Gets all the projects for a company
     */
    function getProjectArray($company_id, $team_id=null)
    {
        if($team_id){
            require_once("Model/UserModel.php");
            $UserModel = new UserModel();
            $data_array = $UserModel->getProjectsForTeam($team_id);
        }else{
            $data_array = $this->executeQuery("
                SELECT id, title FROM project
                WHERE company_id = {$company_id}
                ORDER BY start_date DESC
                ");
        }

        $project_array = array();

        if(is_array($data_array)){
            foreach($data_array AS $field_array){
                $project_array[$field_array['id']] = $field_array['title'];
            }
        }else{
            $project_array = array();
        }
        return $project_array;
    }

    /*
     * Gets details about a supplied project id.
     */
    function getProjectInformation($project_id)
    {
        $data_array = $this->executeQuery("
            SELECT title, description, start_date, YEAR(start_date) AS start_date_year, MONTH(start_date) AS start_date_month, DAY(start_date) AS start_date_day 
            FROM project
            WHERE id = {$project_id}
            ");

        return $data_array[0];
    }

    /*
     * Gets a 2D array of features and the tasks for those features, for a supplied release id.
     */
    function getFeatureTaskArray($release_id)
    {
        $data_array = $this->executeQuery("
            SELECT feature.id AS feature_id, task.id AS task_id
                FROM feature
            LEFT JOIN task ON task.feature_id = feature.id
            INNER JOIN project ON project.id = feature.project_id
            WHERE feature.release_id = {$release_id}
            AND project.company_id = {$_SESSION['company_id']}
            ORDER BY feature.priority, task.priority
            ");

        $feature_array = array();
        if(is_array($data_array)){
            foreach($data_array AS $field_array){
                $feature_array[$field_array['feature_id']][] = $field_array['task_id'];
            }
        }else{
            $feature_array = array();
        }

        return $feature_array;
    }
    
    /*
     * Gets an array of tasks for a feature
     */
    function getTaskArray($feature_id, $order_by=null)
    {
        $order_by_sql = 'ORDER BY priority';
        if($order_by){
            $order_by_sql = "ORDER BY {$order_by}";
        }

        $data_array = $this->executeQuery("
            SELECT id, title
                FROM task
            WHERE feature_id = {$feature_id}
            {$order_by_sql}
            ");

        $task_array = array();
        if(is_array($data_array)){
            foreach($data_array AS $field_array){
                $task_array[$field_array['id']] = $field_array['title'];
            }
        }else{
            return false;
        }

        return $task_array;
    }
    
    /*
     * Gets an array of tasks for a feature
     */
    function getTaskPriorityForFeature($feature_id)
    {
        $data_array = $this->executeQuery("
            SELECT id, priority
                FROM task
            WHERE feature_id = {$feature_id}
            ");

        $task_array = array();
        if(is_array($data_array)){
            foreach($data_array AS $field_array){
                $task_array[$field_array['id']] = $field_array['priority'];
            }
        }else{
            return false;
        }

        return $task_array;
    }
    
    /*
     * Get the an array of feature ids and titles for a project
     */
    function getFeatureArray($project_id, $status=null, $include_archived=TRUE)
    {
        if(isset($status)){
            switch($status){
                case 'NotStarted':
                    $status_sql = " AND feature.status IN ('Not Started', '') ";
                    break;
                case 'InProgress':
                    $status_sql = " AND feature.status IN ('In Progress', 'Impeded') ";
                    break;
                default:
                    $status_sql = " AND feature.status = '{$status}' ";
                    
            }
        }else{
            $status_sql = '';
        }
        
        if(!$include_archived){
            $archive_sql = " AND (`release`.id IS NULL OR `release`.archived = 0 ) ";
        }else{
            $archive_sql = "";
        }
        
        $data_array = $this->executeQuery("
            SELECT feature.id, feature.title
            FROM feature
            LEFT JOIN `release` ON `release`.id = feature.release_id
            WHERE feature.project_id = {$project_id}
            {$status_sql}
            {$archive_sql}
            ORDER BY feature.priority
            ");

        $feature_array = array();
        if(is_array($data_array)){
            foreach($data_array AS $field_array){
                $feature_array[$field_array['id']] = $field_array['title'];
            }
        }else{
            $feature_array = array();
        }

        return $feature_array;
    }

    /*
     * Get the an array of release ids and titles for a project
     */
    function getReleaseArray($project_id, $include_archived=TRUE)
    {
        if(!$include_archived){
            $additional_sql = " AND `release`.archived = 0 ";
        }else{
            $additional_sql = "";
        }
    
        $data_array = $this->executeQuery("
            SELECT `id`, `title`
            FROM `release`
            WHERE `project_id` = {$project_id}
            {$additional_sql}
            ORDER BY `start_date` DESC
            ");

        $release_array = array();
        if(is_array($data_array)){
            foreach($data_array AS $field_array){
                $release_array[$field_array['id']] = $field_array['title'];
            }
        }else{
            $release_array = array();
        }

        return $release_array;
    }

    /* 
     * Get the feature information for a supplied id
     */
    function getFeatureInformation($feature_id)
    {
        $data_array = $this->executeQuery("
            SELECT title, description, priority, status, project_id, release_id
            FROM feature
            WHERE id = {$feature_id}
            ");

        return $data_array[0];
    }
    
    /* 
     * Get the feature information for a supplied id
     */
    function getReleaseInformation($release_id)
    {
        $data_array = $this->executeQuery("
            SELECT `title`, `start_date`, `estimated_completion_date`
            FROM `release`
            WHERE `id` = {$release_id}
            ");

        return $data_array[0];
    }

    /*
     * Gets details about a task
     */
    function getTaskInformation($task_id)
    {
        $data_array = $this->executeQuery("
            SELECT *
                FROM task
            WHERE id = {$task_id}
            ");
        if(!is_array($data_array)){
            return false;
        }
        $return_array = $data_array[0];
        $return_array['md5'] = md5($return_array['title'].'+'.$return_array['description'].'+'.$return_array['user_email']);
        return $return_array;
    }

    /*
     * Gets the priority for a task.
     */
    function getTaskPriority($task_id)
    {
        $data_array = $this->executeQuery("
            SELECT priority 
            FROM task
            WHERE id = {$task_id}
            ");

        return $data_array[0]['priority'];
    }
    
    /*
     * Gets the priority for a feature.
     */
    function getFeaturePriority($feature_id)
    {
        $data_array = $this->executeQuery("
            SELECT priority 
            FROM feature
            WHERE id = {$feature_id}
            ");

        return $data_array[0]['priority'];
    }

    /*
     * Gets the status for a supplied task.
     */
    function getTaskStatus($task_id)
    {
        $data_array = $this->executeQuery("
            SELECT status 
            FROM task
            WHERE id = {$task_id}
            ");

        return $data_array[0]['status'];

    }

    /*
     * Gets the current Working project ID for a team
     */
    function getCurrentProjectId($company_id, $team_id)
    {
    
        //Try get the latest project before today with releases, if fails, get the latest project before today, if fails, get first project in future
        $project = $this->executeQuery("
            (
            SELECT project.id
            FROM project
            INNER JOIN team_project ON team_project.project_id = project.id
            INNER JOIN `release` ON `release`.project_id = project.id
            WHERE company_id = {$company_id}
            AND team_id = {$team_id}
            AND project.start_date <= NOW( )
            ORDER BY project.start_date DESC
            LIMIT 1
            )
            UNION
            (
            SELECT id
            FROM project
            INNER JOIN team_project ON team_project.project_id = project.id
            WHERE company_id = {$company_id}
            AND team_id = {$team_id}
            AND start_date <= NOW()
            ORDER BY start_date DESC
            LIMIT 1 
            )
            UNION
            (
            SELECT id
            FROM project
            INNER JOIN team_project ON team_project.project_id = project.id
            WHERE company_id = {$company_id}
            AND team_id = {$team_id}
            AND start_date > NOW()
            ORDER BY start_date
            LIMIT 1 
            )
            ");
            
        return isset($project[0]['id']) ? $project[0]['id'] : 0;
    }

    /*
     * Gets the current Working project ID for a company
     */
    function getCurrentReleaseId($project_id)
    {
        $releases = $this->executeQuery("
            SELECT id, start_date, end_date
            FROM release
            WHERE project_id = {$project_id}
            AND start_date <= NOW()
            ORDER BY start_date DESC
            LIMIT 1 
            ");
            
        return empty($releases[0]['id']) ? '0' : $releases[0]['id'];
    }

    /*
     * Gets the impediments for a company.
     */
    function getImpediments($company_id)
    {
        $impediments = $this->executeQuery("
            SELECT impediment.id, impediment.title, impediment.description, CONCAT(user.display_name, ' - ', user.email) as user
            FROM impediment
            INNER JOIN user ON user.email = impediment.user_email
            WHERE user.company_id = {$company_id}
            ");

        return $impediments;
    }

//////////////////////////////////////////////Update functions///////////////////////////////////////////////////////////////
    
    function touchReleaseTaskTimeStamp($task_id)
    {
        return $this->executeUpdateQuery("
            UPDATE `release`
            INNER JOIN feature ON feature.release_id = `release`.id
            INNER JOIN task ON task.feature_id = feature.id
            SET `release`.task_time_stamp = NOW()
            WHERE task.id = {$task_id}
        ");
    }
    
    /*
     * Inserts an entry into the impediment table.
     */
    function addImpediment($title, $description, $user_email)
    {
        return $this->executeUpdateQuery("
            INSERT INTO impediment
            (title, description, user_email)
            VALUES
            ('{$title}', '{$description}', '{$user_email}')
            ");
    }

    /*
     * DEPRICATED FUNCTION
     */
    function setCurrentProjectId($company_id, $project_id)
    {
        return;
        return $this->executeUpdateQuery("
            UPDATE company 
            SET current_working_project_id = {$project_id} 
            WHERE id = {$company_id};
            ");

    }

    /*
     * Called to update a feature's status. This is handled automatically, except for the impeded section.
     */
    function updateFeatureStatus($feature_id)
    {
        //Skip impeded features.
        $feature = $this->getFeatureInformation($feature_id);
        if($feature['status'] == 'Impeded'){
            return 'Impeded';
        }

        //Go through each task and check status, 
        //to determine what the feature status should be.
        $task_list = $this->getTaskArray($feature_id);
        $feature_status = 'Not Started';
        $count_done = 0;
        $count_total = 0;

        if(is_array($task_list)){
            foreach($task_list AS $task_id=>$task_title){
                $task_status = $this->getTaskStatus($task_id);

                if($task_status == 'In Progress' || $task_status == 'Impeded'){
                    $feature_status = 'In Progress';
                }elseif($task_status == 'Done'){
                    //Used to determine if all the statuses are done or not
                    $count_done++;
                }
                $count_total++;
            }
        }
        //Final checks to make sure that the overwriting did not mess things up.
        if($count_done == $count_total && $count_total != 0){
            $feature_status = 'Done';
        }
        if($count_done > 0 && $feature_status == 'Not Started'){
            $feature_status = 'In Progress';
        }
        
        if($feature_status == $feature['status']){
            return $feature_status;
        }
        
        $this->executeUpdateQuery("
            UPDATE feature 
            SET status = '{$feature_status}'
            WHERE id = {$feature_id}
            ");
        
        //Handle the priority
        $feature_array_done = $this->getFeatureArray($feature['project_id'], 'Done');
        $feature_array_done = $this->convertFeatureArrayToPriorityArray($feature_array_done);
        
        $feature_array_in_progress = $this->getFeatureArray($feature['project_id'], 'InProgress');
        $feature_array_in_progress = $this->convertFeatureArrayToPriorityArray($feature_array_in_progress);
        
        $feature_array_not_started = $this->getFeatureArray($feature['project_id'], 'NotStarted');
        $feature_array_not_started = $this->convertFeatureArrayToPriorityArray($feature_array_not_started);
        
        $this->updateFeaturePriorityIntelligent($feature_array_done, $feature['project_id'], 'Done');
        $this->updateFeaturePriorityIntelligent($feature_array_in_progress, $feature['project_id'], 'InProgress');
        $this->updateFeaturePriorityIntelligent($feature_array_not_started, $feature['project_id'], 'NotStarted');

        return $feature_status;
    }
    
    //function for the above function.
    function convertFeatureArrayToPriorityArray($feature_array)
    {
        foreach($feature_array AS $feature_id=>$value){
            $return_array[] = 'feature_'.$feature_id;
        }
        return $return_array;
    }

    /*
     * Called via Ajax mostly. Save the task on move
     */
    function saveTaskStatus($task_id, $status)
    {
        $this->executeUpdateQuery("
            UPDATE task 
            SET status = '{$status}'
                WHERE id = {$task_id};
            ");

        $feature_id = $this->executeQuery("
            SELECT feature_id 
            FROM task 
            WHERE id = {$task_id};
            ");
        $feature_id = $feature_id[0]['feature_id'];
        
        $this->touchReleaseTaskTimeStamp($task_id);
        
        return $this->updateFeatureStatus($feature_id);

    }

    //Internal Function for setting the priority.
    function updateDirectTaskPriority($task_id, $priority)
    {
        return $this->executeUpdateQuery("
            UPDATE task
                SET priority = {$priority}	
            WHERE id = {$task_id}
        ");
    } 
    
    //Internal Function for setting the priority.
    function updateDirectFeaturePriority($feature_id, $priority)
    {
        return $this->executeUpdateQuery("
            UPDATE feature
            SET priority = {$priority}	
            WHERE id = {$feature_id}
        ");
    } 
    
    //Takes into account the different statuses.
    function updateFeaturePriorityIntelligent($feature_array, $project_id, $status=null)
    {
        if(!is_array($feature_array)){
            return;
        }
        if(isset($status)){
            switch($status){
                case 'NotStarted':
                    $start_key = $this->executeQuery("SELECT COUNT(id) AS count FROM feature WHERE project_id = {$project_id} AND status NOT IN('Not Started', '') ");
                    $start_key = $start_key[0]['count'];
                    break;
                case 'InProgress':
                    //Select the number of not started features. (in progress is after done).
                    $start_key = $this->executeQuery("SELECT COUNT(id) AS count FROM feature WHERE project_id = {$project_id} AND status = 'Done' ");
                    $start_key = $start_key[0]['count'];
                    break;
                default:
                    $start_key = 0;
            }
        }else{
            $start_key = 0;
        }
        
        //Hack for priority starting at 1, not 0
        $start_key++;
        
        foreach($feature_array AS $key=>$value){
            $feature_id = substr($value, 8);
            $this->updateDirectFeaturePriority($feature_id, $key+$start_key);
        }        
    }
    
    /*
     * Function to update the priority of a task. 
     * Will push other tasks down.
     */
    function updateTaskPriority($task_id, $new_priority)
    {
        //Get feature_id for the tasks list
        $feature_id = $this->executeQuery("
            SELECT feature_id 
            FROM task 
            WHERE id = {$task_id}
            ");
        $feature_id = $feature_id[0]['feature_id'];

        $max_priority = $this->executeQuery("
            SELECT max(priority) as max_priority
            FROM task
            WHERE feature_id = {$feature_id}
        ");
        
        if($new_priority == 'max')
        {
            $new_priority = $max_priority[0];
        }

        //If a +1 or -1 has been supplied, it means that the task must be moved up or down.
        //Get the new priority according to the current priority.
        if($new_priority == '+1' || $new_priority == '-1'){
            $info = $this->getTaskPriority($task_id);
            if($new_priority == '-1'){
                $new_priority = $info-1;
            }else{
                $new_priority = $info+1;

            }
        }
        
        //Just a check to make sure priority doesnt get out of hand:
        $new_priority = $new_priority<1?1:$new_priority;
        $new_priority = $new_priority>$max_priority[0]['max_priority']?$max_priority[0]['max_priority']:$new_priority;

        //Get the tasks that are part of the same feature as the supplied task_id order by priority for loop
        //Exclude the task id, because the loop is going to leave a gap for it's insert
        $task_list = $this->executeQuery("
            SELECT id, priority
            FROM task 
            WHERE feature_id = {$feature_id}
            ORDER BY priority
            ");

        //This loop is going to re-assign all the priorities from the first one.
        $priority = 1;
        foreach($task_list AS $id=>$task_object){
            //Skip the new priority, so that a gap is left for the update.
            if($priority == $new_priority){
                $priority++;
            }
            if($task_object['id'] == $task_id){
                continue;
            }

            //Update each task setting the priority
            $this->updateDirectTaskPriority($task_object['id'], $priority);
            $priority++;
        }

        $this->touchReleaseTaskTimeStamp($task_id);
        
        //Update the task supplied.
        return $this->updateDirectTaskPriority($task_id, $new_priority);
    }


    /*
     * Function to update the priority of a feature. 
     * Will push other tasks down.
     */
    function updateFeaturePriority($feature_id, $new_priority)
    {

        //Get feature_id for the features list
        $project_id = $this->executeQuery("
            SELECT project_id 
            FROM feature 
            WHERE id = {$feature_id}
            ");
        $project_id = $project_id[0]['project_id'];

        $max_priority = $this->executeQuery("
            SELECT max(priority) as max_priority
            FROM feature
            WHERE project_id = {$project_id}
        ");
        
        if($new_priority == 'max')
        {
            $new_priority = $max_priority[0];
        }

        //If a +1 or -1 has been supplied, it means that the feature must be moved up or down.
        //Get the new priority according to the current priority.
        if($new_priority == '+1' || $new_priority == '-1'){
            $info = $this->getFeaturePriority($feature_id);
            if($new_priority == '-1'){
                $new_priority = $info-1;
            }else{
                $new_priority = $info+1;
            }
        }
        
        //Just a check to make sure priority doesnt get out of hand:
        $new_priority = $new_priority<1?1:$new_priority;
        $new_priority = $new_priority>$max_priority[0]['max_priority']?$max_priority[0]['max_priority']:$new_priority;

        //Get the features that are part of the same project as the supplied feature_id order by priority for loop
        //Exclude the feature id, because the loop is going to leave a gap for it's insert
        $feature_list = $this->executeQuery("
            SELECT id, priority
            FROM feature 
            WHERE project_id = {$project_id}
            ORDER BY priority
            ");

        //This loop is going to re-assign all the priorities from the first one.
        $priority = 1;
        foreach($feature_list AS $feature_object){
            //Skip the new priority, so that a gap is left for the update.
            if($priority == $new_priority){
                $priority++;
            }
            if($feature_object['id'] == $feature_id){
                continue;
            }

            //Update each feature setting the priority
            $this->updateDirectFeaturePriority($feature_object['id'], $priority);
            $priority++;
        }
        
        //Update the feature supplied.
        return $this->updateDirectFeaturePriority($feature_id, $new_priority);
    }

    /*
     * Update a project in the database, using $old_email as a reference, incase the email got changed.
     */
    function updateProject($project_id, $title, $description, $start_date, $company_id=null)
    {
        return $this->executeUpdateQuery("
            UPDATE project SET 
            title = '{$title}',
            description = '{$description}',
            start_date = '{$start_date}'
            WHERE id = {$project_id}
            ");
    }

    /*
     * Insert a new project into the database
     */
    function addProject($title, $description, $start_date, $company_id, $team_id=null)
    {
        $this->executeUpdateQuery("
            INSERT INTO project
            (title, description, start_date, company_id)
            VALUES
            ('{$title}', '{$description}', '{$start_date}', '{$company_id}')
            ");
        
        $super_team_id = $this->executeQuery("
            SELECT super_team_id FROM company WHERE id = {$company_id}
        ");
        $super_team_id = $super_team_id[0]['super_team_id'];

        $this->executeUpdateQuery("
            INSERT INTO team_project(team_id, project_id)
            VALUES ({$super_team_id}, (SELECT MAX(id) AS id FROM project WHERE company_id = {$company_id}))
        ");

        if($team_id && $team_id != $super_team_id){
            $this->executeUpdateQuery("
                INSERT INTO team_project(team_id, project_id)
                VALUES ($team_id, (SELECT MAX(id) AS id FROM project WHERE company_id = {$company_id}))
            ");
        }

        return true;
    }
    
    /*
     * Update a feature in the database, using $old_email as a reference, incase the email got changed.
     */
    function updateFeature($feature_id, $title, $description, $status, $project_id=null)
    {
        //Can only update a feature to impeded / Auto Detect
        if($status == 'Impeded'){
            $status_sql = "status = 'Impeded', ";
        }elseif($status == 'Auto'){
            //Set it to anything except impeded. Is going to be overwritten.
            $status_sql = "status = 'Not Started', ";
        }

        $this->executeUpdateQuery("
            UPDATE feature SET 
            title = '{$title}',
            {$status_sql}
            description = '{$description}'
            WHERE id = {$feature_id}
            ");

        return $this->updateFeatureStatus($feature_id);
    }

    /*
     * Insert a new feature into the database
     */
    function addFeature($title, $description, $status, $project_id, $priority=NULL)
    {
        //Auto assign priority
        if(!$priority){
            $priority = $this->executeQuery("
                SELECT max(priority) AS priority FROM feature WHERE project_id = {$project_id};
                ");
            $priority = $priority[0]['priority'] ? $priority[0]['priority'] : 0;
            $priority++;
        }
        
         return $this->executeUpdateQuery("
            INSERT INTO feature
            (title, description, status, project_id, priority)
            VALUES
            ('{$title}', '{$description}', '{$status}', '{$project_id}', '{$priority}')
            ");
    }


    
    /*
     * Update a feature in the database, using $old_email as a reference, incase the email got changed.
     */
    function updateRelease($release_id, $title, $start_date, $estimated_completion_date)
    {
        $this->executeUpdateQuery("
            UPDATE `release` SET 
            `title` = '{$title}',
            `start_date` = '{$start_date}',
            `estimated_completion_date` = '{$estimated_completion_date}'
            WHERE `id` = {$release_id}
            ");
            
        return true;
    }

    /*
     * Insert a new feature into the database
     */
    function addRelease($title, $start_date, $estimated_completion_date, $project_id)
    {
         return $this->executeUpdateQuery("
            INSERT INTO `release`
            (`title`, `start_date`, `estimated_completion_date`, `project_id`)
            VALUES
            ('{$title}', '{$start_date}', '{$estimated_completion_date}', '{$project_id}')
            ");
    }

    /*
     * Update a task in the database, using $old_email as a reference, incase the email got changed.
     */
    function updateTask($task_id, $title, $description, $status=null, $feature_id, $user_email, $md5_check=null, $estimated_hours='0')
    {
        if($md5_check){
            $md5 = $this->getTaskInformation($task_id);
            $md5 = $md5['md5'];
            
            //echo $md5 ."<br/>".$md5_check;exit;
            
            if($md5 != $md5_check){
                return array("CONFLICT"=>'CONFLICT', 'md5'=>$md5);
            }
        }
        
        if($user_email != 'NULL'){
            $user_email = "'{$user_email}'";
        }
        if($status){
            $status_sql = "status = '{$status}', ";
        }
        
        $this->touchReleaseTaskTimeStamp($task_id);
        
        $return_array['success'] = $this->executeUpdateQuery("
            UPDATE task SET 
            title = '{$title}',
            description = '{$description}',
            {$status_sql}
            user_email = {$user_email},
            estimated_hours = '$estimated_hours'
            WHERE id = {$task_id}
            AND feature_id = {$feature_id}
            ");       
            
        $md5 = $this->getTaskInformation($task_id);
        $return_array['md5'] = $md5['md5'];
        
        return $return_array;
    }

    /*
     * Insert a new task into the database
     */
    function addTask($title, $description, $status=null, $feature_id, $user_email, $priority=null, $estimated_hours='0')
    {
        if(isset($_SESSION['user_email'])){
            $added_by = $_SESSION['user_email'];
        }
        //Auto assign priority
        if(!$priority){
            $priority = $this->executeQuery("
                SELECT max(priority) AS priority FROM task WHERE feature_id = {$feature_id};
                ");
            $priority = $priority[0]['priority'] ? $priority[0]['priority'] : 0;
            $priority++;
        }
        
        if(strtoupper($user_email) != 'NULL'){
            $user_email = "'{$user_email}'";
        }
        
        if(!$status){
            $status = 'Not Started';
        }

        $this->executeUpdateQuery("
            INSERT INTO task
            (title, description, status, feature_id, user_email, priority, added_by, estimated_hours)
            VALUES
            ('{$title}', '{$description}', '{$status}', '{$feature_id}', {$user_email}, '{$priority}', '{$added_by}', '$estimated_hours')
            ");

        $this->updateFeatureStatus($feature_id);
        
        $task_id = $this->executeQuery("
            SELECT id
            FROM task
            WHERE feature_id = {$feature_id}
            ORDER BY id DESC 
            LIMIT 1
        ");
                
        $this->touchReleaseTaskTimeStamp($task_id);
        
        return $task_id[0]['id'];
    }
    
    function updateFeatureReleaseId($feature_id, $release_id)
    {
        return $this->executeUpdateQuery("
            UPDATE feature
            SET release_id = $release_id
            WHERE id = $feature_id
        ");
    }


//////////////////////////////////DELETE FUNCTIONS///////////////////////////////////////////////

    /*
     * Delete an impediment by id.
     */
    function deleteImpediment($impediment_id)
    {
        $this->executeUpdateQuery("
            DELETE 
            FROM impediment 
            WHERE id = {$impediment_id}
            ");
    }

    /*
     * Delete Project with a supplied id
     */ 
    function deleteProject($project_id)
    {
        $this->executeUpdateQuery("
            DELETE 
            FROM project
                WHERE id = {$project_id}
            ");
    }
    
    function removeProjectFromTeam($team_id, $project_id)
    {
        $this->executeUpdateQuery("
            DELETE 
            FROM team_project 
            WHERE project_id = {$project_id} 
            AND team_id = {$team_id}
        ");
    }

    /*
     * Delete Feature with a supplied id
     */
    function deleteFeature($feature_id)
    {
        $project_id = $this->getFeatureInformation($feature_id);
        $project_id = $project_id['project_id'];
    
        $this->updateFeaturePriority($feature_id, 'max');
        
        $this->executeUpdateQuery("
            DELETE 
            FROM feature
                WHERE id = {$feature_id}
            ");
    }

    /*
     * Delete Feature with a supplied id
     */
    function deleteRelease($release_id)
    {
        $this->executeUpdateQuery("
            DELETE 
            FROM `release`
            WHERE id = {$release_id}
            ");
    }
    
    function deleteReleaseAndFeatures($release_id)
    {
        $this->executeUpdateQuery("
            DELETE 
            FROM feature 
            WHERE release_id = {$release_id}
        ");
        
        $this->deleteRelease($release_id);
    }

    /*
     * Delete Task with a supplied id
     */
    function deleteTask($task_id)
    {
        $feature_id = $this->getTaskInformation($task_id);
        $feature_id = $feature_id['feature_id'];
    
        $this->updateTaskPriority($task_id, 'max');
    
        $this->executeUpdateQuery("
            DELETE 
            FROM task
                WHERE id = {$task_id}
            ");
        
        $this->updateFeatureStatus($feature_id);
        
        $this->touchReleaseTaskTimeStamp($task_id);
        
    }
    
    function archiveRelease($release_id)
    {
        return $this->executeUpdateQuery("
            UPDATE `release`
            SET archived = 1
            WHERE id = {$release_id}
        ");
    }
    
    function unArchiveRelease($release_id)
    {
        return $this->executeUpdateQuery("
            UPDATE `release`
            SET archived = 0
            WHERE id = {$release_id}
        ");
    }
    
    function getReleaseArchiveList($project_id)
    {
        $releases = $this->executeQuery("
            SELECT `release`.id, `release`.title, `release`.start_date, `release`.estimated_completion_date, feature.title AS feature
            FROM `release` 
            LEFT JOIN feature ON feature.release_id = `release`.id
            WHERE `release`.project_id = {$project_id}
            AND `release`.archived = 1
        ");
        
        foreach($releases AS $release_information){
            $return_array[$release_information['id']]['features'][] = $release_information['feature'];
            $return_array[$release_information['id']]['title'] = $release_information['title'];
            $return_array[$release_information['id']]['start_date'] = $release_information['start_date'];
            $return_array[$release_information['id']]['estimated_completion_date'] = $release_information['estimated_completion_date'];
        }
        
        return $return_array;
    }

}

?>
