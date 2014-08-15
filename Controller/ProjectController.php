<?php
require_once("Controller/BaseController.php");
require_once("Model/ProjectModel.php");
require_once("Model/UserModel.php");
require_once("Helper/DateHelper.php");
require_once("Controller/Project/ProjectLegacy.php");
require_once("Controller/Project/ProjectBacklog.php");
class ProjectController extends BaseController
{
    protected $ProjectModel;
    protected $UserModel;

    function __construct(){
        parent::__construct();

        $this->ProjectModel = new ProjectModel();
        $this->UserModel = new UserModel();
    }
    
    //Legacy
    function taskList()
    {
        $ProjectLegacy = new ProjectLegacy();
        $ProjectLegacy->taskList();
    }
    
    //Backlog Tasklist
    function backlogTaskList()
    {
        $ProjectBacklog = new ProjectBacklog();
        $ProjectBacklog->taskList();
    }
    
    function backlogProjectList()
    {
        $ProjectBacklog = new ProjectBacklog();
        $ProjectBacklog->projectList();
    }
    
    function backlogDisplay()
    {
        $ProjectBacklog = new ProjectBacklog();
        $ProjectBacklog->backlogDisplay();
    }
    
    function releaseArchive()
    {
        $ProjectBacklog = new ProjectBacklog();
        $ProjectBacklog->releaseArchive();
    }
    
    //Legacy
    function featureList()
    {
        $ProjectLegacy = new ProjectLegacy();
        $ProjectLegacy->featureList();
    }
    
    function backlogFeatureList()
    {
        $ProjectBacklog = new ProjectBacklog();
        $ProjectBacklog->featureList();
    }
    
    function projectList()
    {
        $ProjectLegacy = new ProjectLegacy();
        $ProjectLegacy->projectList();
    }
    
    /* action display
     * The main taskboard.
     */
    function taskboardDisplay()
    {
        require_once("Controller/Project/ProjectTaskboard.php");
        $ProjectTaskboard = new ProjectTaskboard();
        $ProjectTaskboard->display();
    }
    
    function generateTaskHTML($task_id, $mode, $hide=false)
    {
        require_once('Model/ProjectModel.php');
        $ProjectModel = new ProjectModel();
        require_once('View/BaseView.php');
        $BaseView = new BaseView();
        
        $task_information = $ProjectModel->getTaskInformation($task_id);
        
        $feature_id = $task_information['feature_id'];
        
        $html = "";
        if($mode == 'add'){
            $task_style = 'ui-widget-header';
            if($task_information['user_email'] == $_SESSION['user_email']){
                $task_style = "ui-state-default";
            }
            
            $task_information['user_email'] = $task_information['user_email'] ? $task_information['user_email'] : 'Nobody';
            if($hide){
                $hide_style="style='display:none'";
            }else{
                $hide_style='';
            }
            $html .= "<div id='drag_{$task_id}_{$feature_id}' title='Assigned to {$task_information['user_email']}' class='{$task_style} ui-corner-all drag' priority='{$task_information['priority']}' status='{$task_information['status']}' {$hide_style} onMouseOver=\"$(this).css('border-color', $(this).attr('default_border_color'))\">";
            $html .= "
                <script language='javascript'>
                    $('#drag_{$task_id}_{$feature_id}').attr('default_border_color', $('#drag_{$task_id}_{$feature_id}').css('border-color'));
                </script>
                ";
        }else{          
            $task_style = 'ui-widget-header';
            if($task_information['user_email'] == $_SESSION['user_email']){
                $task_style = 'ui-state-default';
            }
            
            //So that its not blank if no user is assigned
            $extra_style = 'color:#CCBB60';
            if(!$task_information['user_email']){
                $task_information['user_email'] = 'Nobody';
                $extra_style = '';
            }
            
            $html .= "
            <script language='javascript'>
                $('#drag_{$task_id}_{$feature_id}').attr('title', 'Assigned to {$task_information['user_email']}');
                $('#drag_{$task_id}_{$feature_id}').removeClass('ui-widget-header ui-state-default');
                $('#drag_{$task_id}_{$feature_id}').addClass('{$task_style}');
                $('#drag_{$task_id}_{$feature_id}').attr('default_border_color', $('#drag_{$task_id}_{$feature_id}').css('border-color'));
            </script>
            ";
        }
        
        //Hack to get wordwrapp going.(for firefox)
        $description_array = str_split($task_information['description'], 20);
        $description_final = '';
        foreach($description_array AS $characters){
            $description_final .= "{$characters}&#8203;";
        }
        
        $description = $BaseView->limit_text($description_final, 75);
        $title = ucwords(strtolower($BaseView->limit_text($task_information['title'], 20)));
        //All the '&nbsp's are for aligning the 'Edit' button. 
        $html .=  "<strong style='font-size:8pt'>{$title}</strong> &nbsp&nbsp
            ";
        if($disabled){
            $html .= "
            <span style='color:white;' title='Task is disabled due to feature being impeded.'>Edit</span>
            "; 
        }else{
            if($_SESSION['is_admin'] || $_SESSION['user_email'] == $task_information['added_by']){
                $html .= "<button style='position: absolute; top: 1; right: 41; font-size:5pt;' onclick=\"openTaskDialog({$task_id}, {$feature_id},'".mysql_escape_string(str_replace('"','`',$task_information['title']))."', '".mysql_escape_string(str_replace('"','`', $task_information['description']))."', '{$task_information['user_email']}', '{$task_information['md5']}', '{$task_information['estimated_hours']}');\" title='Click here to view or edit the task.'>?</button>";
                $html .= "<button style='position: absolute; top: 1; right: 21; font-size:5pt;' onclick=\"openHoursDialog({$task_id});\" title='Click here to manage the hours done on this task.'>H</button>";
                $html .= "
                <span style='position: absolute; top: 1; right: 1; font-size:5pt'><button onclick='deleteTask({$task_id}, {$feature_id})'>X</button></span>
                "; 
            }else{
                $html .= "<button style='position: absolute; top: 1; right: 1; font-size:5pt;' onclick=\"openTaskDialog({$task_id}, {$feature_id},'".mysql_escape_string(str_replace('"','`',$task_information['title']))."', '".mysql_escape_string(str_replace('"','`', $task_information['description']))."', '{$task_information['user_email']}', '{$task_information['md5']}', '{$task_information['estimated_hours']}');\" title='Click here to view or edit the task.'>?</button>";
                $html .= "<button style='position: absolute; top: 1; right: 21; font-size:5pt;' onclick=\"openHoursDialog({$task_id});\" title='Click here to manage the hours done on this task.'>H</button>";
            }
        }
        
        $timestamp = $ProjectModel->getReleaseTaskTimeStamp($task_information['release_id']);
        
        $html .= "
            <br />
            <span style='font-size:8pt; font-weight:normal'>
            {$description}
            </span>
            <script>
                $('button').button();
                $('#timestamp').val('{$timestamp}');
            </script>
            ";
            
        if($mode=='add'){
            $html.= "
                </div> 
                <div style='height:69px' id='hackdiv_{$task_id}_{$feature_id}'></div>
                <script>
                    $('#drag_{$task_id}_{$feature_id}').draggable();	
                    $('#drag_{$task_id}_{$feature_id}').draggable('option', 'containment', 'window');	
                    $('#drag_{$task_id}_{$feature_id}').draggable('option', 'revert', 'invalid');	
                    moveDragObject($('#drag_{$task_id}_{$feature_id}'), $('#dropNotStarted_{$feature_id}'));
                    
                        task_json_object.push(
                            {\"id\": \"{$task_id}\",
                            \"md5\": \"{$task_information['md5']}\",
                            \"status\": \"{$task_information['status']}\",
                            \"priority\": \"{$task_information['priority']}\"}
                            );
                        ajax_active = false;
                </script>
            ";
        }
        
        return $html;
    }
    
    
    /* action editProjectDetails. 
     * If no id is supplied, it assumes that you are adding a new one.
     */
    function projectEdit()
    {
        //Handle Post and save
        if(isset($_POST['save'])){
        
            $_POST = $this->makePostSafe($_POST);

            if(!$this->validate('title', $_POST['title'])){
                $errors[] = ("You did not enter enough characters into the title field.");
            }
            if(!$this->validate('description', $_POST['description'])){
                $errors[] =("You did not enter enough characters into the description field.");
            }

            //If is new, add the project, else update the project
            if(!is_array($errors)){
                if(!$_GET['project_id']){
                    $success = $this->ProjectModel->addProject($_POST['title'], $_POST['description'], $_POST['start_date'], $_SESSION['company_id'], $_SESSION['team_id']);
                }else{
                    $success = $this->ProjectModel->updateProject($_GET['project_id'], $_POST['title'], $_POST['description'], $_POST['start_date'], $_SESSION['company_id']);
                }
            }
                
            if($success){
                $_SESSION['show_message'] = 'Project saved successfully.';
                header('location:'.$_SESSION['previous_url']);
                exit;
            }else{
                //throw new exception('Insert/Update failed on the project. Please check that you have entered the correct information');
            }
        }

        //If project_id, edit. Else Add.
        if ($_GET['project_id']){
            $project_id = $_GET['project_id'];
            $project_details = $this->ProjectModel->getProjectInformation($project_id);
        }else{
            //New project being added
        }

        ob_start();

        echo "<button onClick=\"loadPage('index.php?Controller=Project&Action=BacklogProjectList');\" class='ui-state-default ui-corner-all' title='Click here to go back and list the projects for this company.'>List Projects</button>";
        ?>

        <form method='POST'>
        
        <table class='mainEditGrid' name='ProjectEdit' style='width:500px; margin-right:auto; margin-left:auto'> 
        <tr></tr><th colspan=2 height=25px>Project details</th></tr>
        <tr><td> &nbsp </td></tr>
        <tr><td><table class='mainEditGrid' style='width:90%;'>
        <tr>
        <td>Project title</td>
        <td><input type='text' style='width:300px' id='title' name='title' value='<?php echo $_POST['title']; ?>' onBlur='checkValid("title", this, "Project title")'/></td>
        </tr> 
        <tr>
        <td>Project description</td>
        <td><textarea rows=4 style='width:300px;' id='description'name='description' onBlur='checkValid("description", this, "Project description")'><?php echo $_POST['description']; ?></textarea></td>
        </tr>
        <tr><td>Start Date</td><td>
        <input id='start_date' name='start_date' style='width:300px;' value='<?php echo $_POST['start_date']; ?>' type='date' />
        </td></tr>
        <tr><td colspan='2'><br/><center>
        <input type='submit' style='width:80px' class='ui-widget-header ui-corner-all' name='save' id='save' value='Save' title='Click here to check and save the project.'>
        </center><br/></td></tr>

        </form>
        </td></tr></table>
        </table>
        <script language='javascript'>
        var errors = new Array();
        
        $(document).ready(function(){
            for(var i in errors){
                $('#freeow').freeow('Error',errors[i],{
                    classes: ["gray", "error"],
                    autoHide: 0
                });
            }
        });
        
       <?php
        
        if(is_array($errors)){
            foreach($errors AS $key=>$error_message){
                echo "errors['{$key}'] = '{$error_message}';";
            }
        }
        
        //If edit mode, put the details on the page
        if(is_array($project_details)){

            //Go through each of the details retrieved from the database and set the elements to the values for edit
            foreach($project_details AS $detail=>$value){
                if($detail == 'start_date' && $value == '0000-00-00'){
                    continue;
                }

                $value = mysql_escape_string($value);
                echo "$('#{$detail}').val('{$value}'); ";
            }
        }
        ?>
        </script>
        <?php

        $content = ob_get_contents();
        ob_clean();

        if(is_array($project_details)){
            $this->view->setTitle("Edit project: {$project_details['title']}");
        }else{
            $this->view->setTitle("Insert a new project");
        }
        $this->view->render($content);
    }

    /* action editFeatureDetails. 
     * If no id is supplied, it assumes that you are adding a new one.
     */
    function featureEdit()
    {
        //Handle Post and save
        if(isset($_POST['save'])){
            //echo "<pre>";print_r($_POST);exit;
        
            $_POST = $this->makePostSafe($_POST);
            
            session_start();
            
            if(!$this->validate('title', $_POST['title'])){
                $errors[] = ("You did not enter enough characters into the title field");
            }
            if(!$this->validate('description', $_POST['description'])){
                $errors[] =("You did not enter enough characters into the description field");
            }

            //If is new, add feature, else update feature
            if(!is_array($errors)){
                if(!$_GET['feature_id']){
                    $success = $this->ProjectModel->addFeature($_POST['title'], $_POST['description'], $_POST['status'], $_GET['project_id']);
                }else{
                    $success = $this->ProjectModel->updateFeature($_GET['feature_id'], $_POST['title'], $_POST['description'], $_POST['status'], $_GET['project_id']);
                }
            }
                
            if($success){
                $_SESSION['show_message'] = 'Feature saved successfully.';
                header('location:'.$_SESSION['previous_url']);
                exit;
            }else{
                //throw new exception('Insert/Update failed on the feature. Please check that you have entered the correct information.');
            }
        }

        //If feature_id, edit. Else Add.
        if ($_GET['feature_id']){
            $feature_id = $_GET['feature_id'];
            $feature_details = $this->ProjectModel->getFeatureInformation($feature_id);
        }else{
            //New feature being added
        }

        ob_start();

        echo "<button onClick=\"loadPage('index.php?Controller=Project&Action=BacklogDisplay&project_id={$_GET['project_id']}');\" class='ui-state-default ui-corner-all' title='Click here to go to the project backlog.'>Project Backlog</button>";
        ?>

        <form method='POST'>

        <table class='mainEditGrid' name='Feature edit' style='width:500px; margin-right:auto; margin-left:auto'>
        <tr></tr><th colspan=2 height=25px>Feature details</th></tr>
        <tr><td> &nbsp </td></tr>
        <tr><td><table class='mainEditGrid' style='width:90%;'>
        <tr>
        <td>Feature title</td>
        <td><input type='text' style='width:300px' id='title' name='title' value='<?php echo $_POST['title']; ?>' onBlur='checkValid("title", this, "Feature title")' /></td>
        </tr> 
        <tr>
        <td>Feature description</td>
        <td><textarea rows=4 style='width:300px' id='description' name='description' onBlur='checkValid("description", this, "Feature description")'><?php echo $_POST['description']; ?></textarea></td>
        </tr>
        <tr>
        <td>Feature status</td>
        <td><select style='width:300px' id='status' name='status'>
            <option value='Auto'>Auto Detect</option>
            <option value='Impeded'>Impeded</option>
        </select></td> 
        </tr> 
        <tr><td colspan='2'><br/><center>
        <input type='submit' style='width:80px;' class='ui-state-default ui-corner-all' name='save' id='save' value='Save' title='Click here to check and save the feature.'>
        </center><br/></td></tr>

        </form>
        </td></tr></table>
        </table>
        <script language='javascript'>
        var errors = new Array();
        
        $(document).ready(function(){
            for(var i in errors){
                $('#freeow').freeow('Error',errors[i],{
                    classes: ["gray", "error"],
                    autoHide: 0
                });
            }
            
        });
        
       <?php
        if($_POST['status']){
            echo "$('#status').val('{$_POST['status']}');";
        }

        if(is_array($errors)){
            foreach($errors AS $key=>$error_message){
                echo "errors['{$key}'] = '{$error_message}';";
            }
        }
        //If edit mode, put the details on the page
        if(is_array($feature_details)){

            //Go through each of the details retrieved from the database and set the elements to the values for edit
            foreach($feature_details AS $detail=>$value){
                //Check status, so that if its anything except impeded, 
                //it will set the drop down to Auto
                if($detail == 'status' && $value != 'Impeded'){
                    $detail = 'Auto';
                }
                $value = mysql_escape_string($value);
                echo "$('#{$detail}').val('{$value}'); ";
            }
        }
        ?>
        </script>
        <?php

        $content = ob_get_contents();
        ob_clean();

        if(is_array($feature_details)){
            $this->view->setTitle("Edit feature: {$feature_details['title']}");
        }else{
            $this->view->setTitle("Insert a new feature");
        }
        $this->view->render($content);
    }

    /*
     * If no id is supplied, it assumes that you are adding a new one.
     */
    function releaseEdit()
    {
        //Handle Post and save
        if(isset($_POST['save'])){
            //echo "<pre>";print_r($_POST);exit;
        
            $_POST = $this->makePostSafe($_POST);
            
            session_start();

            if(!$this->validate('title', $_POST['title'])){
                $errors[] = "You did not enter enough characters into the title field";
            }


            if($_POST['estimated_completion_date'] < $_POST['start_date']){
                $errors[] = "Estimated Completion date is before the Start Date!";
            }
            //If is new, add feature, else update feature
            if(!is_array($errors)){
                if(!$_GET['release_id']){
                    $success = $this->ProjectModel->addRelease($_POST['title'], $_POST['start_date'],  $_POST['estimated_completion_date'], $_GET['project_id']);
                }else{
                    $success = $this->ProjectModel->updateRelease($_GET['release_id'], $_POST['title'], $_POST['start_date'],  $_POST['estimated_completion_date'], $_GET['project_id']);
                }
            }
                
            if($success){
                $_SESSION['show_message'] = 'Release saved successfully.';
                header('location:'.$_SESSION['previous_url']);
                exit;
            }else{
                //throw new exception('Insert/Update failed on the release. Please check that you have entered the correct information.');
            }
            

        }

        //If feature_id, edit. Else Add.
        if ($_GET['release_id']){
            $release_id = $_GET['release_id'];
            $release_details = $this->ProjectModel->getReleaseInformation($release_id);
        }else{
            //New release being added
        }

        ob_start();

        echo "<button onClick=\"loadPage('index.php?Controller=Project&Action=BacklogDisplay&project_id={$_GET['project_id']}');\" class='ui-state-default ui-corner-all' title='Click here to go back to the backlog for this project.'>Project Backlog</button>";
        ?>

        <form method='POST'>

        <table class='mainEditGrid' name='Release edit' style='width:500px; margin-right:auto; margin-left:auto'>
        <tr></tr><th colspan=2 height=25px>Release details</th></tr>
        <tr><td> &nbsp </td></tr>
        <tr><td><table class='mainEditGrid' style='width:90%;'>
        
        <tr>
        <td>Release Title</td>
        <td><input type='text' style='width:300px' id='title' name='title' value='<?php echo $_POST['title'] ?>' onBlur='checkValid("title", this, "Feature title")' /></td>
        </tr> 
        
        <tr><td>Start Date</td><td>
        <input id='start_date' name='start_date' style='width:300px;' value='<?php echo $_POST['start_date'] ?>' type='date' />
        </td></tr>
        
        <tr><td>Estimated Completion Date</td><td>
        <input id='estimated_completion_date' name='estimated_completion_date' style='width:300px;' type='date' value='<?php echo $_POST['estimated_completion_date'] ?>' />
        </td></tr>
        
        <tr><td colspan='2'><br/><center>
        <input type='submit' style='width:80px;' class='ui-state-default ui-corner-all' name='save' id='save' value='Save' title='Click here to check and save the feature.'>
        </center><br/></td></tr>

        </form>
        </td></tr></table>
        </table>
        <script language='javascript'>
        var errors = new Array();
        
        $(document).ready(function(){
            for(var i in errors){
                $('#freeow').freeow('Error',errors[i],{
                    classes: ["gray", "error"],
                    autoHide: 0
                });
            }
        });
        
       <?php
        
        if(is_array($errors)){
            foreach($errors AS $key=>$error_message){
                echo "errors['{$key}'] = '{$error_message}';";
            }
        }

        //If edit mode, put the details on the page
        if(is_array($release_details)){

            //Go through each of the details retrieved from the database and set the elements to the values for edit
            foreach($release_details AS $detail=>$value){
                $value = mysql_escape_string($value);
                echo "$('#{$detail}').val('{$value}'); ";
            }
        
            $value = mysql_escape_string($value);
            $this->view->setTitle("Edit release: {$release_details['title']}");
            
        }else{
            $this->view->setTitle("Insert a new release");
        }
        ?>
        </script>
        <?php

        $content = ob_get_contents();
        ob_clean();

        $this->view->render($content);
    }

    /* action editTaskDetails. 
     * If no id is supplied, it assumes that you are adding a new one.
     */
    function taskEdit()
    {
        //Handle Post and save
        if(isset($_POST['save'])){
            //print_r($_POST);exit;
            $_POST = $this->makePostSafe($_POST);
                    
            if(!$this->validate('title', $_POST['title'])){
                $errors[] = ("You did not enter enough characters into the title field");
            }
            if(!$this->validate('description', $_POST['description'])){
                $errors[] = ("You did not enter enough characters into the description field");
            }

            //If is new, add the task, else update the task.
            if(!is_array($errors)){
                if(!$_GET['task_id']){
                    $success = $this->ProjectModel->addTask($_POST['title'], $_POST['description'], $_POST['status'], $_GET['feature_id'], $_POST['user_email'], null, $_POST['estimated_hours']);
                }else{
                    $success = $this->ProjectModel->updateTask($_GET['task_id'], $_POST['title'], $_POST['description'], $_POST['status'], $_GET['feature_id'], $_POST['user_email'], null, $_POST['estimated_hours']);
                }
            }
        
            if($success){
                $_SESSION['show_message'] = 'Task saved successfully.';
                header("location:{$_SESSION['previous_url']}");
                exit;
            }else{
                //throw new exception('Insert/Update failed on the task. Please check that you have entered the correct information');
            }
            
        }

        //If task_id, edit. Else Add.
        if ($_GET['task_id']){
            $task_id = $_GET['task_id'];
            $task_details = $this->ProjectModel->getTaskInformation($task_id);
        }else{
            //New task being added
        }

        ob_start();

        if($_SESSION['is_admin']){
                    echo "<button onClick=\"loadPage('index.php?Controller=Project&Action=BacklogTaskList&project_id={$_GET['project_id']}&feature_id={$_GET['feature_id']}');\" class='ui-state-default ui-corner-all' title='Click here to go back and list the tasks for this feature.'>List Tasks</button>";
        }else{
            echo "<button onClick=\"loadPage('index.php?Controller=Project&Action=TaskboardDisplay');\" class='ui-state-default ui-corner-all' title='Click here go to the taskboard'>Project Taskboard</button>";
        }
        ?>

        <form method='POST'>

        <table class='mainEditGrid' name='TaskEdit' style='width:500px; margin-right:auto; margin-left:auto'>
        <tr><th colspan=2 height=25px>Task details</th></tr>
        <tr><td> &nbsp </td></tr>
        <tr><td><table class='mainEditGrid' style='width:90%;'>
        <tr>
        <td>Task title</td>
        <td><input type='text' style='width:300px' id='title' name='title' value='<?php echo $_POST['title']; ?>' onBlur='checkValid("title", this, "Task title")'/></td>
        </tr> 
        <tr>
        <td>Task description</td>
        <td><textarea rows=4 style='width:300px' id='description' name='description' onBlur='checkValid("description", this, "Task description")'><?php echo $_POST['description']; ?></textarea></td>
        </tr>
        <tr>
        <td>Task status</td>
        <td><select style='width:300px' id='status' name='status'>
            <option value='Not Started'>Not Started</option>
            <option value='Impeded'>Impeded</option>
            <option value='In Progress'>In Progress</option>
            <option value='Done'>Done</option>
        </select></td>
        </tr>
        <tr>
        <td>Assign To User</td>
        <td title='The user who will be doing this task'><select style='width:300px' id='user_email' name='user_email'>
        <option value='NULL' >||Unassigned||</option>
        
        <?php
        //Go through all the users for this company for it to be asigned to.
        if($_SESSION['company_admin_email'] == $_SESSION['user_email']){
            $teams = $this->ProjectModel->getTeamsForProject($_GET['project_id']);
            $users = array();
            if(is_array($teams)){
                foreach($teams AS $team_info){
                    $tmp_users = $this->UserModel->getUserList($_SESSION['company_id'], $team_info['id']);
                    if(is_array($tmp_users)){
                        $users = array_merge($users, $tmp_users);
                    }
                }
            }
        }else{
            $users = $this->UserModel->getUserList($_SESSION['company_id'], $_SESSION['team_id']);
        }

        foreach($users AS $id=>$user_information){
            if(!$_SESSION['is_admin'] && $_SESSION['user_email'] == $user_information['email']){
                echo "<option value = '{$user_information['email']}' selected='SELECTED'>{$user_information['display_name']} - {$user_information['email']}</option>";           
            }else{
                echo "<option value = '{$user_information['email']}'>{$user_information['display_name']} - {$user_information['email']}</option>";
            }
        }
        ?>
        
        </select></td>
        </tr>
        <tr height=50px>
            <td>Estimated Hours</td>
            <td><input type='text' id='estimated_hours' name='estimated_hours' style='width:300px' value=0 maxLength=3 onKeyPress='return validateNumeric(event);' /></td>
        </tr>
        <tr><td colspan='2'><br/><center>
        <input type='submit' style='width:80px;' class='ui-state-default ui-corner-all' name='save' id='save' value='Save' title='Click here to check and save the task.'>
        </center><br/></td></tr>
        </table>
        </td></tr></table>
        </form>
        <script language='javascript'>
        var errors = new Array();
        
        $(document).ready(function(){
            for(var i in errors){
                $('#freeow').freeow('Error',errors[i],{
                    classes: ["gray", "error"],
                    autoHide: 0
                });
            }
        });

        function validateNumeric(evt) {
            var theEvent = evt || window.event;
            var key = theEvent.keyCode || theEvent.which;
            //let through Backspace, Up, Down, Left, Right, Delete, F5
            if(key == 8 || key == 37 || key == 38 || key == 39 || key == 40 || key == 46 || key == 116){
                return;
            }
            key = String.fromCharCode( key );
            var regex = /[0-9]|\./;
            if( !regex.test(key) ) {
                theEvent.returnValue = false;
                if(theEvent.preventDefault) theEvent.preventDefault();
            }
        }
        
        <?php
        
        //If edit mode, put the details on the page
        if(is_array($task_details)){
            //Go through each of the details retrieved from the database and set the elements to the values for edit
            foreach($task_details AS $detail=>$value){
            
                $value = mysql_escape_string($value);
                echo "$('#{$detail}').val('{$value}'); ";
            }
        }
        
        if($_POST['status']){
            echo "$('#status').val('{$_POST['status']}');";
        }
        if($_POST['user_email']){
            echo "$('#user_email').val('{$_POST['user_email']}');";
        }
        
        if(is_array($errors)){
            foreach($errors AS $key=>$error_message){
                echo "errors['{$key}'] = '{$error_message}';";
            }
        }
        ?>
        </script>
        <?php

        $content = ob_get_contents();
        ob_clean();

        if(is_array($task_details)){
            $this->view->setTitle("Edit task: {$task_details['title']}");
        }else{
            $this->view->setTitle("Insert a new task");
        }
        $this->view->render($content);
        
    }
    
    /* action addImpediment. 
     * If no id is supplied, it assumes that you are adding a new one.
     */
    function impedimentAdd()
    {
        //Handle Post and save
        if(isset($_POST['save'])){
            //echo "<pre>";print_r($_POST);exit;
        
            $_POST = $this->makePostSafe($_POST);

            if(!$this->validate('title', $_POST['title'])){
                throw new exception("You did not enter enough characters into the title field");
            }
            if(!$this->validate('minimum_text', $_POST['description'])){
                throw new exception("You did not enter enough characters into the description field");
            }

            $success = $this->ProjectModel->addImpediment($_POST['title'], $_POST['description'], $_SESSION['user_email']);
                
            if(!$success){
                throw new exception('Insert failed on the impediment. Please check that you have entered the correct information.');
            }
            
            $_SESSION['show_message'] = 'Impediment saved successfully.';
            header("location:index.php?Controller=Project&Action=TaskboardDisplay&show_impediments=1");
            exit;
        }

        ob_start();

        ?>

        <form method='POST'>

        <table class='mainEditGrid' name='UserList' style='width:500px; margin-right:auto; margin-left:auto'>
        <tr></tr><th colspan=2 height=25px>Impediment details</th></tr>
        <tr><td> &nbsp </td></tr>
        <tr><td>
            <table class='mainEditGrid' style='width:90%;'>
                <tr>
                    <td>Impediment title</td>
                    <td><input type='text' style='width:300px' id='title' name='title' onBlur='checkValid("title", this, "Impediment title")' /></td>
                </tr> 
                <tr>
                    <td>Impediment description</td>
                    <td><textarea rows=4 style='width:300px' id='description' name='description' onBlur='checkValid("minimum_text", this, "Impediment description")'></textarea></td>
                </tr>
                <tr><td colspan='2'><br/><center>
                    <input type='submit' style='width:80px; height:30px' class='ui-state-default ui-corner-all' name='save' id='save' value='Save' title='Click here to check and save the impediment.'>
                </center><br/></td></tr>
            </table>
        </td></tr></table>
        </form>

        <?php

        $content = ob_get_contents();
        ob_clean();

        $this->view->setTitle("Insert a new impediment");

        $this->view->render($content);
    }



}
?>
