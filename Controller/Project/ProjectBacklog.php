<?php
require_once("Controller/BaseController.php");
require_once("Model/ProjectModel.php");

class projectBacklog extends BaseController
{
    protected $ProjectModel;

    function __construct()
    {
        parent::__construct();
        $this->ProjectModel = new ProjectModel();
    }

    function backlogDisplay()
    {
        //Handle GET Param
        if ($_GET['project_id']){
            $project_id = $_GET['project_id'];
            $project_title = $this->ProjectModel->getProjectInformation($project_id);
            $project_title = $project_title['title'];
        }else{
            throw new exception('No project supplied. This may be an internal error, please contact support.');
        }
        
        ob_start();
        
        echo "	
            <button onClick=\"loadPage('index.php?Controller=Project&Action=BacklogProjectList');\" class='ui-state-default ui-corner-all' title='Click here to list your companies projects'>List All Projects</button>
            <button onClick=\"loadPage('index.php?Controller=Project&Action=ReleaseArchive&project_id={$_GET['project_id']}');\" class='ui-state-default ui-corner-all' title='Click here to go to the release archive.'>Archive</button>
            <!--<button onClick=\"loadPage('index.php?Controller=Project&Action=ProjectEdit&project_id={$_GET['project_id']}');\" class='ui-state-default ui-corner-all' title='Click here to edit the details of this project'>Edit Project</button>-->
            <button onClick=\"loadPage('index.php?Controller=Project&Action=BacklogFeatureList&project_id={$_GET['project_id']}');\" class='ui-state-default ui-corner-all' title='Click here to ajust the priority of the features in this project'>Ajust Feature Priority</button>
            <button onClick=\"loadPage('index.php?Controller=Project&Action=ReleaseEdit&project_id={$_GET['project_id']}');\" class='ui-state-default ui-corner-all' title='Click here to add a release to this project'>+ Add Release</button>
            <hr />
            ";
        
        echo "
        <table style='width:100%'>
        <tr>
        <td valign=top style='width:70%;'>
            <table style='width:95%'>
        ";

        //Get the tasks for each release in a nice 2D array
        $release_array = $this->ProjectModel->getReleaseArray($project_id, FALSE);
        
        //Print the feature information for each feature to the list.
        if(is_array($release_array) && count($release_array) > 0){
            foreach($release_array AS $release_id=>$release_name){
                $release_information = $this->ProjectModel->getReleaseInformation($release_id);
                echo "
                    <tr><td>
                    <div style='width:100%;' class='ui-corner-all ui-droppable mainEditGrid' id= 'release_{$release_id}'>
                        <table style='width:100%'>
                            <tr>
                                <td style='width:81%'><span style='font-size:14pt'><b>Release:</b> {$release_information['title']}</span></td>
                                <td align=left>{$release_information['start_date']} <b>to</b> {$release_information['estimated_completion_date']}</td>
                            </tr>
                            <tr>
                                <td style='width:50px'><input type='button' class='ui-state-default ui-corner-all' title=\"Click here to archive this release and it's attached features.\" style='font-size:9pt' value='Archive this' onClick='archiveRelease({$release_id});' /></td>
                                <td align=right><button onClick=\"loadPage('index.php?Controller=Project&Action=ReleaseEdit&release_id={$release_id}&project_id={$_GET['project_id']}');\" class='ui-state-default ui-corner-all' title='Click here to edit this release' style='font-size:9pt'>Edit</button>
                                <button onClick=\"deleteRelease({$release_id});\" class='ui-state-default ui-corner-all' title='Click here to delete this release' style='font-size:9pt'>Delete</button></td>
                            </tr>
                        </table>
                        <hr/>
                    <table>
                    <tr>
                    <td id='release_content_{$release_id}' div_count=0 next_top=0>
                    ";
                    
                    //Foreach feature in this release, put here.
                    
                echo "
                    </td></tr></table>
                    <table style='height:100px; width:100%;'class='mainGrid' id='release_drop_{$release_id}'>
                        <tr>
                            <td style='border-width:1px; border-style:solid; border-color:gray; color:gray; width:100%;'>
                                <center>Drop a feature here</center>
                            </td>
                        </tr>
                    </table>


                    </div>
                    </td></tr>
                    <tr><td><br/></td></tr>
                ";
                    
                ?>
                <script language='javascript'>
                $('#release_drop_<?php echo $release_id; ?>').droppable({
                    drop: function( event, ui ) {
                        $( this ).effect('highlight',{color:'#000000'});
                        
                        moveFeatureTo(ui.draggable, this);
                        
                        feature_id = ui.draggable.get(0).id;
                        feature_id = feature_id.substr(8);
                        release_id = $( this ).get(0).id;    
                        release_id = release_id.substr(13);
                        
                        updateFeatureReleaseId(feature_id, release_id);
                    }
                });
                
              </script>
            
                <?php
            }
        }else{
            echo "
                <tr><td>
                <div style='width:100%; color:#FFFFCC'>
                <span style='font-size:14pt'> No releases for this project</span>
                </div>
                </td></tr>
            ";
        }

        
        echo "
            </table>
        </td>
        <td valign=top>
        <div class='ui-corner-all ui-droppable mainEditGrid'>
        <table style='width:100%;'>
        <tr>
        <td style='border-width:0px;'>
        <span style='font-size:14pt'><b>Feature Backlog</b></span>
        </td><td align=right style='border-width:0px; width:150px'>
        <button onClick=\"loadPage('index.php?Controller=Project&Action=FeatureEdit&project_id={$_GET['project_id']}');\" class='ui-state-default ui-corner-all' title='Click here to add a feature to this project' style='font-size:9pt'>+ Add Feature</button>
        </td></tr><tr><td colspan=2  style='border-width:0px'>
        <hr/>
        </td></tr>
        </table>
            <table style='width:100%'>
            <tr><td id='feature_content' div_count=0  next_top=0>
            ";


        //Each feature not assigned to a release goes here

        echo "
                </td></tr>
                
                <tr><td>
                    <table style='height:100px; width:100%;'class='mainGrid' id='feature_drop'>
                        <tr>
                            <td style='border-width:1px; border-style:solid; border-color:gray; color:gray; width:100%'>
                                <center>Drop a feature here</center>
                            </td>
                        </tr>
                    </table>
                </td></tr>
            </table>
        </div>
        </td>
        </tr>
        </table>
        
        <script language='javascript'>
            $('#feature_drop').droppable({
                drop: function( event, ui ) {
                    $( this ).effect('highlight',{color:'#000000'});
                        
                    moveFeatureTo(ui.draggable, this);
                                            
                    feature_id = ui.draggable.get(0).id;
                    feature_id = feature_id.substr(8);
                    
                    updateFeatureReleaseId(feature_id, 'NULL');
                }
            });
        </script>

        ";       
        
        //Get the tasks for each feature in a nice 2D array
        $feature_array = $this->ProjectModel->getFeatureArray($project_id, NULL, FALSE);
        
        //Print the feature information for each feature to the list.
        if(is_array($feature_array) && count($feature_array) > 0){
            foreach($feature_array AS $feature_id=>$feature_name){
                $feature_information = $this->ProjectModel->getFeatureInformation($feature_id);
                
                $description_array = str_split($feature_information['description'], 40);
                $description_final = '';
                foreach($description_array AS $characters){
                    $description_final .= "{$characters}&#8203;";
                }
                $feature_information['description'] = $this->view->limit_text(str_replace("'", "`", $description_final), 230);
                
                $feature_title = $this->view->limit_text($feature_information['title'], 30);
                $feature_information['title'] = $this->view->limit_text($feature_information['title'], 70);
                echo "
    
                    <div class='ui-corner-all drag-feature ui-draggable big_title ui-state-default' title='
                    <span style=\"font-size:13pt\">{$feature_information['title']}</span><hr/>
                    {$feature_information['description']}<br/>
                    <b>Status:</b> {$feature_information['status']}<br/>
                    <b>Priority:</b> {$feature_information['priority']}
                    ' id='feature_{$feature_id}' onmousedown='hackHideTooltip();'>
                    <table width=100% class='ui-state-default' style='border:none; font-weight:normal'>
                    <tr>
                        <td colspan=3 style='font-size:12pt'>{$feature_title}</td>
                    </tr>
                    <tr>
                        <td width=32%></td>
                        <td  style='padding:2px; text-align:center'>
                            <button onClick=\"loadPage('index.php?Controller=Project&Action=BacklogTaskList&project_id={$_GET['project_id']}&feature_id={$feature_id}');\" class='ui-state-default ui-corner-all' title='Click here to lists the tasks for this feature' style='font-size:8pt'>Tasks</button>
                            <button onClick=\"loadPage('index.php?Controller=Project&Action=FeatureEdit&project_id={$_GET['project_id']}&feature_id={$feature_id}');\" class='ui-state-default ui-corner-all' title='Click here to edit this feature' style='font-size:8pt'>Edit</button>
                            <button onClick=\"deleteFeature({$feature_id})\" class='ui-state-default ui-corner-all' title='Click here to delete this feature' style='font-size:8pt'>Delete</button>
                        </td>
                    </tr>
                    </table>
                    </div>
                    
                ";
            ?>
            <script language='javascript'>
            function hackHideTooltip(){
                $("*.tooltip2").hide();
            }
            
              //Sorting out the limitations on the dragging
              $('#<?php echo "feature_{$feature_id}"; ?>').draggable();	
              //$('#<?php echo "feature_{$feature_id}"; ?>').draggable('option', 'containment', 'window');	
              $('#<?php echo "feature_{$feature_id}"; ?>').draggable('option', 'revert', 'invalid');	
              
           <?php
                if($feature_information['release_id']){
                    $move_script_array[] = " moveFeatureTo($('#feature_{$feature_id}'), $('#release_drop_{$feature_information['release_id']}'));";
                }else{
                    $move_script_array[] = " moveFeatureTo($('#feature_{$feature_id}'), $('#feature_drop'));";
                }
            ?>
              
            </script>
            <?php
            }
        }
        
        //Javascript for the Drag-N-Drop-ness
        ?>
        

        <script language='javascript'>
            $(window).load(function(){
                <?php
                    if(is_array($move_script_array)){
                        foreach($move_script_array AS $script_bit){
                            echo $script_bit;
                            echo "\n";
                        }
                    }
                  ?>
            });
            
            function archiveRelease(release_id){
                if(confirm('Are you sure you want to archive this release and its attached features?')){
                    $.ajax({
                        url: "index.php?Controller=Ajax&Action=ArchiveRelease&release_id="+release_id,
                        success: function(data){                        
                            $('div[last_div_id="release_content_'+release_id+'"]').each(function(){
                                $(this).remove();
                            });
                            $('#release_'+release_id).slideUp('fast', function(){
                                $('#release_'+release_id).remove();
                                shiftFeaturesUp('releases');
                            });

                        }
                    });
                }
            }
                
            function expandDiv(element){
                //alert('expanding '+$(element).get(0).id);
                var next_top = parseInt($(element).attr('next_top'));
                next_top += 69;

                $(element).attr('next_top', next_top);
                
                var element_id = $(element).get(0).id;

                div_count = parseInt($(element).attr('div_count'));
                div_count++;
                $(element).attr('div_count', div_count);
                $(element).html('');

                for(var x=0; x<div_count; x++){
                    $(element).html($(element).html()+"<div style='height:69px'></div>");
                }
            }
            
            function contractDiv(element){
                //alert('contracting '+$(element).get(0).id);
                var next_top = parseInt($(element).attr('next_top'));
                next_top -= 69;

                $(element).attr('next_top', next_top);
              
                div_count = parseInt($(element).attr('div_count'));
                div_count--;
                $(element).attr('div_count', div_count);
                $(element).html('');

                for(var x=0; x<div_count; x++){
                    $(element).html($(element).html()+"<div style='height:69px'></div>");
                }
            }
            
            function shiftFeaturesUp(div_type){
                if(div_type == 'releases'){
                    $('td[id*="release_content_"]').each(function(){
                        div_id = $(this).get(0).id;
                        slideObjectUp(div_id);
                    });
                }else{
                    slideObjectUp('feature_content');
                }
            }
            
            function slideObjectUp(div_id){
                //alert(div_id);
                    var move_top = $('#'+div_id).position().top;
                    var move_left = $('#'+div_id).position().left;
                    $('div[last_div_id="'+div_id+'"]').each(function(){
                        //alert(move_top);
                        $(this).animate({left:move_left, top:move_top}, 50, 'linear');
                        move_top += 69;
                    });
                    move_top -=69;
                    //alert('top = '+$('#'+div_id).position().top);
                    $('#'+div_id).attr('next_top', move_top);
            }
            
            function moveFeatureTo(drag_element, drop_element){
            
                var drop_element_id = $(drop_element).get(0).id;
                if(drop_element_id != 'feature_drop'){
                    drop_element_id = 'release_content'+drop_element_id.substr(12);
                }else{
                    drop_element_id = 'feature_content';
                }   
                
                //drop_element = $('#'+element_id).next();
            
                //alert($('#release_drop_<?php echo $release_id; ?>').attr('next_top'));
                if($('#'+drop_element_id).attr('next_top') == 0){
                    $('#'+drop_element_id).attr('next_top', parseInt($('#'+drop_element_id).position().top)-69);
                }

                expandDiv($('#'+drop_element_id));  
                
                if($(drag_element).attr('last_div_id')){

                    var last_element_id = $(drag_element).attr('last_div_id');
                    
                    contractDiv($('#'+last_element_id));  
                }
                
                //Just making sure that it doesnt try and move relatively.
                var move_left = $('#'+drop_element_id).position().left;
                var move_top = $('#'+drop_element_id).attr('next_top');
  
                
                var drag_element_id = $(drag_element).get(0).id;
                $('#'+drag_element_id).animate({left:move_left, top:move_top}, 300, 'swing');
            
                $(drag_element).attr('last_div_id', $('#'+drop_element_id).get(0).id);
                
                shiftFeaturesUp('releases');
                shiftFeaturesUp('feature');
                
                if($(drag_element).attr('last_div_id') && drop_element_id == $(drag_element).attr('last_div_id')){
                    //slideObjectUp(drag_element, 0);
                    return;
                }
                
            }  
            
            function updateFeatureReleaseId(feature_id, release_id)
            {
                $.ajax({
                   url: "index.php?Controller=Ajax&Action=UpdateFeatureReleaseId&release_id="+release_id+"&feature_id="+feature_id
                });
            }
            
            //Delete the project via Ajax, but first confirm.
            function deleteRelease(release_id){
                if(confirm("Are you sure you want to delete this release? All the features will go back into the feature backlog.")){
                    $.ajax({
                        url: "index.php?Controller=Ajax&Action=deleteRelease&release_id="+release_id
                    });
                    
                    $('#release_'+release_id).slideUp('slow', function(){
                        $('#release_'+release_id).remove();
                        shiftFeaturesUp('releases');
                    });
                    $('div[last_div_id="release_content_'+release_id+'"]').each(function(){
                        moveFeatureTo(this, $('#feature_drop'));
                    });
                    
                } 
            }
            
            function deleteFeature(feature_id){
                if(confirm("Are you sure you want to delete this Feature?")){
                    $.ajax({
                        url: "index.php?Controller=Ajax&Action=deleteFeature&feature_id="+feature_id
                    });

                    div_type = $('#feature_'+feature_id).attr('last_div_id');
                    $('#feature_'+feature_id).remove();
                    contractDiv($('#'+div_type));
                    if(div_type.substr(7) == 'feature'){
                        slideObjectUp('feature_content');
                    }else{
                        shiftFeaturesUp('releases');
                    }
                    shiftFeaturesUp(div_type);
                }
             }
       </script>
        
        <?php
        
        $content = ob_get_contents();
        ob_clean();

        $this->view->setTitle("Project backlog: {$project_title}");
        $this->view->render($content);
    }
    
    
    function projectList()
    {
        //Handle GET Param
        if ($_SESSION['company_id']){
            $company_id = $_SESSION['company_id'];
        }else{
            throw new exception("No company_id. Please log out and back in again.");
        }
        
        $project_array = $this->ProjectModel->getProjectArray($company_id, $_SESSION['team_id']);
        ob_start();		

        ?>
            <button onClick="loadPage('index.php?Controller=Project&Action=ProjectEdit');" class='ui-state-default ui-corner-all' title='Click here to add a project.'>+ Add Project</button><hr />

            <table class='mainGrid' name='ProjectList' rules='all' style='border-style:solid; width:98%; margin-right:auto; margin-left:auto;'>
            <tr>
            <th style='width:20%'>Project Name</th>
            <th style='width:80%'>Project Description</th>
            <!--<th width='100px'>Start Date</th>-->
            <th width='100px'>Options</th>
            </tr>
        <?php
        
        //Print the project information for each project to the list.
        if(is_array($project_array) && count($project_array) >= 1){
            foreach($project_array AS $project_id=>$project_title){
                $project_information = $this->ProjectModel->getProjectInformation($project_id);
                
                //Hack to get wordwrapp going.(for firefox)
                $description_array = str_split($project_information['description'], 95);
                $description_final = '';
                foreach($description_array AS $characters){
                    $description_final .= "{$characters}&#8203;";
                }
                
                echo "<tr id='project_{$project_id}'>";
                echo "<td>{$project_title}</td>"; 
                echo "<td>{$description_final}</td>";
                //echo "<td><center>{$project_information['start_date']}</center></td>";
                //echo "<td><center><input name='currentProject' type='radio' onclick='setCurrentProjectId({$_SESSION['company_id']},{$project_id})' title='Click here to make this the current working project' {$checked} /></center></td>";
                echo "<td style='text-align:center; padding:3px'>
                        <table class='mainEditGrid' >
                        <tr>
                        <td colspan=2 style='border-width:0px; border-style:none'><button style='font-size:8pt' onclick='loadPage(\"index.php?Controller=Project&Action=BacklogDisplay&project_id={$project_id}\")'>Backlog</button></td>
                        <td style='border-width:0px; border-style:none'><button style='font-size:8pt' onclick='loadPage(\"index.php?Controller=Project&Action=ProjectEdit&project_id={$project_id}\")'>Edit</button></td>
                        <td style='border-width:0px; border-style:none'><button style='font-size:8pt' onclick='deleteProject({$project_id}, {$_SESSION['team_id']})' title='Delete this project'>Delete</button></td>
                        </tr>
                        </table>
                      </td>";
                echo "</tr>";
            }
        }else{
            echo "<tr><td colspan='5'><center><br/>There are no projects for your company.<br/><br/></center></td></tr>";
        }

        echo "</table>";

        ?>
            <script language = 'javascript'>
                //Delete the project via Ajax, but first confirm.
                function deleteProject(project_id, team_id){
                    if(confirm("Are you sure you want to delete this Project?")){
                        $.ajax({
                            url: "index.php?Controller=Ajax&Action=deleteProject&project_id="+project_id+'&team_id='+team_id
                        });
                        $('#project_'+project_id).slideUp('slow');
                    } 
                }
            </script> 
        <?php


        $content = ob_get_contents();
        ob_clean();

        $this->view->setTitle("List of projects");
        $this->view->render($content);
    }
    

    function taskList()
    {
        $row_count =0;
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
        
        //Get the file needed for drag n drop table rows.
        ?>
        <script type="text/javascript" src="js/jquery.tablednd_0_5.js"></script>
        <?php

        echo "	
            <button onClick=\"loadPage('index.php?Controller=Project&Action=BacklogDisplay&project_id={$_GET['project_id']}');\" class='ui-state-default ui-corner-all' title='Click here go to the backlog feature'>Project Backlog</button>
            <button onClick=\"loadPage('index.php?Controller=Project&Action=TaskEdit&project_id={$_GET['project_id']}&feature_id={$_GET['feature_id']}');\" class='ui-state-default ui-corner-all' title='Click here to add a task to this feature'>+ Add Task</button>
            <hr />";
        ?>
        <center>
            <div id='btnSave' style='display:none'>
                <br/>
                <input type='button' value='Save' onclick='updateTaskPriorityOrder();' />
                <br/>
            </div>
            
            <div id='saveLoading' style='display:none'>
                <br/>
                <img src='img/loading.gif' />
                <br/>
            </div>
            
            <br/>
            
        </center>
        <table style='width:100%;'>
        <tr><td style='width:80px' valign=top>
            <table style='width:100%;border-collapse:collapse;' cellspacing="0" cellpadding="0" class='mainGrid' rules='all'>
            <tbody>
                <tr>
                    <th>Priority</th>
                </tr>
                <?php
                if(is_array($task_array) && count($task_array) > 0){
                    foreach($task_array AS $task_id=>$task_title){
                        $row_count++;
                        echo "<tr style='height:58px; border:4px solid #201913;' id='reference_{$row_count}'><td style='padding:10px; text-align:center;'>{$row_count}</td></tr>"; 
                    }
                }else{
                    echo "<tr><td><center><br />None<br /><br /></center></td></tr>";
                }
                ?>
            </tbody>
            </table>
        </td>
        <td  valign=top>
            <table style='width:100%;border-collapse:collapse;' cellspacing="0" cellpadding="0" class='mainGrid' id='taskList' name='taskList' rules='all'>
                <tbody>
                <tr style='cursor: normal;' class='nodrop nodrag'>
                    <th style='width:150px'>Task Title</th>
                    <th>Task Description</th>
                    <th style='width:80px; text-align:center'>Status</th>
                    <th style='width:125px'>Options</th>
                </tr>
        <?php

        //Print the task information for each task to the list.
        if(is_array($task_array) && count($task_array) > 0){
            foreach($task_array AS $task_id=>$task_title){
                $task_information = $this->ProjectModel->getTaskInformation($task_id);
    
                //Hack to get wordwrapp going.(for firefox)
                $description_array = str_split($task_information['description'], 20);
                $description_final = '';
                foreach($description_array AS $characters){
                    $description_final .= "{$characters}&#8203;";
                }

                echo "<tr style='height:50px; border:4px solid #201913;' title='Drag the task up or down to ajust the priority.' id='task_{$task_id}'>";
                echo "  <td style='padding:10px; width:75px'>{$task_title}</td>"; 
                echo "  <td style='padding:10px'>{$description_final}</td>";
                echo "  <td style='padding:10px; text-align:center'>{$task_information['status']}</td>";
                echo "  <td style='text-align:center'>
                            <input type='button' style='font-size:8pt' onclick=\"loadPage('index.php?Controller=Project&Action=TaskEdit&project_id={$_GET['project_id']}&feature_id={$_GET['feature_id']}&task_id={$task_id}');\" value='Edit' />
                            <input type='button' style='font-size:8pt' onclick='deleteTask({$task_id})' value='Delete' />
                        </td>";
                echo "</tr>";
            }
        }else{
            echo "<tr><td colspan='3'><center><br />There are no tasks in this feature.<br /><br /></center></td></tr>";
        }

        echo "
            </tbody>
            </table>
        </td></tr></table>";

        ?>
            <script language = 'javascript'>
            var max_row = <?php echo $row_count; ?>;
            var browser = '<?php echo strstr(strtolower($_SERVER['HTTP_USER_AGENT']), 'chrome') ? 'chrome' : 'firefox'; ?>';
            var new_order_string = '';
                function deleteTask(task_id){
                    if(confirm("Are you sure you want to delete this Task?")){
                        $.ajax({
                            url: "index.php?Controller=Ajax&Action=deleteTask&task_id="+task_id
                        });
                        $('#task_'+task_id).remove();
                        $('#reference_'+max_row).hide();
                        max_row = max_row-1;
                        redoTable();
                    }
                }
                
                function updateTaskPriorityOrder(){
                    if(new_order_string == ''){
                        return;
                    }
                    
                    $('#saveLoading').show();
                    
                    $.ajax({
                        type: 'POST',
                        url:'index.php?Controller=Ajax&Action=updateTaskPriorityOrder',
                        data: { OrderString: new_order_string},
                        success: function(){
                            new_order_string = '';
                            $('#saveLoading').hide();
                            $('#btnSave').slideUp();
                            //$('#featureList').fadeIn();
                        }
                    });
                }
                
                window.onbeforeunload = function() {
                    if(new_order_string != ''){
                        return "Are you sure you want to leave the page without saving the order the priority?";
                    }
                }
                
                $(window).load(function() {
                    redoTable();
                
                    $('#taskList').tableDnD({
                        onDrop: function(table, row) {
                            var rows = table.tBodies[0].rows;
                            var order_string = "";
                            for (var i=1; i<rows.length; i++) {
                                order_string += rows[i].id;
                                if(i !=(rows.length-1)){
                                    order_string += ',';
                                }
                            }
                            new_order_string = order_string;
                            $('#btnSave').slideDown();
                            redoTable();
                        }
                    });
                });
                
                function redoTable(){
                    count = 0;
                    $('#taskList tr').each(function(){
                        if(count != 0) {
                            height = $(this).css('height');
                            if(browser == 'chrome'){
                                height = height.replace('px', '');
                                height = parseInt(height) + 8;
                                height = height + 'px';
                            }
                            //alert(height);
                            $('#reference_'+count).css('height', height);
                        }
                        count++;
                    });
                }
            </script> 
        <?php

        $content = ob_get_contents();
        ob_clean();

        $this->view->setTitle("Tasks for feature: {$feature_title}");
        $this->view->render($content);
    }
    

    function featureList()
    {
        $row_count =0;
              //Handle GET Param
        if ($_GET['project_id']){
            $project_id = $_GET['project_id'];
            $project_title = $this->ProjectModel->getProjectInformation($project_id);
            $project_title = $project_title['title'];
        }else{
            //Throw an error if no project supplied
            throw new exception('No project supplied. This may be an internal error, please contact support.');
        }

        if ($_GET['feature_status']){
            $selected_feature_status = $_GET['feature_status'];
            $this->ProjectModel->saveUserPreference($_GET['Controller'], $_GET['Action'], 'feature_status', $selected_feature_status, $_SESSION['user_email']);
        }else if (isset($_SESSION['preferences'][$_GET['Controller']][$_GET['Action']]['feature_status'])){
            $selected_feature_status = $_SESSION['preferences'][$_GET['Controller']][$_GET['Action']]['feature_status'];
        }else{
            $selected_feature_status = 'NotStarted';
        }
        
        ob_start();		
        
        //Get the file needed for drag n drop table rows.
        ?>
        <script type="text/javascript" src="js/jquery.tablednd_0_5.js"></script>
        <?php

        echo "	
            <button onClick=\"loadPage('index.php?Controller=Project&Action=BacklogDisplay&project_id={$_GET['project_id']}');\" class='ui-state-default ui-corner-all' title='Click here go to the project backlog'>Project Backlog</button>
            <button onClick=\"loadPage('index.php?Controller=Project&Action=FeatureEdit&project_id={$_GET['project_id']}');\" class='ui-state-default ui-corner-all' title='Click here to add a feature to this project'>+ Add Feature</button>
            <hr />";
        ?>
        <center>
            <div class='ui-corner-all ui-widget-content' style='padding: 5px; width:45%' id='current_selection'>
            <table style='color:#FFFFCC;width:100%; text-align:center'>
            <tr>
                <td style='width:120px;text-align:right' title='Ajust priority for the feature status selected.'>Feature status: </td>
                <td>
                    <select class='ui-widget-header' style='width:300px; font-weight:normal;' name='selectFeatureStatus' id='selectFeatureStatus' onChange='window.location = "<?php echo "index.php?Controller=Project&Action=BacklogFeatureList&project_id={$_GET['project_id']}&feature_status="; ?>"+this.value'>
                        <option value='Done'>Done</option>
                        <option value='InProgress'>In Progress and Impeded</option>
                        <option value='NotStarted'>Not Started</option>
                    </select>
                </td>
            </tr>
            </table>
            </div>
            
            <div id='btnSave' style='display:none'>
                <br/>
                <input type='button' value='Save' onclick='updateFeaturePriorityOrder();' />
                <br/>
            </div>
            
            <div id='saveLoading' style='display:none'>
                <br/>
                <img src='img/loading.gif' />
                <br/>
            </div>
            
            <br/>
        </center>
        <?php
        
        $feature_array = $this->ProjectModel->getFeatureArray($project_id, $selected_feature_status);
        
        ?>
               
        <table style='width:100%;'>
        <tr><td style='width:80px' >
            <table class='mainGrid' style='width:100%' rules='all'>
            <tr><th>Priority</th></tr>
            <?php
            if(is_array($feature_array) && count($feature_array) > 0){
                foreach($feature_array AS $feature_id=>$feature_title){
                    $row_count++;
                    echo "<tr style='height:50px; border-color:#201913; border-style:solid;border-width:4px;' id='reference_{$row_count}'><td style='text-align:center;'>{$row_count}</td></tr>"; 
                }
            }else{
                echo "<tr><td><center><br />None<br /><br /></center></td></tr>";
            }
            ?>
            </table>
        </td>
        <td>
            <table style='width:100%' class='mainGrid' id='featureList' name='featureList' rules='all'>
            <tbody>
            <tr style='cursor: normal;' class='nodrop nodrag'>
            <th style='width:150px;'>Feature Title</th>
            <th>Feature Description</th>
            <th style='width:125px'>Options</th>
            </tr>
        <?php

        //Print the feature information for each feature to the list.
        if(is_array($feature_array) && count($feature_array) > 0){
            foreach($feature_array AS $feature_id=>$feature_title){
                $feature_information = $this->ProjectModel->getFeatureInformation($feature_id);
    
                //Hack to get wordwrapp going.(for firefox)
                $description_array = str_split($feature_information['description'], 20);
                $description_final = '';
                foreach($description_array AS $characters){
                    $description_final .= "{$characters}&#8203;";
                }

                echo "<tr style='height:50px; border-color:#201913; border-style:solid;border-width:4px;' title='Drag the feature up or down to ajust the priority.' id='feature_{$feature_id}' status='{$feature_information['status']}'>";
                echo "<td style='padding:10px;style='width:75px;''>{$feature_title}</td>"; 
                echo "<td style='padding:10px'>{$description_final}</td>";
                echo "<td style='text-align:center'>
                    <input type='button' style='font-size:8pt' onclick=\"loadPage('index.php?Controller=Project&Action=FeatureEdit&project_id={$_GET['project_id']}&feature_id={$feature_id}');\" value='Edit' />
                    <input type='button' style='font-size:8pt' onclick='deleteFeature({$feature_id})' value='Delete' />
                    </td>";
                echo "</tr>";
            }
        }else{
            echo "<tr><td colspan='3'><center><br />There are no features in this Project.<br /><br /></center></td></tr>";
        }

        echo "
            </tbody>
            </table>
        </td></tr></table>";

        ?>
            <script language = 'javascript'>
            var max_row = <?php echo $row_count; ?>;
            var browser = '<?php echo strstr(strtolower($_SERVER['HTTP_USER_AGENT']), 'chrome') ? 'chrome' : 'firefox'; ?>';
            var new_order_string = '';
            
                function deleteFeature(feature_id){
                    if(confirm("Are you sure you want to delete this Feature?")){
                        $.ajax({
                            url: "index.php?Controller=Ajax&Action=deleteFeature&feature_id="+feature_id
                        });
                        $('#feature_'+feature_id).remove();
                        $('#reference_'+max_row).hide();
                        max_row = max_row-1;
                        redoTable();
                    }
                }
           
                window.onbeforeunload = function() {
                    if(new_order_string != ''){
                        return "Are you sure you want to leave the page without saving the order the priority?";
                    }
                }
                
                function updateList(checkbox_element, status)
                {
                    if($(checkbox_element).attr('checked')){
                        $("tr[status='"+status+"']").each(function(){
                            $(this).show();
                            $('#reference_'+max_row).show();
                            max_row++;
                            redoTable();
                        });
                        redoTable();
                    }else{
                        $("tr[status='"+status+"']").each(function(){
                            $(this).hide();
                            $('#reference_'+max_row).hide();
                            max_row--;
                            redoTable();
                        });
                        redoTable();
                    }
                }
                
                function updateFeaturePriorityOrder(){
                    if(new_order_string == ''){
                        return;
                    }
                    
                    $('#saveLoading').show();
                    //$('#featureList').fadeOut();
                    
                    $.ajax({
                        type: 'POST',
                        url:'index.php?Controller=Ajax&Action=updateFeaturePriorityOrder',
                        data: { OrderString: new_order_string, project_id:'<?php echo $_GET['project_id']; ?>', status:'<?php echo $selected_feature_status; ?>'},
                        success: function(){
                            new_order_string = '';
                            $('#saveLoading').hide();
                            $('#btnSave').slideUp();
                            //$('#featureList').fadeIn();
                        }
                    });
                }
                
                $(window).load(function() {
                    redoTable();
                    
                    <?php 
                            echo "$('#selectFeatureStatus').val('{$selected_feature_status}');";
                        ?>
                    
                    $('#featureList').tableDnD({
                        onDrop: function(table, row) {
                            var rows = table.tBodies[0].rows;
                            var order_string = "";
                            for (var i=1; i<rows.length; i++) {
                                order_string += rows[i].id;
                                if(i !=(rows.length-1)){
                                    order_string += ',';
                                }
                            }
                            new_order_string = order_string;
                            $('#btnSave').slideDown();
                            redoTable();
                        }
                    });
                });
                
                function redoTable(){
                    count = 0;
                    $('#featureList tr').each(function(){
                        if(count != 0) {
                            height = $(this).css('height');
                            
                            if(browser == 'chrome'){
                                height = height.replace('px', '');
                                height = parseInt(height) + 8;
                                height = height + 'px';
                            }
                            $('#reference_'+count).css('height', height);
                        }
                        count++;
                    });
                }

            </script> 
        <?php

        $content = ob_get_contents();
        ob_clean();

        $this->view->setTitle("Features for project: {$project_title}");
        $this->view->render($content);
    }
    
    function releaseArchive()
    {
        $row_count =0;
        //Handle GET Param
        if ($_GET['project_id']){
            $project_id = $_GET['project_id'];
            $project_title = $this->ProjectModel->getProjectInformation($project_id);
            $project_title = $project_title['title'];
        }else{
            //Throw an error if no project supplied
            throw new exception('No project supplied. This may be an internal error, please contact support.');
        }
        
        $archived_release_list = $this->ProjectModel->getReleaseArchiveList($project_id);
        
        //print_r($archived_release_list);
        
        ob_start();
        ?>
        
        <button onClick="loadPage('index.php?Controller=Project&Action=BacklogDisplay&project_id=<?php echo $_GET['project_id']; ?>');" class='ui-state-default ui-corner-all' title='Click here go to the project backlog'>Project Backlog</button>
        <hr />
        
        <table style='width:100%' class='mainGrid' id='featureList' name='featureList' rules='all'>
            <tbody>
            <tr>
            <th style='width:200px;'>Release Title</th>
            <th style='width:200px;'>Dates</th>
            <th>Features</th>
            <th style='width:180px'>Options</th>
            </tr>
            <?php
            if(!is_array($archived_release_list)){
                echo "<tr><td style='text-align:center; padding:10px' colspan=4>No archived releases</td></tr>";
            }else{
            
                foreach($archived_release_list AS $release_id=>$release_details){
                    $feature_html = "<ul>";
                    foreach($release_details['features'] AS $feature_title){
                        $feature_html .= "<li>{$feature_title}</li>";
                    }
                    $feature_html .= "</ul>";
                    ?>
                    <tr id='release_<?php echo $release_id; ?>'>
                        <td style='padding-left:10px; font-weight:bold'><?php echo $release_details['title']; ?></td>
                        <td style='text-align:center'><?php echo $release_details['start_date']." <b>to</b> ". $release_details['estimated_completion_date']; ?></td>
                        <td style='padding-top:10px;'><?php echo $feature_html; ?></td>
                        <td style='text-align:center'>
                            <button onClick="deleteRelease(<?php echo $release_id; ?>);" class='ui-state-default ui-corner-all' title='Click here to delete this release' style='font-size:9pt'>Delete</button>
                            <button onClick="unArchiveRelease(<?php echo $release_id; ?>);" class='ui-state-default ui-corner-all' title='Click here to undo the archive on this release.' style='font-size:9pt'>Unarchive</button>
                        </td>
                    </tr>
                <?php
                }
            }
            ?>
            </table>
            
            <script language='javascript'>
            //Delete the project via Ajax, but first confirm.
            function deleteRelease(release_id){
                if(confirm("Are you sure you want to delete this release?")){
                    if(confirm("Are you absolutely sure that you would like to delete this release ALONG WITH ALL THE ATTACHED FEATURES AND TASKS?")){
                        $.ajax({
                            type: 'post',
                            url: "index.php?Controller=Ajax&Action=deleteRelease&release_id="+release_id,
                            data: {
                                delete_features: true
                            }
                        });
                    
                        $('#release_'+release_id).slideUp('slow', function(){
                            $('#release_'+release_id).remove();
                        });
                    } 
                }
            }
            
            function unArchiveRelease(release_id){
                if(confirm("Are you sure you would like to put this release back into your backlog?")){
                        $.ajax({
                           url: "index.php?Controller=Ajax&Action=unArchiveRelease&release_id="+release_id
                        });
                        
                        $('#release_'+release_id).slideUp('slow', function(){
                            $('#release_'+release_id).remove();
                        });
                }
            }
          </script>
        
        <?php

        $content = ob_get_contents();
        ob_clean();

        $this->view->setTitle("Release Archive: {$project_title}");
        $this->view->render($content);
    }
}