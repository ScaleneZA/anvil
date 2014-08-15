<?php
require_once("Controller/BaseController.php");
require_once("Model/ProjectModel.php");
require_once("Model/UserModel.php");

class ProjectTaskboard extends BaseController
{
    protected $ProjectModel;
    protected $UserModel;

    function __construct(){
        parent::__construct();

        $this->ProjectModel = new ProjectModel();
        $this->UserModel = new UserModel();
    }

    function display()
    {
        if ($_SESSION['company_id']){
            $company_id = $_SESSION['company_id'];
        }else{
            throw new exception("No company_id. Please log out and back in again.");
        }
        
        if (!$_SESSION['team_id']){
            throw new exception("You are not assigned to team, therefore you cannot access any projects, your Company Admin needs to assign you to a team for you to use Anvil.");
        }
    
        //Handle GET Params
        if (!empty($_GET['project_id'])){
            $selected_project_id = $_GET['project_id'];
            $this->ProjectModel->saveUserPreference($_GET['Controller'], $_GET['Action'], 'project_id', $selected_project_id, $_SESSION['user_email']);
        }else if (isset($_SESSION['preferences'][$_GET['Controller']][$_GET['Action']]['project_id'])){
            $selected_project_id = $_SESSION['preferences'][$_GET['Controller']][$_GET['Action']]['project_id'];
        }else{
            //throw new Exception("Invalid project ID. Please contact Anvil support");
            $selected_project_id = $this->getCurrentProjectId();
        }
        
        if (!empty($_GET['release_id'])){
            $selected_release_id = empty($_GET['release_id']) ? '0' : $_GET['release_id'];
            $this->ProjectModel->saveUserPreference($_GET['Controller'], $_GET['Action'], 'release_id', $selected_release_id, $_SESSION['user_email']);
        }else if (isset($_SESSION['preferences'][$_GET['Controller']][$_GET['Action']]['release_id'])){
            //print_r($_SESSION);exit;
            $selected_release_id = empty($_SESSION['preferences'][$_GET['Controller']][$_GET['Action']]['release_id']) ? '0' : $_SESSION['preferences'][$_GET['Controller']][$_GET['Action']]['release_id'];
        }else{
            $selected_release_id = self::getCurrentReleaseId($selected_project_id);
        }
        
        ob_start();		

        //Functions needed for some of the javascript events
        $this->generateExtraJavascriptDisplayFunctions();
   
        $project_array = $this->ProjectModel->getProjectArray($company_id, $_SESSION['team_id']);
        
        ?>
            <center>
            <!--<div class='ui-corner-all ui-widget-content' style='padding: 5px; width:50%' onmouseover="$('#current_selection').slideDown();" onmouseout="$('#current_selection').slideUp();"> -->
            <div class='ui-corner-all ui-widget-content box ui-state-default' style='padding: 5px; width:50%' id='current_selection'>
            <table style='width:100%; text-align:center; border:none; padding:0px; font-weight:normal' class='ui-state-default'>
            <tr>
                <td style='width:150px;text-align:right'>Working Project: </td>
                <td><select class='ui-widget-header' style='width:300px; font-weight:normal;' name='selectProject' id='selectProject' onChange='populateReleaseBox(this.value)'>
        <?php
            foreach($project_array AS $project_id=>$project_title)
            {
                if ($project_id == $selected_project_id){
                    echo "<option style='font-weight:normal' value='{$project_id}' selected='yes'>{$project_title}</option>";
                }else{
                    echo "<option style='font-weight:normal' value='{$project_id}'>{$project_title}</option>";
                }
            }
        ?>	
                </select></td>
            </tr>
            <tr>
                <td style='width:150px;text-align:right'>Release: </td>
                <td><select class='ui-widget-header' style='width:300px; font-weight:normal;' name='selectRelease' id='selectRelease' onChange='displayNewRelease(this.value, $("#selectProject").val())'>
        <?php
            //Getting the project_id
            if(!$selected_project_id){
                $project_keys = array_keys($project_array);
                $selected_project_id = $project_keys[0];
            }
            $release_array = $this->ProjectModel->getReleasesForProject($selected_project_id, true, true, false);
        
            if(is_array($release_array)){
                foreach($release_array AS $release_id=>$release_title){
                    if ($release_id == $selected_release_id){
                        echo "<option style='font-weight:normal' value='{$release_id}' selected='yes'>$release_title</option>";
                    }else{
                        echo "<option style='font-weight:normal' value='{$release_id}'>$release_title</option>";
                    }
                }
            }
            
        ?>
                </select></td>
            </tr>
            </table>
            </div>
            </center>
            
            <hr/>
            
            <div class='ui-corner-bottom' style='background:rgba(0, 0, 0, 0.8); padding:5px; position:fixed; top:0; width:990px; height:50px; z-index: 10; color:white; text-align:center; display:none;' id='notice'>
                <b style='color:red'>Notice:</b> There was a change to one or more tasks on the current page. <br/><input type='button' style='font-size:9pt' onclick='javascript: window.location.reload();' value='Refresh' /> <input type='button' style='font-size:9pt' onclick="javascript: $('#notice').slideUp();" value='Ignore' />
            </div>
            <input type='hidden' value='' id='timestamp' />
            
            <!--
                Task Edit popup
            -->
            <div id='editTaskDialog' title='View/Edit/Add Task' style='display:none'>
                <input type='hidden' value='' id='editTaskDialog-task_id' />
                <input type='hidden' value='' id='editTaskDialog-md5' />
                <input type='hidden' value='' id='editTaskDialog-feature_id' />
                <table class='mainEditGrid' name='TaskEdit' style='width:500px; margin-right:auto; margin-left:auto'>
                    <tr><th colspan=2 height=25px>Task details</th></tr>
                    <tr><td> &nbsp </td></tr>
                    <tr>
                        <td>
                            <table class='mainEditGrid' style='width:90%;'>
                                <tr>
                                    <td>Task title</td>
                                    <td><textarea rows=1 style='width:300px' id='editTaskDialog-task_title' name='editTaskDialog-task_title' onBlur='checkValid("title", this, "Task title")'></textarea></td>
                                </tr> 
                                <tr>
                                    <td>Task description</td>
                                    <td><textarea rows=11 style='width:300px' id='editTaskDialog-task_description' name='editTaskDialog-task_description' onBlur='checkValid("description", this, "Task description")'></textarea></td>
                                </tr>
                                <tr>
                                    <td>Assign To User</td>
                                    <td title='The user who will be doing this task'>
                                        <select style='width:300px' id='editTaskDialog-task_user' name='editTaskDialog-task_user'>
                                            <option value='NULL' >||Unassigned||</option>
        
                                            <?php
                                            //Go through all the users for this company for it to be asigned to.
                                            if($_SESSION['company_admin_email'] == $_SESSION['user_email']){
                                                $teams = $this->ProjectModel->getTeamsForProject($selected_project_id);
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
        
                                        </select>
                                    </td>
                                </tr>
                                <tr height=50px>
                                    <td>Estimated Hours</td>
                                    <td><input type='text' id='editTaskDialog-task_estimated_hours' style='width:300px' value=0 maxLength=3 onKeyPress='return validateNumeric(event);' /></td>
                                </tr>
                                <tr style='height:80px'>
                                    <td colspan=2>
                                    <center>
                                    <table style='width:40%; text-align:center'><tr>
                                    <td>
                                        <input type='button' style='width:80px; font-size:10pt' class='ui-state-default ui-corner-all' name='save' id='save' value='Save' onclick='saveTaskDetails();' title='Click here to check and save the task.' />
                                    </td>
                                    <td>
                                        <input type='button' style='width:80px; font-size:10pt' class='ui-state-default ui-corner-all' name='close' id='close' value='Close' onclick="closeTaskDetails();" title='Click here to check and save the task.' />
                                    </td>
                                    </tr></table>
                                    </center>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>

            <!--
                Task Hours popup
            -->
            <div id='hoursTaskDialog' title='Task hours management' style='display:none;'>
                <center><img id='hoursLoading' align=center src='img/loading.gif' style='display:none' /></center>
                <div id='hoursDialogDisplay' class='mainEditGrid ui-corner-all' style='display:none'>
                </div>
                <br/>
                <form id='hoursForm'>
                <input type='hidden' id='hours-task_id' name='hours-task_id' />
                <input type='hidden' id='hours-release_id' name='hours-release_id' />
                <table class='mainEditGrid ui-corner-all' name='TaskHours' style='width:350px; margin-right:auto; margin-left:auto; padding:10px' cellpadding=5>
                    <tr style='height:10px'></tr>
                    <tr>
                        <td align=right style='width:20%;'>User:</td>
                        <td title='The user who worked on this task.'>
                            <select style='width:250px' id='user' name='user'>
                                <?php
                                //Go through all the users for this company for it to be asigned to.
                                if($_SESSION['company_admin_email'] == $_SESSION['user_email']){
                                    $teams = $this->ProjectModel->getTeamsForProject($selected_project_id);
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
                                    if($_SESSION['user_email'] == $user_information['email']){
                                        echo "<option value = '{$user_information['email']}' selected='SELECTED'>{$user_information['display_name']} - {$user_information['email']}</option>";           
                                    }else{
                                        echo "<option value = '{$user_information['email']}'>{$user_information['display_name']} - {$user_information['email']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <td align=right style='width:20%'>Date:</td>
                        <td>
                            <input id='date' class='ui-corner-all' name='date' style='width:250px;' type='date' value='<?php echo date('Y-m-d') ?>' />
                        </td>
                    </tr>
                    <tr>
                        <td style='width:20%' align=right>Hours:</td>
                        <td>
                            <select style='width:250px' id='hours' name='hours'>
                                <?php
                                    for($x=1; $x<=24; $x++){
                                        echo "<option value='{$x}'>{$x}</option>";
                                    }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td align=center colspan=2><input style='font-size:9pt' type='submit' value='Add to task' /></td>
                    </tr>
                </table>
                </form>
            </div>
            <script>
              $('#hoursForm').submit(function(){
                  var data = $(this).serialize();
                  saveLoadTaskHours(data);
                  
                  return false;
              });
              
              function saveLoadTaskHours(data)
              {
                  $('#hoursLoading').show();
                  $('#hoursDialogDisplay').hide();
                  $.ajax({
                      url: "index.php?Controller=Ajax&Action=touchTaskHours&"+data,
                      success: function(msg){
                          $('#hoursLoading').hide();
                          $('#hoursDialogDisplay').html(msg);
                          $('input:button').button();
                          $('#hoursDialogDisplay').slideDown();
                      }
                  });
              }
              
              function deleteTaskHours(id, task_id)
              {
                  $('#hoursLoading').show();
                  $('#hoursDialogDisplay').slideUp();
                  $.ajax({
                      url: "index.php?Controller=Ajax&Action=deleteTaskHours&id="+id,
                      success: function(msg){
                          saveLoadTaskHours('hours-task_id='+task_id+'&hours-release_id='+$('#selectRelease').val());
                      },
                      error: function(){
                        $('#hoursTaskDialog').dialog('close');
                      }
                  });
              }
              </script>


            <table name='mainGrid' rules='all' class='taskboardGrid'>
            <tr style='font-size:18pt; font-weight:normal;'>
            <th>Features</th>
            <th colspan=4>Task Progress</th>
            </tr>
            <tr style='height:10px;'><td class='ui-widget-content' colspan=5></td></tr>
            
            <?php
            
            if(!$selected_release_id && is_array($release_array)){
                $release_keys = array_keys($release_array);
                $selected_release_id = $release_keys[0];
            }
            
            //Get the tasks for each feature in a nice 2D array
            $feature_array = $this->ProjectModel->getFeatureTaskArray($selected_release_id);
            $release_task_timestamp = $this->ProjectModel->getReleaseTaskTimeStamp($selected_release_id);
            $release_task_timestamp = $release_task_timestamp ?  $release_task_timestamp : '0';
            
            //echo "<pre>"; print_r($feature_array);exit;
            foreach($feature_array AS $feature_id=>$task_array){
                $information_array = $this->ProjectModel->getFeatureInformation($feature_id);	
                $feature_impeded = $information_array['status'];
                $disabled = '';
                if($feature_impeded == 'Impeded'){
                    $disabled = true;
                    $bg_color = 'background-color:#DDDDCC';
                    $impeded_bg_color = 'background-color:#DDDDCC';
                }else{
                    $bg_color = '';
                    $impeded_bg_color = 'background-color:#DEC2A2';
                }

                $feature_information = $this->getFeatureInformation($feature_id, $selected_project_id, $selected_release_id, $information_array, $disabled);

            ?>
            <tr style='display:none' id='show_feature<?php echo "_{$feature_id}"; ?>'>
                <td colspan=5>
                    <table style='width:100%'>
                        <tr>
                            <th style='height:50px; width:50px; text-align:center; font-size:11pt;'><button style='width:100%;height:90%' onclick="showhideFeature('feature<?php echo "_{$feature_id}"; ?>', 'show');" title='Expand this feature'>+</button></th>
                            <th style='font-size:15pt; text-align:left; padding-left:5px'><?php echo $information_array['title']; ?></th>
                            <th style='width:110px' id='show_feature_status_<?php echo "{$feature_id}"; ?>'><?php echo $information_array['status']; ?></th>
                        </tr>
                    </table>
                </td>
            </tr>
            
            <tr style='font-size:15pt; font-weight:normal;' id='hide_feature<?php echo "_{$feature_id}"; ?>'>
            <th style='width:30%; text-align:left; padding-left:5px'><input type='button' style='font-size:9pt; padding-left:5;padding-right:5;padding-top:0;padding-bottom:0;' value='-' onclick="showhideFeature('feature<?php echo "_{$feature_id}"; ?>', 'hide');"  title='Minimize this feature' /></th>
            <th style='width:17.5%;'>Not Started</th>
            <th style='width:17.5%; background-color:#BCA080;'>Impeded</th>
            <th style='width:17.5%;'>In Progress</th>
            <th style='width:17.5%;'>Done</th>
            </tr>
            
            <tr id='feature<?php echo "_{$feature_id}"; ?>'>
                <td style='<?php echo $bg_color; ?>' valign=top><br/><?php echo $feature_information; ?></td>
                <td id='dropNotStarted<?php echo "_{$feature_id}"; ?>' style='<?php echo $bg_color; ?>'>
                <!-- By default, put tasks into not started. Move if nessisary. -->
                <?php
                    require_once('Controller/ProjectController.php');
                    $ProjectController = new ProjectController();
                    if(count($task_array) >= 1){
                        foreach($task_array AS $task_id){
                            if(!$task_id){
                                continue;
                            }
                            
                            echo $ProjectController->generateTaskHTML($task_id, 'add', true);
                        }
                    }
                ?>
                </td>
                <td id='dropImpeded<?php echo "_{$feature_id}"; ?>'  style='<?php echo $impeded_bg_color; ?>'></td>
                <td id='dropInProgress<?php echo "_{$feature_id}"; ?>' style='<?php echo $bg_color; ?>'></td>
                <td id='dropDone<?php echo "_{$feature_id}"; ?>' style='<?php echo $bg_color; ?>'></td>
                </tr>
                <tr style='height:10px;'><td class='ui-widget-content' colspan=5></td></tr>

                <script>
                //Start of the very complex javascript to determine if a task has been dropped on a droppable space.
                //It also calls a function which updates the status of the task via Ajax.
                //
                //I had a lot of bugs in this section that I had to iron out.
                //Most of the bugs came from hardcoding the places to start and end in the substring. 
                //Changed to work dynamically with index of and lastIndexOf.
                    $(function() {
                        $( "#dropNotStarted<?php echo "_{$feature_id}"; ?>" ).droppable({
                            drop: function( event, ui ) {
                                if($(ui.draggable).get(0).id == 'editTaskDialog' || $(ui.draggable).get(0).id == ''){
                                    return;
                                }

                                if($(ui.draggable).get(0).id.substr(ui.draggable.get(0).id.lastIndexOf('_')+1) == '<?php echo $feature_id; ?>'){
                                    $( this ).effect('highlight',{color:'#FFFFFF'});

                                    moveDragObject(ui.draggable, this);

                                    task_id_start = ui.draggable.get(0).id.indexOf('_')+1;
                                    task_id_end =  ui.draggable.get(0).id.lastIndexOf('_')
                                    task_id = $(ui.draggable).get(0).id.substr(task_id_start, task_id_end - task_id_start);

                                    
                                    updateTaskStatus(task_id,<?php echo "{$feature_id}"; ?>,'Not Started');
                                }else{
                                    moveDragObject(ui.draggable, 'old_status');
                                }
                            }
                        });
                        $( "#dropImpeded<?php echo "_{$feature_id}"; ?>" ).droppable({
                            drop: function( event, ui ) {
                                if($(ui.draggable).get(0).id == 'editTaskDialog' || $(ui.draggable).get(0).id == ''){
                                    return;
                                }

                                if($(ui.draggable).get(0).id.substr(ui.draggable.get(0).id.lastIndexOf('_')+1) == '<?php echo $feature_id; ?>'){
                                    $( this ).effect('highlight',{color:'#FFFFFF'});

                                    moveDragObject(ui.draggable, this);

                                    task_id_start = ui.draggable.get(0).id.indexOf('_')+1;
                                    task_id_end =  ui.draggable.get(0).id.lastIndexOf('_')
                                    task_id = $(ui.draggable).get(0).id.substr(task_id_start, task_id_end - task_id_start);

                                    updateTaskStatus(task_id,<?php echo "{$feature_id}"; ?>,'Impeded');
                                }else{
                                    moveDragObject(ui.draggable, 'old_status');
                                }
                            }
                        });
                        $( "#dropInProgress<?php echo "_{$feature_id}"; ?>" ).droppable({
                            drop: function( event, ui ) {
                                if($(ui.draggable).get(0).id == 'editTaskDialog' || $(ui.draggable).get(0).id == ''){
                                    return;
                                }

                                if($(ui.draggable).get(0).id.substr(ui.draggable.get(0).id.lastIndexOf('_')+1) == '<?php echo $feature_id; ?>'){
                                    $( this ).effect('highlight',{color:'#FFFFFF'});

                                    moveDragObject(ui.draggable, this);

                                    task_id_start = ui.draggable.get(0).id.indexOf('_')+1;
                                    task_id_end =  ui.draggable.get(0).id.lastIndexOf('_')
                                    task_id = $(ui.draggable).get(0).id.substr(task_id_start, task_id_end - task_id_start);

                                    updateTaskStatus(task_id,<?php echo "{$feature_id}"; ?>,'In Progress');
                                }else{
                                    moveDragObject(ui.draggable, 'old_status');
                                }
                            }
                        });
                        $( "#dropDone<?php echo "_{$feature_id}"; ?>" ).droppable({
                            drop: function( event, ui ) {
                                if($(ui.draggable).get(0).id == 'editTaskDialog' || $(ui.draggable).get(0).id == ''){
                                    return;
                                }

                                if($(ui.draggable).get(0).id.substr(ui.draggable.get(0).id.lastIndexOf('_')+1) == '<?php echo $feature_id; ?>'){
                                    $( this ).effect('highlight',{color:'#FFFFFF'});

                                    moveDragObject(ui.draggable, this);

                                    task_id_start = ui.draggable.get(0).id.indexOf('_')+1;
                                    task_id_end =  ui.draggable.get(0).id.lastIndexOf('_')
                                    task_id = $(ui.draggable).get(0).id.substr(task_id_start, task_id_end - task_id_start);

                                    updateTaskStatus(task_id,<?php echo "{$feature_id}"; ?>,'Done');
                                }else{
                                    moveDragObject(ui.draggable, 'old_status');
                                }
                            }
                        });
                    });
                </script>
            <?php } ?>
        </table>

        <script language='javascript'>
            $(document).ajaxStart(function(){
                ajax_active = true;
            });
            
            $(document).ajaxStop(function(){
                ajax_active = false;
            }); 
        
           function deleteImpediment(impediment_id){
              if(confirm("Are you sure you want to resolve this Impediment? It will be removed from the list.")){
                 $.ajax({
                     url: "index.php?Controller=Ajax&Action=deleteImpediment&impediment_id="+impediment_id
                 });
                 $('#impediment_'+impediment_id).slideUp('slow');
              }
           }
            
           function closeTaskDetails(){

               $('#editTaskDialog').dialog('close');
           }
           
           function saveTaskDetails(){
               if(update_active){
                   update_active.abort();
               }
           
               task = $('#editTaskDialog-task_id').val();
               feature = $('#editTaskDialog-feature_id').val();
               task_title = $('#editTaskDialog-task_title').val();
               task_description = $('#editTaskDialog-task_description').val();
               task_user = $('#editTaskDialog-task_user').val();
               task_estimated_hours = $('#editTaskDialog-task_estimated_hours').val();
               md5 = $('#editTaskDialog-md5').val();
               
               //validate all fields
               if(!checkValid('title', $('#editTaskDialog-task_title').get(0), 'Task Title')){
                   return false;
               }
               if(!checkValid('description', $('#editTaskDialog-task_description').get(0), 'Task Description')){
                   return false;
               }
            
               $('#tabs').css('cursor', 'wait');
               //ajax_active = true;
               if(task){
                    $.ajax({
                       type: 'POST',
                       dataType: 'json',
                       url: "index.php?Controller=Ajax&Action=TaskEdit",
                       success: (function(data){
                            var md5_return = data.md5;
                            
                            if(data.conflict == 'CONFLICT'){
                                if(confirm("This task is conflicting with someone else's changes. Do you want to reload the page? Alternatively you can cancel this box and submit the task again to overwrite the conflict.")){
                                    window.location.reload();
                                }else{
                                    $('#editTaskDialog-md5').val(md5_return);
                                    $('#editTaskDialog').dialog('open');
                                    if(md5_return && md5_return != ''){
                                        var change_md5_array = Array();
                                        change_md5_array['id'] = task;
                                        change_md5_array['md5'] = md5_return;
                                        
                                        changeMD5Object(change_md5_array);
                                    }
                                }
                                //ajax_active = false;
                                return;
                            }
                            var change_md5_array = Array();
                            change_md5_array['id'] = task;
                            change_md5_array['md5'] = md5_return;
                            
                            changeMD5Object(change_md5_array);
                            
                            $('#drag_'+task+'_'+feature).html(data.html);
                            $('#tabs').css('cursor', 'default');
                            clearTaskDialog();
                            //ajax_active = false;
                       }),
                       error:(function(data){
                           $('#freeow').freeow('Ajax fail','A request to the server failed due to a network issue.',{
                               classes: ["gray", "error"]
                           });
                           $('#editTaskDialog').dialog('open');
                           alert("The save did not happen due to a network issue. Please try again.");
                           $('#tabs').css('cursor', 'default');
                       }),
                       data: {
                          task_id: task, 
                          md5: md5,
                          title: task_title,
                          description: task_description,
                          user: task_user,
                          estimated_hours: task_estimated_hours,
                          feature_id: feature,
                          release_id: <?php echo $selected_release_id; ?>
                        }
                    });
               }else{
                    $.ajax({
                       type: 'POST',
                       dataType: 'json',
                       url: "index.php?Controller=Ajax&Action=TaskEdit",
                       data: {
                          title: task_title,
                          description: task_description,
                          user: task_user,
                          estimated_hours: task_estimated_hours,
                          feature_id: feature,
                          add:'1',
                          release_id: <?php echo $selected_release_id; ?>
                        },
                        success: (function(data){
                            $('#dropNotStarted_'+feature).append(data.html);
                            touchAllTasks();
                            clearTaskDialog();
                            $('#tabs').css('cursor', 'default');
                        }),
                        error:(function(data){
                            $('#freeow').freeow('Ajax fail','A request to the server failed due to a network issue.',{
                               classes: ["gray", "error"]
                            });
                           $('#editTaskDialog').dialog('open');
                           alert("The save did not happen due to a network issue. Please try again.");
                           $('#tabs').css('cursor', 'default');
                       })
                    });
               }
               $('#editTaskDialog').dialog('close');
           }  

           function clearTaskDialog(){
               $('#editTaskDialog-task_id').val('');
               $('#editTaskDialog-md5').val('');
               $('#editTaskDialog-feature_id').val('');
               $('#editTaskDialog-task_title').val('');
               $('#editTaskDialog-task_description').val('');

               $('#editTaskDialog-task_user').val('');
           }
           
           //not used
           function handleTaskDialogDisable(enabled){
               if(enabled){
                   $('#editTaskDialog-task_title').attr('readonly', 'readonly');
                   $('#editTaskDialog-task_description').attr('readonly', 'readonly');
                   $('#editTaskDialog-task_user').attr('readonly', 'readonly');
               }else{
                   $('#editTaskDialog-task_title').attr('readonly', '');
                   $('#editTaskDialog-task_description').attr('readonly', '');
                   $('#editTaskDialog-task_user').attr('readonly', '');
               }
           }
           
           function openTaskDialog(task_id, feature_id, task_title, task_description, task_user, md5, task_estimated_hours){
               if(task_id){
                   $('#editTaskDialog-task_id').val(task_id);
                   $('#editTaskDialog-md5').val(md5);
                   $('#editTaskDialog-feature_id').val(feature_id);
                   task_title = task_title.replace(new RegExp('`', 'g'), '"');
                   $('#editTaskDialog-task_title').val(task_title);
                   task_description = task_description.replace(new RegExp('`', 'g'), '"');
                   $('#editTaskDialog-task_description').val(task_description);

                   $('#editTaskDialog-task_user').val(task_user);
                   $('#editTaskDialog-task_estimated_hours').val(task_estimated_hours);
               }else{
                   //clearTaskDialog();
                   $('#editTaskDialog-feature_id').val(feature_id);
               }
               $('#editTaskDialog').dialog('open');
           }
           
            function openHoursDialog(task_id){
               saveLoadTaskHours('hours-task_id='+task_id+'&hours-release_id='+$('#selectRelease').val());
               $('#hours-task_id').val(task_id);
               $('#hours-release_id').val($('#selectRelease').val());
               $('#hoursTaskDialog').dialog('open');
           }
           
        // Javascript function to call Ajax to update a task status when it is dragged into a new status. 
        // Also updates the attribute on the task element.
        function updateTaskStatus(task_id, feature_id, status){
            //ajax_active = true;
            old_status = $('#drag_'+task_id+'_'+feature_id).attr('status');
            if(status == 'Done'){
                //$('#drag_'+task_id+'_'+feature_id).effect('highlight', {color:'#cdc2a2'});
            <?php
                if (!isset($_SESSION['preferences']['All']['All']['disable_hours_popup'])){
                    echo "openHoursDialog(task_id);";
                }
              ?>
            } 
            
            if(status == old_status){
                return;
            }
            $('#drag_'+task_id+'_'+feature_id).css('cursor', 'wait');
            
            if(update_active){
                update_active.abort();
            }
            
            $.ajax({
               url: "index.php?Controller=Ajax&Action=saveTaskStatus&task_id="+task_id+"&status="+status+"&old_status="+old_status+'&release_dont_check='+<?php echo $selected_release_id; ?>,
               success: function(msg){
                    if(msg.indexOf(',') != -1){
                        time = msg.substr(msg.indexOf(',')+1);
                        msg = msg.substr(0, msg.indexOf(','));
                    }
                    $('#drag_'+task_id+'_'+feature_id).css('cursor', 'move');
                   if(msg.indexOf('CONFLICT') != -1){
                       moveDragObject($('#drag_'+task_id+'_'+feature_id), 'old_status');
                       if(confirm("Another user has updated that task since you last reloaded the page. Do you want to reload the page now? (You may move the task once the page has been reloaded)")){
                           window.location.reload();
                       }
                       //ajax_active = false;
                       return false;
                   }else{
                       $('#drag_'+task_id+'_'+feature_id).attr('status', status);
                       $('#feature_status_'+feature_id).fadeOut('fast', function(){
                           $('#feature_status_'+feature_id).html(' Status: <b>'+msg+'</b>');
                           $('#feature_status_'+feature_id).fadeIn('fast');
                       });
                       $('#show_feature_status_'+feature_id).html(msg);
                       $('#timestamp').val(time);
                       
                       var change_md5_array = Array();
                       change_md5_array['id'] = task_id;
                       change_md5_array['status'] = status;
                                        
                       changeMD5Object(change_md5_array);
                       //ajax_active = false;
                       return true;
                   }
               }
             });
        }
                        
        function deleteTask(task_id, feature_id, fake){           
            if(!feature_id){
                $('div[id*="drag_'+task_id+'"]').each(function(){
                    feature_id = $(this).get(0).id.substr($(this).get(0).id.lastIndexOf('_')+1);
                });
            }
            var confirmation = false;
            if(!fake){
                confirmation = confirm("Are you sure you want to delete this Task?");
            }else{
                confirmation = true;
            }
            
            if(confirmation){
                $('#tabs').css('cursor', 'wait');
                if(!fake){
                    if(update_active){
                        update_active.abort();
                    }
                    $.ajax({
                        url: "index.php?Controller=Ajax&Action=deleteTask&task_id="+task_id+'&release_dont_check='+<?php echo $selected_release_id; ?>,
                        success: function(msg){
                            $('#timestamp').val(msg);
                            $('#tabs').css('cursor', 'default');
                        }
                    });
                }else{
                    $('#tabs').css('cursor', 'default');
                }
                
                $('#drag_'+task_id+'_'+feature_id).remove();
                $('#hackdiv_'+task_id+'_'+feature_id).remove();
                        
                var change_md5_array = Array();
                change_md5_array['id'] = task_id;
                changeMD5Object(change_md5_array, true);
                
                var count = 1;
                $('div[id*="_'+feature_id+'"]').each(function(){
                    //alert($(this).attr('priority'));
                    if($(this).attr('priority') && $(this).get(0).id.indexOf('scroll') == -1){
                        //safty check to make sure we are not looking at task id instead of feature id
                        if($(this).get(0).id.substr($(this).get(0).id.lastIndexOf('_')+1) == feature_id){
                            var status = $(this).attr('status');
                            status = status.replace(' ','');
                    
                            $(this).attr('priority', count);
                            count++;
                        }
                    }
                });
                touchAllTasks();
            }
        }
        
            function showhideFeature(div_id, show_hide, exclude_ajax, init)
            {
                if(!init){
                    init = false;
                }
                if(show_hide == 'show'){
                    $('#show_'+div_id).hide();
                    $('#hide_'+div_id).show();
                    $('#'+div_id).show();
                    var value = '';
                }else{
                    $('#hide_'+div_id).hide();
                    $('#show_'+div_id).show();
                    $('#'+div_id).hide();
                    var value = 'hidden';
                }

                touchAllTasks(init);
                
                if(!exclude_ajax){
                    $.ajax({
                        type:'POST',
                        url: "index.php?Controller=Ajax&Action=SaveUserPreference",
                        data: {
                            controller: 'Project',
                            action: 'TaskboardDisplay',
                            key: div_id,
                            value: value
                        }
                    });
                }
            }
            
            function scrollFeatures(){
                $('div[id*="feature_scroll_"]').each(function(){
                    //alert($(this).attr('maxY'));
                    var y = $(window).scrollTop()-$(this).parent().offset().top,
                        minY = $(this).attr('minY'),
                        maxY = $(this).attr('maxY');
                        
                    if(y< maxY && y > minY){
                        //alert("Y:"+y+" maxY:"+maxY+" minY:"+minY+" scrollHeight:"+scrollHeight+" Pos:"+$(this).css('marginTop'));
                        $(this)
                            .stop()
                            .animate({"marginTop": (y) + "px"}, "slow" );
                    }else if(y < minY){
                        $(this)
                            .stop()
                            .animate({"marginTop": ($(this).attr('minY')) + "px"}, "slow" );
                    }else if(y> maxY){
                        $(this)
                            .stop()
                            .animate({"marginTop": ($(this).attr('maxY')) + "px"}, "slow" );
                    }
                });
            }
                            
            var ajax_active = false;
            var update_active = false;
            $(window).load(function(){
                ajax_active = false;
            
                $(window).scroll(function(){ 
                    scrollFeatures();
                });
                
                $('#editTaskDialog').dialog({ 
                    autoOpen: false,
                    modal: true,
                    maxHeight: 325, 
                    minHeight: 325,
                    minWidth: 530,
                    maxWidth: 530,
                    close: function(event, ui){
                        task = $('#editTaskDialog-task_id').val();
                        if(task){
                            clearTaskDialog();
                        }
                    }
                });
                
                $('#hoursTaskDialog').dialog({ 
                    autoOpen: false,
                    modal: true,
                    maxHeight: 325, 
                    minHeight: 325,
                    minWidth: 530,
                    maxWidth: 530
                });
                
                $('#timestamp').val('<?php echo $release_task_timestamp; ?>');
                
                <?php
                    if (isset($_SESSION['preferences']['All']['All']['automatic_reload']) && $_SESSION['preferences']['All']['All']['automatic_reload'] == 'semi-auto'){
                  ?>
                    setInterval(function(){
                        
                        if($('#notice').is(":visible")){
                            //Do nothing
                        }else{
                            $.ajax({
                                type: 'POST',
                                cache: true,
                                url: "index.php?Controller=Ajax&Action=CheckTaskboardChanges",
                                data: {
                                    release_id: <?php echo $selected_release_id; ?>,
                                    release_task_timestamp: $('#timestamp').val()
                                },
                                success: (function(data){
                                    if(data == 'CHANGED'){
                                        $('#notice').slideDown();
                                    }
                                }),
                                error: {} //Disable the error
                            });
                        }
                    }, 30000); //30seconds
                <?php
                    }else if(!isset($_SESSION['preferences']['All']['All']['automatic_reload']) || $_SESSION['preferences']['All']['All']['automatic_reload'] == 'auto') {
                  ?>
                    setInterval(function(){
                            
                            //alert(ajax_active);
                            if(ajax_active){
                                return;
                            }
                            
                            update_active = $.ajax({
                                type: 'POST',
                                dataType: 'json',
                                cache: true,
                                url: "index.php?Controller=Ajax&Action=UpdateTaskboard",
                                data: {
                                    release_id: <?php echo $selected_release_id; ?>,
                                    task_json_object_send: task_json_object
                                },
                                success: (function(data){
                                    if(data){
                                        $.each(data, function(key, item){
                                            var change_md5_array = Array();
                                            if(item.type == 'new'){
                                                $('#dropNotStarted_'+item.feature_id).append(item.html);
                                                //alert('appended');
                                                //change_md5_array['id'] = item.id;
                                                //change_md5_array['md5'] = item.md5;
                                                //change_md5_array['status'] = item.status;
                                                //change_md5_array['priority'] = item.priority;
                                            }else if(item.type == 'update'){
                                                $('#drag_'+item.id+'_'+item.feature_id).html(item.html);
                                                change_md5_array['id'] = item.id;
                                                change_md5_array['md5'] = item.md5;
                                            }else if(item.type == 'status_ajust'){
                                                $('#drag_'+item.id+'_'+item.feature_id).attr('status', item.status);
                                                change_md5_array['id'] = item.id;
                                                change_md5_array['status'] = item.status;
                                            }else if(item.type == 'priority_ajust'){
                                                $.each(item.tasks, function(task_priority_id, task_priority){
                                                    $('#drag_'+task_priority_id+'_'+item.feature_id).attr('priority', task_priority);
                                                    change_md5_array['id'] = item.id;
                                                    change_md5_array['priority'] = item.priority;
                                                });
                                            }else if(item.type == 'delete'){
                                                deleteTask(item.id, null, true);
                                                //alert('Deleting task '+item.id);
                                            }
                                            $('#drag_'+item.id+'_'+item.feature_id).css('border-color', 'red');
                                            changeMD5Object(change_md5_array);
                                        });
                                        touchAllTasks();
                                    }
                                 }),
                                error: {} //Disable the error
                            });
                    }, 16000); //16seconds
                <?php
                    }
                  ?>
                
                <?php
                $force_init = 1;
                if(!empty($_SESSION['preferences']['Project']['TaskboardDisplay']) && is_array($_SESSION['preferences']['Project']['TaskboardDisplay'])){
                    foreach($_SESSION['preferences']['Project']['TaskboardDisplay'] AS $feature_id=>$value){
                        if(substr($feature_id, 0, 8) != 'feature_' || $value != 'hidden'){
                            continue;
                        }
                        $force_init = 0;
                        echo " showhideFeature('{$feature_id}', 'hide', true, true); ";
                    }
                }
                
                //go through each task and move to correct position
                if($force_init){
                    $task_array = null;
                    //echo "<pre>llll";print_r($feature_array);exit;
                    foreach($feature_array AS $feature_id=>$task_array){
                        foreach($task_array AS $task_id){
                            if(!$task_id){
                                continue;
                            }
                            $task_status = $this->ProjectModel->getTaskStatus($task_id);
                            ?>
                            moveDragObject($("#drag<?php echo "_{$task_id}_{$feature_id}"; ?>"), 'old_status', true);
                        <?php
                        }
                    }
                }
                
                ?>
            });
        </script>


        <?php
            $impediments = $this->ProjectModel->getImpediments($_SESSION['company_id']);
        ?>

        <br />
        <hr />
        <br />
        
        <center>
            <button onClick="$('#impedimentsBox').slideToggle();" class='ui-state-default ui-corner-all' title='Click here to show/hide the impediments section'>Show/Hide Impediments</button>
        </center>
        <br />

        <div id='impedimentsBox' style='padding:5px;'>
        <button onClick="loadPage('index.php?Controller=Project&Action=ImpedimentAdd');" class='ui-state-default ui-corner-all' title='Click here to add an impediment.'>Add Impediment</button>
        <br />
        <br />
        <table name='impedimentsTable' rules='all' class='mainGrid'>
        <tr><th colspan=4  style='padding:10px;'><center>Impediments list</center></th></tr>
        <tr>
        <th style='padding:10px;'>Title</th>
        <th style='padding:10px;'>Description</th>
        <th style='padding:10px;'>User</th>
        <th style='padding:10px; width:50px'>Resolve</th>
        </tr>
        <?php
        if(is_array($impediments)){
            foreach($impediments AS $impediment_object){
                $disabled = 'DISABLED';
                if($_SESSION['is_admin'] || strstr($impediment_object['user'], $_SESSION['user_email'])){
                    $disabled = '';
                }
                echo "
                    <tr id='impediment_{$impediment_object['id']}'>
                    <td style='font-size:10pt; font-weight:bold'>{$impediment_object['title']}</td>
                    <td style='font-size:10pt'><p>{$impediment_object['description']}</p></td>
                    <td style='font-size:10pt'>{$impediment_object['user']}</td>
                    <td style='font-size:10pt;'><center><button onClick='deleteImpediment({$impediment_object['id']});' style='font-size:8pt' title='Resolve this impediment.' {$disabled}>Resolve</button></center></td>
                    </tr>
                    ";
            }
        }
        ?>
        </table>
        </div>

        <?php
        if(empty($_GET['show_impediments'])){
            echo "
            <script language='javascript'>
            $('#impedimentsBox').hide();
            </script>
            ";
        }

        $content = ob_get_contents();
        ob_clean();

        $this->view->setTitle("Taskboard");
        $this->view->render($content);

    }    
    
    //Javascript functions that get called throughout the code. 
    //Including animation for dragging and dropping, etc.
    protected function generateExtraJavascriptDisplayFunctions()
    { ?>
        <!--<script type="text/javascript" src="js/jquery-jscroll.js"></script>
        <script type="text/javascript" src="js/jquery-ajax_que.js"></script>
        <script type="text/javascript" src="js/jquery.isonscreen.min.js"></script>-->
        
        <script>
        var task_json_object = Array();
        
        function changeMD5Object(array_values, delete_flag){
            for(var x in task_json_object){
                if(task_json_object[x].id == array_values['id']){
                    if(delete_flag){
                        //alert('deleting index: '+x+' task_id: '+task_json_object[x].id);
                        delete task_json_object[x];
                    }else{
                        
                        for(var y in array_values){
                            switch(y){
                                case 'priority':
                                    task_json_object[x].priority = array_values[y];
                                    break;
                                case 'md5':
                                    //alert(task_json_object[x].md5);
                                    task_json_object[x].md5 = array_values[y];
                                    //alert(array_values[y]);
                                    break;  
                                case 'status':
                                    task_json_object[x].status = array_values[y];
                                    break;
                            }
                        }
                    }
                }
            }
        }
        
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
        
        //Handles the Final location, and the animation to line the task up when it is dragged and dropped.
        function moveDragObject(element, location_element, init){

            //For reseting the task to its old position
            if(location_element == 'old_status'){
                //Location of old placeholder (eg: #drop_NotStarted1)
                location_id = '#drop'+$(element).attr('status').replace(' ','')+'_'+$(element).get(0).id.substr($(element).get(0).id.lastIndexOf('_')+1);
                location_element = $(location_id);
                //alert(location_id);
            }

            //Just making sure that it doesnt try and move relatively.
            var move_left = $(location_element).position().left;
            var move_top = $(location_element).position().top + 1 + ($(element).attr('priority') * 69) - 69;

            var object_id = $(element).get(0).id;
            
            var speed = 300;
            if(init){
                speed = 0;
            }

            $('#'+object_id).animate({left:move_left, top:move_top}, speed, 'swing');
            
            if(init){
                $('#'+object_id).fadeIn();
            }
        }
        

        //Reload the page with the new release as a GET Param. This will be handled on load.
        function displayNewRelease(release_id, project_id){
            window.location = 'index.php?Controller=Project&Action=TaskboardDisplay&release_id='+release_id+'&project_id='+project_id;
        }
        
        function populateReleaseBox(project_id){
            window.location = 'index.php?Controller=Project&Action=TaskboardDisplay&project_id='+project_id;
            return;
            
            //Pointless. 2 loads.
            $.ajax({
                url: "index.php?Controller=Ajax&Action=GetReleasesForProjectHTML&project_id="+project_id,
                success: function(msg){
                    $('#selectRelease').html(msg);
                    
                    displayNewRelease($('#selectRelease').val(), project_id);
                }
            });
        }

        
        function touchAllTasks(init)
        { 
            if(!init){
                init = false;
            }
            $('div[id*="_"]').each(function(){
                //alert($(this).attr('priority'));
                if($(this).attr('priority')){
                    var status = $(this).attr('status');
                    var current_feature_id = $(this).get(0).id.substr($(this).get(0).id.lastIndexOf('_')+1);
                    //alert(current_feature_id);
                    status = status.replace(' ','');
                    moveDragObject(this, $('#drop'+status+'_'+current_feature_id), init);
                }
            });
                
            redoFeatureScrolling();
        }
        
        function redoFeatureScrolling()
        {
            $('div[id*="feature_scroll_"]').each(function(){
                $(this).attr('minY', 0);
                $(this).attr('maxY', $(this).parent().height() - ($(this).height()+175));
            });
            scrollFeatures();
        }

        </script>
    <?php
    }

    //Return formatted text for the Feature information
    protected function getFeatureInformation($feature_id, $project_id, $release_id, $information_array, $disabled=false)
    {
        $text = '';
        if(!$information_array['status']){
            $information_array['status'] = 'No Tasks';
        }
        
        $description_array = str_split($information_array['description'], 27);
        $description_final = '';
        foreach($description_array AS $characters){
            $description_final .= "{$characters}&#8203;";
        }
        
        $title_array = str_split($information_array['title'], 19);
        $title_final = '';
        foreach($title_array AS $characters){
            $title_final .= "{$characters}&#8203;";
        }
        
        $information_array['title'] = $this->view->limit_text($title_final, 100);
        $information_array['description'] = $this->view->limit_text($description_final, 550);
        
        $text .= "<div id='feature_scroll_{$feature_id}' style='margin-top:0px;'>";
        $text .= "<span style='font-size:14pt'><b>{$information_array['title']}</b></span> <br/>
            ";
            
        $text .= "
            <span id='feature_status_{$feature_id}'> Status: <b>{$information_array['status']}</b></span>
            <br /><br />
            {$information_array['description']}
            <br />
            <br />
            ";
            
        if($disabled){
            $text .= "
            <input id='addTask_{$feature_id}' type='button' style='font-size:8pt' title='Add task is disabled, due to the feature being impeded.' DISABLED value='Add task' /><br /><br />
                ";
        }else{
            if($_SESSION['is_admin']){
                $text .= "<input type='button' style='font-size:8pt' title='Click here to ajust the priority of these tasks.' onclick=\"loadPage('index.php?Controller=Project&Action=BacklogTaskList&project_id={$project_id}&feature_id={$feature_id}')\" value='Ajust task priority' /><br /><div style='height:5px'></div>";
            }
            $text .= "<input type='button' style='font-size:8pt' title='Click here to add a task to this feature.' onclick='openTaskDialog(null, {$feature_id});' value='Add task' /><br/><br/>";
        }
        $text .= "</div>";
            
        return $text;
    }

    protected function getCurrentProjectId()
    {
        return $this->ProjectModel->getCurrentProjectId($_SESSION['company_id'], $_SESSION['team_id']);
    }
    protected function getCurrentReleaseId($project_id=null)
    {
        if(!$project_id){
            return;
        }

        return $this->ProjectModel->getCurrentReleaseId($project_id);
    }
    
}