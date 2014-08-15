<?php
require_once("Controller/BaseController.php");
require_once("Model/ProjectModel.php");

class projectLegacy extends BaseController
{
    protected $ProjectModel;
    protected $UserModel;

    function __construct(){
        parent::__construct();

        $this->ProjectModel = new ProjectModel();
        $this->UserModel = new UserModel();
    }

    /* action projectList
     * Lists the projects for a company.
     */
    function projectList()
    {
        //Handle GET Param
        if ($_SESSION['company_id']){
            $company_id = $_SESSION['company_id'];
        }else{
            throw new exception("No company_id. Please log out and back in again.");
        }
        
        $project_array = $this->ProjectModel->getProjectArray($company_id);

        ob_start();		

        ?>
            <button onClick="loadPage('index.php?Controller=Project&Action=ProjectEdit');" class='ui-state-default ui-corner-all' title='Click here to add a project.'>Add Project</button><hr />

            <table class='mainGrid' name='ProjectList' rules='all' style='border-style:solid; width:98%; margin-right:auto; margin-left:auto'>
            <tr>
            <th>Project Name</th>
            <th>Project Description</th>
            <th width='100px'>Start Date</th>
            <th width='175px'>Options</th>
            <th width=35px title='Current Project'>CP</th>
            </tr>
        <?php
        //Get the current working Project according to the company
        $current_working_project_id = $this->ProjectModel->getCurrentProjectId($_SESSION['company_id']);

        //Print the project information for each project to the list.
        if(is_array($project_array) && count($project_array) >= 1){
            foreach($project_array AS $project_id=>$project_title){
                $project_information = $this->ProjectModel->getProjectInformation($project_id);
                $checked = '';
                if($project_id == $current_working_project_id){
                    $checked = "checked = 'checked'";
                }
                echo "<tr id='project_{$project_id}'>";
                echo "<td>{$project_title}</td>"; 
                echo "<td>{$project_information['description']}</td>";
                echo "<td><center>{$project_information['start_date']}</center></td>";
                echo "<td style='text-align:center'>
                    <a style='color:brown; text-decoration: none;' href='index.php?Controller=Project&Action=ProjectEdit&project_id={$project_id}'>Edit</a> | 
                    <a style='color:brown;cursor:pointer; cursor:hand' onclick='deleteProject({$project_id})'>Delete</a> | 
                    <a style='color:brown; text-decoration: none;' href='index.php?Controller=Project&Action=FeatureList&project_id={$project_id}'>Features</a>
                      </td>";
                echo "<td><center><input name='currentProject' type='radio' onclick='setCurrentProjectId({$_SESSION['company_id']},{$project_id})' title='Click here to make this the current working project' {$checked} /></center></td>";
                echo "</tr>";
            }
        }else{
            echo "<tr><td colspan='5'><center><br/>There are no projects for your company.<br/><br/></center></td></tr>";
        }

        echo "</table>";

        ?>
            <script language = 'javascript'>
                //Delete the project via Ajax, but first confirm.
                function deleteProject(project_id){
                    if(confirm("Are you sure you want to delete this Project?")){
                        $.ajax({
                            url: "index.php?Controller=Ajax&Action=deleteProject&project_id="+project_id
                        });
                        $('#project_'+project_id).slideUp('slow');
                    } 
                }

                //Set the working project for the company via ajax
                function setCurrentProjectId(company_id, project_id){
                    $.ajax({
                        url: "index.php?Controller=Ajax&Action=setCurrentProjectId&project_id="+project_id+'&company_id='+company_id
                    });
                }
            </script> 
        <?php


        $content = ob_get_contents();
        ob_clean();

        $this->view->setTitle("List of projects");
        $this->view->render($content);

    }

    /* action featureList
     * Lists the features for a feature
     */
    function featureList()
    {
        //Handle GET Param
        if ($_GET['project_id']){
            $project_id = $_GET['project_id'];
            $project_title = $this->ProjectModel->getProjectInformation($project_id);
            $project_title = $project_title['title'];
        }else{
            $project_id = self::getCurrentProjectId();
            if (!$project_id){
                throw new exception('No project supplied. This may be an internal error, please contact support.');
            }
        }

        //Get the tasks for each feature in a nice 2D array
        $feature_array = $this->ProjectModel->getFeatureArray($project_id);

        ob_start();		

        echo "	<button onClick=\"loadPage('index.php?Controller=Project&Action=ProjectList');\" class='ui-state-default ui-corner-all' title='Click here to list your companies projects'>List Projects</button>
            <button onClick=\"loadPage('index.php?Controller=Project&Action=FeatureEdit&project_id={$_GET['project_id']}');\" class='ui-state-default ui-corner-all' title='Click here to add a feature to this project'>Add Feature</button>
            <hr />";
        ?>
            <table class='mainGrid' name='FeatureList' rules='all' style='border-style:solid; width:98%; margin-right:auto; margin-left:auto'>
            <tr>
            <th>Feature Title</th>
            <th>Feature Description</th>
            <th width='90px'>Priority</th>
            <th width='100px'>Status</th>
            <th width='175px'>Options</th>
            </tr>
        <?php

        //Print the feature information for each feature to the list.
        if(is_array($feature_array) && count($feature_array) > 0){
            foreach($feature_array AS $feature_id=>$feature_name){
                $feature_information = $this->ProjectModel->getFeatureInformation($feature_id);
                echo "<tr id='feature_{$feature_id}'>";
                echo "<td>{$feature_information['title']}</td>"; 
                echo "<td>{$feature_information['description']}</td>";
                echo "<td><center>{$feature_information['priority']}</center></td>";
                echo "<td><center>{$feature_information['status']}</center></td>";
                echo "<td style='text-align:center'>
                    <a style='color:brown; text-decoration: none;' href='index.php?Controller=Project&Action=FeatureEdit&project_id={$_GET['project_id']}&feature_id={$feature_id}'>Edit</a> |
                    <a style='color:brown; cursor:hand; cursor:pointer'  onclick='deleteFeature({$feature_id})'>Delete</a> | 
                    <a style='color:brown; text-decoration: none;' href='index.php?Controller=Project&Action=TaskList&project_id={$_GET['project_id']}&feature_id={$feature_id}'>Tasks</a>
                      </td>";
                echo "</tr>";
            }
        }else{
            echo "<tr><td colspan='5'><center><br />There are no features in this project.<br /><br /></center></td></tr>";
        }

        echo "</table>";
        
        ?>
            <script language = 'javascript'>
                function deleteFeature(feature_id){
                    if(confirm("Are you sure you want to delete this Feature?")){
                        $.ajax({
                            url: "index.php?Controller=Ajax&Action=deleteFeature&feature_id="+feature_id
                        });
                        $('#feature_'+feature_id).slideUp('slow');
                    }
                }
            </script> 
        <?php


        $content = ob_get_contents();
        ob_clean();

        $this->view->setTitle("Features for project: {$project_title}");
        $this->view->render($content);
    }

    /* action taskList
     * Lists the tasks for a task.
     */
    function taskList()
    {
        //Handle GET Param
        if ($_GET['feature_id']){
            $feature_id = $_GET['feature_id'];
            $feature_title = $this->ProjectModel->getFeatureInformation($feature_id);
            $feature_title = $feature_title['title'];
        }else{
            //Throw an error if no feature supplied
            throw new exception('No feature supplied. This may be an internal error, please contact support.');
        }

        $task_array = $this->ProjectModel->getTaskArray($feature_id);

        ob_start();		

        echo "	<button onClick=\"loadPage('index.php?Controller=Project&Action=ProjectList');\" class='ui-state-default ui-corner-all' title='Click here to list your companies projects'>List Projects</button>
            <button onClick=\"loadPage('index.php?Controller=Project&Action=FeatureList&project_id={$_GET['project_id']}');\" class='ui-state-default ui-corner-all' title='Click here to go back and list the features for this project.'>List Features</button>
            <button onClick=\"loadPage('index.php?Controller=Project&Action=TaskEdit&project_id={$_GET['project_id']}&feature_id={$_GET['feature_id']}');\" class='ui-state-default ui-corner-all' title='Click here to add a task to this feature'>Add Task</button>
            <hr />";
        ?>

            <table class='mainGrid' name='TaskList' rules='all' style='border-style:solid; width:98%; margin-right:auto; margin-left:auto'>
            <tr>
            <th width='110px'>Priority</th>
            <th>Task Name</th>
            <th>Task Description</th>
            <th width='125px'>Options</th>
            </tr>
        <?php

        //Print the task information for each task to the list.
        if(is_array($task_array) && count($task_array) > 0){
            foreach($task_array AS $task_id=>$task_title){
                $task_information = $this->ProjectModel->getTaskInformation($task_id);
                echo "<tr id='task_{$task_id}'>";
                echo "<td><center><a style='color:brown; cursor:hand; cursor:pointer' onclick='moveTaskUp({$task_id})'>[up]</a> {$task_information['priority']} <a style='color:brown; cursor:hand; cursor:pointer' onclick='moveTaskDown({$task_id})'>[down]</a></center></td>"; 
                echo "<td>{$task_title}</td>"; 
                echo "<td>{$task_information['description']}</td>";
                echo "<td style='text-align:center'>
                    <a style='color:brown; text-decoration: none;' href='index.php?Controller=Project&Action=TaskEdit&project_id={$_GET['project_id']}&feature_id={$_GET['feature_id']}&task_id={$task_id}'>Edit</a> | 
                    <a style='color:brown; cursor:hand; cursor:pointer' onclick='deleteTask({$task_id})'>Delete</a>
                    </td>";
                echo "</tr>";
            }
        }else{
            echo "<tr><td colspan='4'><center><br />There are no tasks in this feature.<br /><br /></center></td></tr>";
        }

        echo "</table>";

        ?>
            <script language = 'javascript'>
                function deleteTask(task_id){
                    if(confirm("Are you sure you want to delete this Task?")){
                        $.ajax({
                            url: "index.php?Controller=Ajax&Action=deleteTask&task_id="+task_id
                        });
                        $('#task_'+task_id).slideUp('slow');
                    }
                }
                
                //Move the task up in Priority
                function moveTaskUp(task_id){
                    $.ajax({
                        url: "index.php?Controller=Ajax&Action=moveTaskUp&task_id="+task_id,
                        success: function(msg){
                            window.location.reload();
                        }
                    });
                }

                //Move the task down in priority
                function moveTaskDown(task_id){
                    $.ajax({
                        url: "index.php?Controller=Ajax&Action=moveTaskDown&task_id="+task_id,
                        success: function(msg){
                            window.location.reload();
                        }
                    });
                }
            </script> 
        <?php

        $content = ob_get_contents();
        ob_clean();

        $this->view->setTitle("Tasks for feature: {$feature_title}");
        $this->view->render($content);
    }

}