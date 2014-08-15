<?php
require_once("Controller/BaseController.php");
require_once("Model/UserModel.php");

class UserController extends BaseController
{
    protected $UserModel;

    function __construct(){
        parent::__construct();

        $this->UserModel = new UserModel();
    }

    function userList()
    {
        $company_id = $_SESSION['company_id'];

        if($_SESSION['company_admin_email'] == $_SESSION['user_email']){
            $user_array = $this->UserModel->getUserList($company_id);
        }else{
            $user_array = $this->UserModel->getUserList($company_id, $_SESSION['team_id']);
        }
        
        if(!is_array($user_array)){
            throw new Exception("Something went wrong with pulling the users for your team. Please contact Anvil Support.");
        }
        $teams = $this->UserModel->getTeamList($company_id);

        ob_start();		

        ?>
            <button onClick="loadPage('index.php?Controller=User&Action=TeamList');" class='ui-state-default ui-corner-all' title='Click here to manage the teams in your company'>Team Management</button>
            <button onClick="loadPage('index.php?Controller=User&Action=UserEdit');" class='ui-state-default ui-corner-all' title='Click here to add a user to your company'>+ Add User</button>
            <hr />
            <table class='mainGrid' name='UserList' rules='all' style='border-style:solid; width:98%; margin-right:auto; margin-left:auto'>
            <tr>
            <th class='left'>Email</th>
            <th>Display Name</th>
            <th width='175px'>Role</th>
            <th width='170px'>Team</th>
            <th width='150px' class='right'>Options</th>
            </tr>
        <?php

        //Print the user information for each user to the list.
        foreach($user_array AS $id=>$user_details){
            echo "<tr id=user_{$id}>";
            echo "<td>{$user_details['email']}</td>";
            echo "<td>{$user_details['display_name_actual']}</td>";
            
            
            if($user_details['email'] == $_SESSION['company_admin_email']){
                echo "<td>Company Admin</td>";
                
                echo "<td style='text-align:center'><select style='width:150px' class='ui-widget-header' DISABLED><option>Super team</option></select></td>";
            }else{
                if($user_details['is_admin']){
                    echo "<td>Team Admin</td>";
                }else{
                    echo "<td>Team Member</td>";
                }
                
                //Only company admin can do this.
                $disabled = '';
                if($_SESSION['company_admin_email'] != $_SESSION['user_email']){
                    $disabled = ' DISABLED ';
                }
            
                echo "<td style='text-align:center'><select onChange=\"updateTeam('{$user_details['email']}', this.value);\" style='width:150px' class='ui-widget-header' $disabled>
                ";
                echo "<option value='NULL'>No Team</option>";
                foreach($teams AS $team_details){
                    if($team_details['id'] == $user_details['team_id']){
                        echo "<option value='{$team_details['id']}' selected='yes' title=\"{$team_details['description']}\">{$team_details['name']}</option>";
                    }else{
                        echo "<option value='{$team_details['id']}' title=\"{$team_details['description']}\">{$team_details['name']}</option>";
                    }
                }
                echo "</select></td>";
            }
            
            echo "<td style='text-align:center; padding:2px'><table style='border:none'><tr>";
            
            
            if($user_details['active']){
                $disable_button_value = 'Disable';
                $enable_hidden_html = '; display:none';
                $disable_hidden_html = '';
            }else{
                $disable_button_value = 'Enable';
                $enable_hidden_html = '';
                $disable_hidden_html = '; display:none';
            }
            
            if($user_details['email'] != $_SESSION['company_admin_email'] || $_SESSION['company_admin_email'] == $_SESSION['user_email']){ 
                echo "<td style='border:none'><button onClick=\"loadPage('index.php?Controller=User&Action=UserEdit&user_email={$user_details['email']}');\" class='ui-state-default ui-corner-all' title='Click here to edit this user' style='font-size:8pt'>Edit</button></td>
                 ";     
            }else{
                echo "<td style='border:none'><button class='ui-state-default ui-corner-all' style='font-size:8pt' DISABLED>Edit</button></td>";
            }
            
            if($user_details['email'] != $_SESSION['company_admin_email'] && $_SESSION['user_email'] != $user_details['email']){
                echo "
                    <td style='border:none'>
                        <button id='{$id}_enable' onClick=\"disableEnableUser('{$user_details['email']}', 'Enable', {$id});\" class='ui-state-default ui-corner-all' style='font-size:8pt$enable_hidden_html'>Enable</button>
                        <button id='{$id}_disable' onClick=\"disableEnableUser('{$user_details['email']}', 'Disable', {$id});\" class='ui-state-default ui-corner-all' style='font-size:8pt$disable_hidden_html'>Disable</button>
                    </td>";
            }else{
                echo "<td style='border:none'><button class='ui-state-default ui-corner-all' style='font-size:8pt' DISABLED>$disable_button_value</button></td>";
            }

            if($_SESSION['company_admin_email'] == $_SESSION['user_email']){
                if($user_details['email'] != $_SESSION['company_admin_email']){
                    echo "<td style='border:none'><button onClick=\"deleteUser('{$user_details['email']}', {$id});\" class='ui-state-default ui-corner-all' title='Click here to delete this user' style='font-size:8pt'>Delete</button></td>";
                }else{
                    echo "<td style='border:none'><button class='ui-state-default ui-corner-all' style='font-size:8pt' DISABLED>Delete</button></td>";
                }
            }else{
                echo "<td style='border:none'><button class='ui-state-default ui-corner-all' style='font-size:8pt' DISABLED>Delete</button></td>";
            }
            echo "
                 </tr></table></td>";
            echo "</tr>";
        }
        ?>
        </table>
        <script language='javascript'>
            function updateTeam(user_email, team_id){
                $.ajax({
                    url: "index.php?Controller=Ajax&Action=UpdateUserTeam&email="+user_email+"&team_id="+team_id
                });
            }
            
            function deleteUser(user_email, element_index){
                if(confirm("Are you sure you want to delete this user?")){
                    $.ajax({
                        url: "index.php?Controller=Ajax&Action=DeleteUser&email="+user_email
                    });
                    $('#user_'+element_index).slideUp();
                }
            }
            
            function disableEnableUser(user_email, action, button_reference){
                if(action == 'Disable'){
                    if(confirm("Are you sure you want to disable this user? This user will not be able to login from now on.")){
                        $.ajax({
                            url: "index.php?Controller=Ajax&Action=DisableUser&email="+user_email
                        });
                        $('#'+button_reference+'_enable').show();
                        $('#'+button_reference+'_disable').hide();
                    }
                }else{
                    if(confirm("Are you sure you want to re-enable this user?")){
                        $.ajax({
                            url: "index.php?Controller=Ajax&Action=EnableUser&email="+user_email
                        });
                        $('#'+button_reference+'_disable').show();
                        $('#'+button_reference+'_enable').hide();
                    }
                }
            }
       </script>
        <?php

        $content = ob_get_contents();
        ob_clean();

        $this->view->setTitle("List of users");
        $this->view->render($content);

    }

    function userEdit()
    {
        $errors = null;
        //Handle Post and save
        if(isset($_POST['save'])){
            //echo "<pre>"; print_r($_POST); exit;
                
            $preference = $_POST['preference'];
            unset($_POST['preference']);
            $_POST = $this->makePostSafe($_POST);
            
            //Validate some of the fields, make sure that they are ok.
            if($_POST['password'] && !$this->validate('password', $_POST['password'])){
                //throw new exception ('Password is incorrect length. Needs to be at least 6 characters long, and at most 20 characters long.');
                $errors[] = 'Password is incorrect length. Needs to be at least 6 characters long, and at most 20 characters long.';
            }
            if($_POST['password'] != $_POST['confirm_password'] ){
                //throw new exception ('Passwords do not match.');
                $errors[] = 'Passwords do not match.';
            }
            
            if($_POST['is_new'] && !$this->validate('email', $_POST['email'])){
                //throw new exception ('Email address was in an invalid format. Please re-enter information.');
                $errors[] = "Email address ({$_POST['email']}) was in an invalid format. Please re-enter information.";
            }else if(!$_POST['is_new'] && !$this->validate('email', $_POST['email_hidden'])){
                throw new exception ('An error occurred, please try again.');
                //$errors[] = "Email address ({$_POST['email_hidden']}) was in an invalid format. Please re-enter information.";
            }
            
            //print_r($errors);exit;
            if(!is_array($errors)){
                if(!isset($_POST['is_admin'])){
                    $_POST['is_admin'] = 0;
                }
                
                //If hidden element is_new is set, add the user, else update the user.
                if($_POST['is_new']){
                    $team_id = $this->UserModel->getUserDetails($_SESSION['user_email']);
                    $team_id = $team_id['team_id'];
                    $success = $this->UserModel->addUser($_POST['email'], $_POST['display_name'], $_POST['is_admin'], $_POST['password'], $_SESSION['company_id'], $team_id);
                }else{
                    //safty check. NO NMAP HACKING WILL BE DONE ON THIS DAY.
                    $user_company_id = $this->UserModel->getUserCompanyId($_POST['email_hidden']);
                    if($_SESSION['company_id'] == $user_company_id){
                        
                        if($_POST['email_hidden'] == $_SESSION['company_admin_email']){
                            $_POST['is_admin'] = 1;
                        }
                    
                        if($_SESSION['user_email'] == $_POST['email_hidden']){
                            $success = $this->UserModel->updateUser($_POST['email_hidden'], $_POST['display_name'], $_POST['is_admin'], $_POST['password'], $_SESSION['company_id']);
                            $success = $this->UserModel->setUserTheme($_POST['email_hidden'], $_POST['theme_id']);
                            
                            foreach($preference AS $key=>$value){
                                $this->UserModel->saveUserPreference('All', 'All', $key, $value, $_SESSION['user_email']);
                            }
                        }else if($_SESSION['is_admin']){
                            $success = $this->UserModel->updateUser($_POST['email_hidden'], $_POST['display_name'], $_POST['is_admin'], NULL, $_SESSION['company_id']);
                        }
                    }
                    
                }

                if(!$success){
                    throw new exception('Insert/Update failed on the user. This is most likely because that email already exists in our database. Please check that you have entered the correct information and resave.');
                }
                
                //Update theme
                if($_POST['email_hidden'] == $_SESSION['user_email']){
                    $_SESSION['theme_id'] = $_POST['theme_id'];
                }
            
                $_SESSION['show_message'] = 'User details saved successfully.';
                header('location:'.$_SESSION['previous_url']);
                exit;
            }
        }
        //If an email is supplied, we are in edit mode, else we are in add mode.
        if($_GET['user_email']){
            $user_details = $this->UserModel->getUserDetails($_GET['user_email']);

            if(!is_array($user_details)){
                throw new exception('That user does not exist. Please contact support.');
            }
        }else{
            //new user
        }

        ob_start();

        if($_SESSION['is_admin']){
            echo "<button onClick=\"loadPage('index.php?Controller=User&Action=UserList');\" class='ui-state-default ui-corner-all' title='Click here to list the users in your company'>List Users</button>";
        }else{
            echo "<button onClick=\"loadPage('index.php?Controller=Project&Action=TaskboardDisplay');\" class='ui-state-default ui-corner-all' title='Click here go to the taskboard'>Project Taskboard</button>";
        }
        ?>
        <hr/>
        
        <form method='POST' autocomplete="off">

        <table class='mainEditGrid' name='UserList' style='width:500px; margin-right:auto; margin-left:auto'>
        <tr></tr><th colspan=2 height=25px>User details</th></tr>
        <tr><td> &nbsp </td></tr>
        <tr><td><table class='mainEditGrid' style='width:90%;'>
        <tr>
        <td>Email</td>
        <td><input type='text' style='width:300px' id='email' name='email' value='<?php echo !empty($_POST['email']) ? $_POST['email'] : ''; ?>' onBlur='checkValid("email", this, "User email")' />
        <input type='hidden' style='width:300px' id='email_hidden' value='<?php echo !empty($_POST['email_hidden']) ? $_POST['email_hidden'] : ''; ?>' name='email_hidden' /></td>
        </tr> 
        <tr>
        <td>Display Name</td>
        <td><input type='text' style='width:300px' id='display_name' value='<?php echo !empty($_POST['display_name']) ? $_POST['display_name'] : ''; ?>' name='display_name' onBlur='checkValid("minimum_text", this, "Display name")' /></td>
        </tr>
        <tr>
        <?php

        if($_SESSION['is_admin']){

            ?>
            <td>Administrator</td>
        
            <?php
            $disabled = '';
            if(is_array($user_details) && $user_details['email'] == $_SESSION['company_admin_email']){
                $disabled = "DISABLED title='This is disabled because this is the company admin.'";
            }
            ?>
            <td><select style='width:300px' id='is_admin' value='<?php echo $_POST['is_admin']; ?>' name='is_admin' <?php echo $disabled; ?>>
                <option value='0'>No</option>
                <option value='1'>Yes</option>
            </select></td>
            </tr>
            
            <?php
        }

        //If a new user is being added OR
        //If you are editing your own user account,
        //Then Show password.
        if(!is_array($user_details) || $user_details['email'] == $_SESSION['user_email']){
            ?>
            <tr>
            <td>Password</td>
            <td><input type='password' style='width:300px' id='password' name='password' onBlur='checkValid("password", this, "Password")'/></td>
            </tr>
            <tr>
            <td>Confirm Password</td>
            <td><input type='password' style='width:300px' id='confirm_password' name='confirm_password' onBlur='checkValid("password", this, "Confirm password")' /></td>
            </tr>
            
            <?php
            if($user_details['email'] == $_SESSION['user_email']){
                $themes = $this->UserModel->getThemeList();
                ?>
                <tr height=10px></tr>
                <tr>
                    <td>Theme</td>
                    <td>
                    <select id='theme_id' name='theme_id' style='width:300px;\' class='ui-widget-header'>
                        <?php
                        foreach($themes AS $theme_details){
                            if($theme_details['name'] == 'brown'){
                                $theme_details['name'] = 'brown (default)';
                            }
                            echo "<option value='{$theme_details['id']}'>{$theme_details['name']}</option>";
                        }
                        ?>
                    </select>
                    </td>
                </tr>
                <tr height=10px></tr>
                <tr>
                    <td colspan=2>
                        <table class='mainEditGrid' style='border:1px solid #ABA080; width:100%;' cellpadding=5>
                            <tr>
                                <th colspan=2 style='padding:5px'>Preferences</th>
                            </tr>
                            <tr>
                                <td>
                                    Check for taskboard changes:
                                </td>
                                <td>
                                    <select name="preference[automatic_reload]" class='ui-widget-header'>
                                        <option value='' selected='selected'>Fully Automatic</option>
                                        <option value='semi-auto'>Semi Automatic (request to reload)</option>
                                        <option value='disabled'>Manual</option>
                                    </select>
                                </td>
                            </tr>
                            <tr title = 'Check this to disable the server connection warnings that pop up when an Ajax request fails.'>
                                <td>
                                    Disable connection warnings
                                </td>
                                <td>
                                   <input type='hidden' name="preference[disable_ajax_warnings]" value='' /><input type='checkbox' name="preference[disable_ajax_warnings]" value='Yes' />
                                </td>
                            </tr>
                            <tr title = 'Check this to disable the hours management box from popping up whenever a task is moved into the done column.'>
                                <td>
                                    Disable task hours management automatic popup
                                </td>
                                <td>
                                   <input type='hidden' name="preference[disable_hours_popup]" value='' /><input type='checkbox' name="preference[disable_hours_popup]" value='Yes' />
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <?php
            }
        }
        
        if(!is_array($user_details)){
            //set a hidden element to know that it is new in the POST
            ?>

            <input type='hidden' value='1' id='is_new' name='is_new' /> 

            <?php
        }
            ?>

            <tr><td colspan='2'><br/><center>
            <input type='submit' style='width:80px' class='ui-state-default ui-corner-all' name='save' id='save' value='Save' title='Click here to check and save the user.'>
            </center><br/></td></tr>

            </form>
            </tr></td></table></table>
        <script language='javascript'>
        var errors = new Array();
       <?php
        
        if(is_array($errors)){
            foreach($errors AS $key=>$error_message){
                echo "errors['{$key}'] = '{$error_message}';";
            }
        }

        //If edit mode, put the details on the page
        if(is_array($user_details)){
            //Little hack to make the confirm password the same as the password on load.
            $user_details['confirm_password'] = $user_details['password'];
            //Set the hidden email element for reference
            $user_details['email_hidden'] = $user_details['email'];
            //Go through each of the details retrieved from the database and set the elements to the values for edit
            foreach($user_details AS $detail=>$value){
                $value = mysql_escape_string($value);
                echo "$('#{$detail}').val('{$value}'); ";
            }
            
            if(isset($_SESSION['preferences']['All']['All']) && is_array($_SESSION['preferences']['All']['All'])){
                foreach($_SESSION['preferences']['All']['All'] AS $key=>$value){
                    if($value == 'Yes'){
                        echo "$('[name=\"preference[{$key}]\"]').attr('checked', 'checked'); ";
                    }else{
                        echo "$('[name=\"preference[{$key}]\"]').val('$value'); ";
                    }
                }
            }
            //Not able to update email, because its a primary key.
            echo " $('#email').attr('disabled','disabled'); ";
        }
        ?>        
        $(document).ready(function(){
            for(var i in errors){
                $('#freeow').freeow('Error',errors[i],{
                    classes: ["gray", "error"],
                    autoHide: 0
                });
            }
        });
        
        </script>

        <?php
        
        $content = ob_get_contents();
        ob_clean();

        if(is_array($user_details)){
            $this->view->setTitle("Edit user: {$user_details['email']}");
        }else{
            $this->view->setTitle("Insert a new user");
        }
        $this->view->render($content);
        
    }

    function teamList()
    {
        $company_id = $_SESSION['company_id'];

        $team_array = $this->UserModel->getTeamList($company_id);

        ob_start();		

        ?>
        <button onClick="loadPage('index.php?Controller=User&Action=UserList');" class='ui-state-default ui-corner-all' title='Click here to list the users in your company'>List Users</button>
        <?php
        if($_SESSION['company_admin_email'] == $_SESSION['user_email']){
            echo "<button onClick=\"loadPage('index.php?Controller=User&Action=TeamEdit');\" class='ui-state-default ui-corner-all' title='Click here to add a team to your company'>+ Add Team</button>";
        }
        ?>
            <hr />
            <table class='mainGrid' name='TeamList' rules='all' style='border-style:solid; width:98%; margin-right:auto; margin-left:auto'>
            <tr>
            <th width='175px'>Name</th>
            <th>Description</th>
            <th width='200px'>Projects</th>
            <th width='130px'>Options</th>
            </tr>
        <?php

        //Print the team information for each team to the list.
        if(is_array($team_array)){
            foreach($team_array AS $team_details){
                echo "<tr id=\"team_{$team_details['id']}\">";
                echo "<td>{$team_details['name']}</td>"; 
                echo "<td>{$team_details['description']}</td>";
            
                $projects = $this->UserModel->getProjectsForTeam($team_details['id']);

                echo "<td><table width=100%>";
                if(is_array($projects)){

                    foreach($projects AS $project_details){
                        echo "<tr><td style='cursor:pointer' class='big_title' title='
                        <h2>{$project_details['title']}</h2>
                        {$project_details['description']}'>";
                        echo $project_details['title'];
                        echo "</td></tr>";
                    }

                }else{
                    echo "None";
                }
                echo "</table></td>";
                
                echo "<td style='text-align:center; padding:2px'>";
                if($_SESSION['super_team_id'] == $team_details['id']){
                    echo "
                        <button class='ui-state-default ui-corner-all' style='font-size:8pt' title='Cannot edit the super team' DISABLED>Edit</button>
                        <button class='ui-state-default ui-corner-all' style='font-size:8pt' title='Cannot delete the super team' DISABLED>Delete</button>
                    ";
                }else{
                    if($_SESSION['company_admin_email'] == $_SESSION['user_email']){
                        echo "
                            <button onClick=\"loadPage('index.php?Controller=User&Action=TeamEdit&team_id={$team_details['id']}');\" class='ui-state-default ui-corner-all' title='Click here to edit this team' style='font-size:8pt'>Edit</button>
                        ";
                        echo "
                            <button onClick=\"deleteTeam({$team_details['id']});\" class='ui-state-default ui-corner-all' title='Click here to delete this team' style='font-size:8pt'>Delete</button>
                            ";
                    }else{
                        if($_SESSION['team_id'] == $team_details['id']){
                            echo "
                                <button onClick=\"loadPage('index.php?Controller=User&Action=TeamEdit&team_id={$team_details['id']}');\" class='ui-state-default ui-corner-all' title='Click here to edit this team' style='font-size:8pt'>Edit</button>
                            ";
                        }else{
                            echo "
                                <button class='ui-state-default ui-corner-all' style='font-size:8pt' title='Cannot edit the super team' DISABLED>Edit</button>
                            ";
                        }
                        echo "
                            <button class='ui-state-default ui-corner-all' style='font-size:8pt' title='Cannot delete the super team' DISABLED>Delete</button>
                            ";
                    }
                }
                echo "</td>";
                echo "</tr>";
            }
        }else{
            echo "
            <tr height=50px>
                <td colspan=4 style='text-align:center;'>
                    There are no teams in this company.
                </td>
            </tr>";
        }
        
        ?>
        </table>
        <script language='javascript'>
            function deleteTeam(team_id){
                if(confirm("Are you sure you want to delete this team? All users assigned to this team will no longer be able to access any projects until you re-assign their teams.")){
                    $.ajax({
                        url: "index.php?Controller=Ajax&Action=DeleteTeam&team_id="+team_id
                    });
                    $('#team_'+team_id).slideUp();
                }
            }
       </script>
        <?php

        $content = ob_get_contents();
        ob_clean();

        $this->view->setTitle("List of teams");
        $this->view->render($content);

    }

    function teamEdit()
    {
        //Safty check
        if(
            $_SESSION['super_team_id'] == $_GET['team_id'] || 
            ($_SESSION['company_admin_email'] != $_SESSION['user_email']) && $_GET['team_id'] != $_SESSION['team_id'])
        {
            header('location:'.$_SESSION['previous_url']);
            exit;
        }
        
        $errors = null;
        //Handle Post and save
        if(isset($_POST['save'])){
            //echo "<pre>"; print_r($_POST); exit;
                 
            $_POST = $this->makePostSafe($_POST);
            
            if(!is_array($_POST)){
                throw new exception("Something went wrong with saving this record. Please contact Anvil support with the following code: UC501.");
            }
            
            foreach($_POST AS $element_name=>$element_value){
                if(substr($element_name, 0, 8) == 'project_'){
                    $project_array[] = substr($element_name, 8);
                }
            }
            //echo "<pre>";print_r($project_array);exit;
            
            if(!$this->validate('name', $_POST['minimum_text'])){
                $errors[] = "Team name does not have enough characters.";
            }
            
            //print_r($errors);exit;
            if(!is_array($errors)){
                if($_GET['team_id']){
                    $success = $this->UserModel->updateTeam($_GET['team_id'], $_POST['name'], $_POST['description'], $project_array, $_SESSION['company_id']);
                }else{
                    $success = $this->UserModel->addTeam($_POST['name'], $_POST['description'], $project_array, $_SESSION['company_id']);
                }
                
                if(!$success){
                    throw new exception('Insert/Update failed on the team. This is most likely because something went wrong with our database. Please check that you have entered the correct information and resave.');
                }

                $_SESSION['show_message'] = 'Details saved successfully.';
                header('location:'.$_SESSION['previous_url']);
                exit;
            }
        }
        //If an email is supplied, we are in edit mode, else we are in add mode.
        if($_GET['team_id']){
            $team_details = $this->UserModel->getTeamDetails($_GET['team_id']);

            if(!is_array($team_details)){
                throw new exception('That team does not exist. Please contact support.');
            }
        }else{
            //new team
        }
        
        $team_projects = $this->UserModel->getProjectsForTeam($team_details['id']);
        
        require_once("Model/ProjectModel.php");
        $ProjectModel = new ProjectModel();
        $projects = $ProjectModel->getProjectArray($_SESSION['company_id']);
        
        ob_start();

        ?>
        <button onClick="loadPage('index.php?Controller=User&Action=TeamList');" class='ui-state-default ui-corner-all' title='Click here to list the users in your company'>List Teams</button>
        <hr/>
        
        <form method='POST' autocomplete="off">

        <table class='mainEditGrid' name='TeamList' style='width:500px; margin-right:auto; margin-left:auto'>
        <tr></tr><th colspan=2 height=25px>Team details</th></tr>
        <tr><td> &nbsp </td></tr>
        <tr><td><table class='mainEditGrid' style='width:90%;'>
        <tr>
        <td>Name</td>
        <td><input type='text' style='width:300px' id='name' name='name' value='<?php echo $_POST['name']; ?>' onBlur='checkValid("minimum_text", this, "Team name")' /></td>
        </tr> 
        <tr>
        <td>Description</td>
        <td><textarea rows=4 style='width:300px;' id='description' name='description'></textarea></td>
        </tr>
        <tr height=10px></tr>
        <tr>
        <td>Projects</td>
        <td style='border: 1px solid #9C947C; width:280px; padding:5px'>
        <!--
        <input style='font-size:8pt' type='button' value='All' onclick='selectAllProjects();'/>
        <input style='font-size:8pt' type='button' value='None' onclick='selectNoneProjects();'/><br/>
        -->
        <?php
        if(is_array($projects) && count($projects) > 0){
            $checked = ' CHECKED ';
            if($_GET['team_id']){
                $checked = '';
            }
            foreach($projects AS $project_id=>$project_title){
                echo "<input type='checkbox' value='{$project_title}' name='project_{$project_id}' id='project_{$project_id}' {$checked} />{$project_title}<br/>";
            }
        }else{
            echo "<i>There are no projects for your company.</i>";
        }
        ?>
        </td>
        </tr>
            
        <tr><td colspan='2'><br/><center>
        <input type='submit' style='width:80px;' class='ui-state-default ui-corner-all' name='save' id='save' value='Save' title='Click here to check and save the user.'>
        </center><br/></td></tr>

        </form>
        </td>
        </tr>
        </table>
        </table>
        <script language='javascript'>
        var errors = new Array();
       <?php
        
        if(is_array($errors)){
            foreach($errors AS $key=>$error_message){
                echo "errors['{$key}'] = '{$error_message}';";
            }
        }

        //If edit mode, put the details on the page
        if(is_array($team_details)){
            //Go through each of the details retrieved from the database and set the elements to the values for edit
            foreach($team_details AS $detail=>$value){
                if($detail == 'description' && ($value == '' || !$value || is_null($value))){
                    $value = 'None';
                }
                $value = mysql_escape_string($value);
                echo "$('#{$detail}').val('{$value}'); ";
            }
            
            if(is_array($team_projects)){
                foreach($team_projects AS $project_details){
                    echo "$('#project_{$project_details['id']}').attr('checked', true); ";
                }
            }
            
            $this->view->setTitle("Edit team: {$team_details['name']}");
        }else{
            $this->view->setTitle("Insert a new team");
        }
        ?>        
        $(document).ready(function(){
            for(var i in errors){
                $('#freeow').freeow('Error',errors[i],{
                    classes: ["gray", "error"],
                    autoHide: 0
                });
            }
        });
        </script>

        <?php
        
        $content = ob_get_contents();
        ob_clean();

        $this->view->render($content);
        
    }


}
