<?php
class AjaxController
{
    function touchTaskHours()
    {
        require_once("Model/ProjectModel.php");
        $ProjectModel = new ProjectModel(); 
        
        $user_email = $_GET['user'];
        $date = $_GET['date'];
        $hours = $_GET['hours'];
        $task_id = $_GET['hours-task_id'];
        $release_id = $_GET['hours-release_id'];
        
        if( 
            $user_email && $user_email != ''
            && $date && $date != ''
            && $hours && $hours != ''
            && $task_id && $task_id != ''
            && $release_id && $release_id != ''
        ){
            $ProjectModel->saveTaskHours($task_id, $release_id, $user_email, $date, $hours);
        }
        
        $task_hours = $ProjectModel->getTaskHours($task_id);
        echo "<table class='mainGrid'>";
        echo "<tr>
                <td colspan=4 style='text-align:center'>Estimated hours: {$task_hours[0]['estimated_hours']}</td>
              </tr>";
        echo "<tr>
                <th>User</th>
                <th>Date</th>
                <th>Hours</th>
                <th>Delete</th>
              </th>
            ";
            
        if(is_array($task_hours)){
            foreach($task_hours AS $values){
                if(!$values['user_email']){
                    echo "<tr>";
                    echo "<td colspan=4 style='text-align:center'>No hours assigned yet.</td>";
                    echo "</tr>";
                    continue;
                }
                echo "<tr>";
                echo "<td>{$values['user_email']}</td>";
                echo "<td>{$values['date']}</td>";
                echo "<td>{$values['hours']}</td>";
                echo "<td style='text-align:center'><input type='button' onclick=\"deleteTaskHours({$values['id']}, {$values['task_id']});\" style='font-size:6pt;' title='Delete these hours done.' value='X' /></td>";
                echo "</tr>";
            }
        }else{
            echo "<tr>";
            echo "<td colspan=4 style='text-align:center'>No hours assigned yet.</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    function deleteTaskHours()
    {
        $id = $_GET['id'];
        
        require_once("Model/ProjectModel.php");
        $ProjectModel = new ProjectModel(); 
        
        $ProjectModel->deleteTaskHours($id);
    }
    
    function saveUserPreference()
    {
        require_once("Model/BaseModel.php");
        $BaseModel = new BaseModel();
        
        $controller = $_POST['controller'];
        $action = $_POST['action'];
        $key = $_POST['key'];
        $value = $_POST['value'];
        $email = $_SESSION['user_email'];
        
        $BaseModel->saveUserPreference($controller, $action, $key, $value, $email);
    }
    
    //Function called via Ajax when a task is moved into a column.
    function saveTaskStatus()
    {
        $task_id = $_GET['task_id'];
        $status = $_GET['status'];
        $old_status = $_GET['old_status'];
        $release_id = $_GET['release_dont_check'];
        
        require_once("Model/ProjectModel.php");
        $ProjectModel = new ProjectModel();
        
        //Check that the status hasnt changed since the update.
        $current_status = $ProjectModel->getTaskStatus($task_id);
        if($current_status != $old_status){
            echo "CONFLICT";
        }else{
            echo $ProjectModel->saveTaskStatus($task_id, $status).',';
            echo $ProjectModel->getReleaseTaskTimeStamp($release_id);
        }
    }
    
    function sendTicketEmail()
    {
        require_once("Controller/BaseController.php");
        $BaseController = new BaseController();
    
        $title = $_POST['title'];
        $type = $_POST['type'];
        $description = $_POST['description'];
        
        $email_content_client = "
            Hi,<br/>
            This is just an auto-response to the ticket that you have submitted. We will get to this problem as soon as we can.<br/>
            
            See the following text that was submitted by you:<br/><hr/>
            <b>Title: </b>
            {$title}<br/>
            <b>Type: </b>
            {$type}<br/>
            <b>Description:</b><br/>
            {$description}<br/><br/>
            
            <hr/>
            Regards Anvil Support.
        ";
        
        $session_dump = serialize($_SESSION);
        $date_time = date('Y-m-y');
        $date_time .= ' '. date('H:i:s');
        $email_content_anvil = "
            Ticket opened:<hr/>
            <b>Title: </b>
            {$title}<br/>
            <b>Type: </b>
            {$type}<br/>
            <b>Description:</b><br/>
            {$description}<br/><br/>
            
            Submitted by {$_SESSION['user_email']}<br/><br/>
            _SESSION dump:<br/><pre> {$session_dump} </pre><br/>
            Date time: {$date_time} (could be -1 hour)<br/>           
        ";
    
        $BaseController->sendEmail('support@butternet.com', "Ticket: {$type} - {$title}", $email_content_anvil);
        $BaseController->sendEmail($_SESSION['user_email'], "Auto-respond to ticket: {$title}", $email_content_client);
        
        echo "SUCCESS";
    }

    //Depricated function
    function setCurrentProjectId()
    {
        $project_id = $_GET['project_id'];
        $company_id = $_GET['company_id'];
        require_once("Model/ProjectModel.php");
        $ProjectModel = new ProjectModel();
        $ProjectModel->setCurrentProjectId($company_id, $project_id);
    }

    function deleteProject()
    {
        $project_id = $_GET['project_id'];
        require_once("Model/ProjectModel.php");
        $ProjectModel = new ProjectModel();
    
        //Only company admin can completely remove project.
        if($_SESSION['user_email'] == $_SESSION['company_admin_email']){
            $ProjectModel->deleteProject($project_id);
        }else{
            $team_id = $_GET['team_id'];
            $ProjectModel->removeProjectFromTeam($team_id, $project_id);
        }
        
    }
    
    function deleteRelease()
    {
        $release_id = $_GET['release_id'];
        $delete_features_flag = $_POST['delete_features'];
        require_once("Model/ProjectModel.php");
        $ProjectModel = new ProjectModel();
        
        if($delete_features_flag){
            $ProjectModel->deleteReleaseAndFeatures($release_id);
        }else{
            $ProjectModel->deleteRelease($release_id);
        }
    }

    function deleteFeature()
    {
        $feature_id = $_GET['feature_id'];
        require_once("Model/ProjectModel.php");
        $ProjectModel = new ProjectModel();
        $ProjectModel->deleteFeature($feature_id);
    }

    function deleteTask()
    {
        $task_id = $_GET['task_id'];
        require_once("Model/ProjectModel.php");
        $ProjectModel = new ProjectModel();
        $ProjectModel->deleteTask($task_id);
        
        $timestamp = $ProjectModel->getReleaseTaskTimeStamp($_GET['release_dont_check']);
        
        echo $timestamp;
    }

    function deleteImpediment()
    {
        $impediment_id = $_GET['impediment_id'];
        require_once("Model/ProjectModel.php");
        $ProjectModel = new ProjectModel();
        $ProjectModel->deleteImpediment($impediment_id);
    }
    
    function deleteUser()
    {
        //Only company admin can do this.
        if($_SESSION['company_admin_email'] != $_SESSION['user_email']){
            return;
        }
        
        $user_email = $_GET['email'];
        
        //Cannot delete the company admin
        if($user_email == $_SESSION['company_admin_email']){
            return;
        }
        
        require_once("Model/UserModel.php");
        $UserModel = new UserModel();
        $UserModel->deleteUser($user_email, $_SESSION['company_id']);
    }
    
    function deleteTeam()
    {
        //Only company admin can do this.
        if($_SESSION['company_admin_email'] != $_SESSION['user_email']){
            return;
        }
        
        $team_id = $_GET['team_id'];
        
        //Cannot delete super_team
        if($_SESSION['company_super_team_id'] == $team_id){
            return;
        }
        
        require_once("Model/UserModel.php");
        $UserModel = new UserModel();
        $UserModel->deleteTeam($team_id, $_SESSION['company_id']); 
    }
    
    function updateUserTeam()
    {
        //Only company admin can do this.
        if($_SESSION['company_admin_email'] != $_SESSION['user_email']){
            return;
        }
    
        $user_email = $_GET['email'];
        $team_id = $_GET['team_id'];
        
        //Cannot update the company admin
        if($user_email == $_SESSION['company_admin_email']){
            return;
        }
        
        require_once("Model/UserModel.php");
        $UserModel = new UserModel();
        $UserModel->updateUserTeam($user_email, $team_id, $_SESSION['company_id']);
    }
    
    function updateTaskPriorityOrder()
    {
        require_once("Model/ProjectModel.php");
        $ProjectModel = new ProjectModel();
        
        $newTaskOrderString = $_POST['OrderString'];
        $task_array = explode(',', $newTaskOrderString);
        
        foreach($task_array AS $key=>$value){
            $task_id = substr($value, 5);
            $ProjectModel->updateDirectTaskPriority($task_id, ++$key);
        }        
    }
    
    function updateFeaturePriorityOrder()
    {
        require_once("Model/ProjectModel.php");
        $ProjectModel = new ProjectModel();
        
        $newFeatureOrderString = $_POST['OrderString'];
        $feature_array = explode(',', $newFeatureOrderString);
        
        $status = $_POST['status'];
        $project_id = $_POST['project_id'];
        
        $ProjectModel->updateFeaturePriorityIntelligent($feature_array, $project_id, $status);
    }
    
    function updateFeatureReleaseId()
    {
        $feature_id = $_GET['feature_id'];
        $release_id = $_GET['release_id'];
        
        require_once("Model/ProjectModel.php");
        $ProjectModel = new ProjectModel();
        $ProjectModel->updateFeatureReleaseId($feature_id, $release_id);
    }
    
    function archiveRelease()
    {
        $release_id = $_GET['release_id'];
        
        require_once("Model/ProjectModel.php");
        $ProjectModel = new ProjectModel();
        $ProjectModel->archiveRelease($release_id);
    }
    
    function unArchiveRelease()
    {
        $release_id = $_GET['release_id'];
        
        require_once("Model/ProjectModel.php");
        $ProjectModel = new ProjectModel();
        $ProjectModel->unArchiveRelease($release_id);
    }
    
    function getReleasesForProjectHTML()
    {
        $project_id = $_GET['project_id'];
        
        require_once('Model/ProjectModel.php');
        $ProjectModel = new ProjectModel();
        
        $release_array = $ProjectModel->getReleasesForProject($project_id);
        if(is_array($release_array)){
            foreach($release_array AS $release_id=>$release_title){
                echo "<option value='{$release_id}'>$release_title</option>";
            }   
        }
    }   
    
    function taskEdit()
    {
        require_once('Model/ProjectModel.php');
        $ProjectModel = new ProjectModel();

        if(isset($_POST['add'])){
            $task_id = $ProjectModel->addTask($_POST['title'],$_POST['description'], null, $_POST['feature_id'], $_POST['user'], null, $_POST['estimated_hours']);
            $mode = 'add';
        }else{
            $return_array = $ProjectModel->updateTask($_POST['task_id'],$_POST['title'],$_POST['description'], null, $_POST['feature_id'], $_POST['user'], $_POST['md5'], $_POST['estimated_hours']);
            
            $json_array['md5'] = $return_array['md5'];
            if($return_array['CONFLICT']){
                $json_array['conflict'] = 'CONFLICT';
            }
            $mode = 'update';
            $task_id = $_POST['task_id'];
        }
        
        require_once('Controller/ProjectController.php');
        $ProjectController = new ProjectController();
        $json_array['html'] = $ProjectController->generateTaskHTML($task_id, $mode);
        
        echo json_encode($json_array);
    }
    
    function updateTaskboard()
    {
        require_once('Controller/ProjectController.php');
        require_once("Model/ProjectModel.php");
        $ProjectModel = new ProjectModel();
        $ProjectController = new ProjectController();
        
        $task_json_array = $_POST['task_json_object_send'];
        
        //print_r($task_json_array);exit;
        
        $release_id = $_POST['release_id'];
        if(!is_array($task_json_array)){
            return;
        }
        
        //$md5_compare_array = $ProjectModel->getMD5Array(array_keys($task_json_array));
        $task_id_string = "'z'";
        $json_array = null;
        $key = 0;
        foreach($task_json_array AS $lol=>$info_array){
            
            if(!is_array($info_array)){
                continue;
            }
            
            $task_id = $info_array['id'];
            $task_id_string .= ",{$task_id}";
            
            $task_information = $ProjectModel->getTaskInformation($task_id);
            $feature_id = $task_information['feature_id'];
            //$key = $task_id;
            $key++;
            if(!$task_information){
            
                $json_array[$key]['id'] = $task_id;
                $json_array[$key]['type'] = 'delete';
            }
            
            if($task_information['md5'] && $task_information['md5'] != $info_array['md5']){
                //echo "task_id:{$task_id} ".$task_information['md5']." --- ".$info_array['md5'];exit;
                $html = $ProjectController->generateTaskHTML($task_id, 'edit');
                                
                $json_array[$key]['id'] = $task_id;
                $json_array[$key]['type'] = 'update';
                $json_array[$key]['md5'] = $task_information['md5'];
                $json_array[$key]['feature_id'] = $feature_id;
                $json_array[$key]['html'] = $html;
            }
            
            if($task_information['priority'] && $task_information['priority'] != $info_array['priority']){
                //Needs to be for all tasks, not just one.

                $json_array[$key]['type'] = 'priority_ajust';
                $json_array[$key]['feature_id'] = $feature_id;
                $json_array[$key]['tasks'] = $ProjectModel->getTaskPriorityForFeature($feature_id);
            }
            
            if($task_information['priority'] && $task_information['status'] != $info_array['status']){
        
                $json_array[$key]['id'] = $task_id;
                $json_array[$key]['type'] = 'status_ajust';
                $json_array[$key]['feature_id'] = $feature_id;
                $json_array[$key]['status'] = $task_information['status'];
            }
            
        }
        
        $new_tasks = $ProjectModel->getNewTasksForRelease($release_id, $task_id_string);
        
        if(is_array($new_tasks)){
            foreach($new_tasks AS $task_id=>$task_information){
                $key++;
                $html = $ProjectController->generateTaskHTML($task_id, 'add');
                
                $json_array[$key]['id'] = $task_id;
                $json_array[$key]['type'] = 'new';
                $json_array[$key]['md5'] = $task_information['md5'];
                $json_array[$key]['feature_id'] = $task_information['feature_id'];
                $json_array[$key]['html'] = $html;
                
                //echo json_encode($json_array);       
            }
        }
        
        if($json_array){
            echo json_encode($json_array);  
        }
    }
    
    //Legacy code
    function checkTaskboardChanges()
    {
        require_once("Model/ProjectModel.php");
        $ProjectModel = new ProjectModel();
        
        $release_task_timestamp = $_POST['release_task_timestamp'];
        $release_id = $_POST['release_id'];
        //echo $release_task_timestamp." ".$release_id;exit;
        $compare_time_stamp = $ProjectModel->getReleaseTaskTimeStamp($release_id);
        if($compare_time_stamp && $compare_time_stamp > $release_task_timestamp){
            echo "CHANGED";
        }
    }

    //Legacy code
    function moveTaskUp()
    {
        $task_id = $_GET['task_id'];
        require_once("Model/ProjectModel.php");
        $ProjectModel = new ProjectModel();
        $ProjectModel->updateTaskPriority($task_id, '-1');
    }
    
    //Legacy code
    function moveTaskDown()
    {
        $task_id = $_GET['task_id'];
        require_once("Model/ProjectModel.php");
        $ProjectModel = new ProjectModel();
        $ProjectModel->updateTaskPriority($task_id, '+1');
    }
    
    function disableUser()
    {
        $user_email = $_GET['email'];
        require_once('Model/UserModel.php');
        $UserModel = new UserModel();
        $UserModel->disableUser($user_email);
    }
    
    function enableUser()
    {
        $user_email = $_GET['email'];
        require_once('Model/UserModel.php');
        $UserModel = new UserModel();
        echo $UserModel->enableUser($user_email);
    }

}
?>
