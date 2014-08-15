<?php
require_once('Model/BaseModel.php');
class UserModel extends BaseModel
{
    /**
    * Attempt to log a user into the system.
    * If log in fails, the function will return a reason.
    */
    function logUserIn($email, $password)
    {		
        $user = $this->executeQuery("
            SELECT user.*, company.email AS 'company_admin_email', company.super_team_id, count(project.id) AS project_count, project.id AS top_project_id
            FROM user 
            INNER JOIN company ON company.id = user.company_id
            INNER JOIN project ON project.company_id = company.id
            WHERE user.email = '{$email}' 
            AND user.password = '{$password}'
            AND company.status = 'active'
            AND user.active = 1
            GROUP BY user.email, company.email, company.super_team_id
            ");
        $user = $user[0];
        
        if(is_array($user)){
            $success = TRUE;		
            $reason = "";
        } else {
            $success = FALSE;
            $reason = "Username or password incorrect.";
        }

        if ($success) {
            $_SESSION['logged_in'] = '1';
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['company_id'] = $user['company_id'];
            $_SESSION['display_name'] = $user['display_name'];
            $_SESSION['is_admin'] = $user['is_admin'];
            $_SESSION['company_admin_email'] = $user['company_admin_email'];
            $_SESSION['super_team_id'] = $user['super_team_id'];
            $_SESSION['show_message'] = 'Welcome '.$user['display_name'].'.';
            $_SESSION['team_id'] = $user['team_id'];
            $_SESSION['theme_id'] = $user['theme_id'];
            $_SESSION['project_count'] = $user['project_count'];
            $_SESSION['top_project_id'] = $user['top_project_id'];
            
            $user_preferences = $this->executeQuery("
                SELECT * 
                FROM user_preference
                WHERE user_email = '{$email}'
            ");
            if(is_array($user_preferences)){
                foreach($user_preferences AS $preference_details){
                    $_SESSION['preferences'][$preference_details['controller']][$preference_details['action']][$preference_details['key']] = $preference_details['value'];
                }
            }
        }
        
        return array(
            'success' => $success,
            'reason' => $reason
        );		
    }

    /**
     * Log the current user out of the system
     */
    function logUserOut()
    {
        unset($_SESSION);	
        session_destroy();    
        header("location: login.php");
        exit;
    }

    /*
     * Insert a new user into the database
     */
    function addUser($email, $display_name, $is_admin, $password, $company_id, $team_id)
    {
        //Check if the email is already taken.
        $user_exists = $this->getUserDetails($email);
        if(is_array($user_exists)){
            return false;
        }

        return $this->executeUpdateQuery("
            INSERT INTO user
            (email, display_name, is_admin, password, company_id, team_id)
            VALUES
            ('{$email}','{$display_name}','{$is_admin}','{$password}','{$company_id}', $team_id)
            ");

    }
    
    /*
     * Update a user in the database, using $old_email as a reference, incase the email got changed.
     */
    function updateUser($email, $display_name, $is_admin, $password=null, $company_id=null)
    {
        //Only set password if it is not null or blank.
        $password_sql = '';
        if($password && $password != ''){
            $password_sql = ", password = '{$password}' ";
        }
        $this->executeUpdateQuery("
            UPDATE user SET 
            display_name = '{$display_name}',
            is_admin = '{$is_admin}'
            {$password_sql}
            WHERE email = '{$email}'
            ");

        //if the update is for the person logged in, 
        //change the session variables
        if($_SESSION['user_email'] == $email){
            $_SESSION['display_name'] = $display_name;
            $_SESSION['is_admin'] = $is_admin;
        }

        return true;
    }
    
    function setUserTheme($email, $theme_id)
    {
        return $this->executeUpdateQuery("
            UPDATE user 
            SET theme_id = {$theme_id}
            WHERE email = '{$email}';
        ");
    }
    
    function getThemeName($theme_id){
        $theme_name = $this->executeQuery("
            SELECT name 
            FROM theme
            WHERE id = {$theme_id}
        ");
        
        $theme_name = $theme_name[0]['name'];
        return $theme_name;
    }

    /*
     * Add a company
     */
    function addCompany($name, $company_information, $email, $password)
    {
        if($this->getUserDetails($email)){
            return false;
        }
        
        $this->executeUpdateQuery("
            INSERT INTO company
            (name, company_information, email, `key`)
            VALUES
            ('{$name}','{$company_information}', NULL,'0')
            ");
        
        $company_id = $this->executeQuery("
            SELECT id 
            FROM company 
            ORDER BY id DESC 
            LIMIT 1;
        ");
        $company_id = $company_id[0]['id'];
        
        if(!$this->addTeam('Super Team', 'This is the main team that can see all projects. This team cannot be changed or deleted.', NULL, $company_id)){
            return false;
        }
        
        $team_id = $this->executeQuery("
            SELECT id 
            FROM team
            WHERE company_id = {$company_id}
            ORDER BY id DESC 
            LIMIT 1
        ");
        
        $team_id = $team_id[0]['id'];
        
        if(!$this->addUser($email, $name.' Admin', 1, $password, $company_id, $team_id)){
            return false;
        }
        
        if(!$this->executeUpdateQuery("
            UPDATE company 
            SET email = '{$email}',
            super_team_id = '{$team_id}'
            WHERE id = {$company_id}
        ")) return false;
        
        return $company_id;
    }
    
    function deleteCompany($company_id)
    {
        $this->executeUpdateQuery("
            DELETE FROM company 
            WHERE id = {$company_id};
        ");
    }
    
    function updateCompanyStatus($company_id, $key, $status)
    {
        return $this->executeUpdateQuery("
            UPDATE company 
            SET status='{$status}'
            WHERE id = {$company_id}
            AND `key` = '{$key}'
        ");
    }
    
    function updateCompanyKey($company_id, $key)
    {
        return $this->executeUpdateQuery("
            UPDATE company 
            SET `key` = {$key}
            WHERE id = {$company_id}
        ");
    }
    /*
     * Get a company id for a supplied admin email
     */
    function getCompanyByEmail($email)
    {
        $company = $this->executeQuery("
            SELECT id 
            FROM company
            WHERE email = '{$email}'
            ORDER BY id DESC
            ");

        return $company[0]['id'];
    }

    /**
     * Return a list of users for a company
     */
    function getUserList($company_id, $team_id=null)
    {
        $team_sql = '';
        if($team_id ){
            $team_sql = "
            AND team_id = {$team_id}
            ";
        }
        
        $users = $this->executeQuery("
            (
            SELECT email, IF(active = 1, display_name, CONCAT(display_name, ' (Disabled)')) AS display_name, display_name AS display_name_actual, is_admin, team_id, active
            FROM user
            WHERE company_id = '{$company_id}'
            AND email = (SELECT email FROM company WHERE id = {$company_id})
            {$team_sql}
            )
            UNION
            (
            SELECT email, IF(active = 1, display_name, CONCAT(display_name, ' (Disabled)')) AS display_name, display_name AS display_name_actual, is_admin, team_id, active
            FROM user
            WHERE company_id = '{$company_id}'
            AND email != (SELECT email FROM company WHERE id = {$company_id})
            {$team_sql}
            )
            ");

        return $users;
    }
    
    function deleteUser($user_email, $company_id)
    {
        $this->executeUpdateQuery("
            DELETE 
            FROM user 
            WHERE email = '{$user_email}'
            AND company_id = {$company_id}
        ");
    }
    
    function deleteTeam($team_id, $company_id)
    {
        $this->executeUpdateQuery("
            DELETE 
            FROM team 
            WHERE id = {$team_id}
            AND company_id = {$company_id}
        ");
        
        //Set all users of this team to the super_team.
    }
    
    function updateUserTeam($user_email, $team_id, $company_id)
    {
        $this->executeUpdateQuery("
            UPDATE user
            SET team_id = {$team_id}
            WHERE email = '{$user_email}'
            AND company_id = {$company_id}
        ");
    }  
    
    function getTeamList($company_id)
    {
        $teams = $this->executeQuery("
            SELECT *
            FROM team
            WHERE company_id = {$company_id}
        ");
        
        return $teams;
    }
    
    function getTeamDetails($team_id)
    {
        $details = $this->executeQuery("
            SELECT *
            FROM team
            WHERE id = {$team_id}
            ");

        return $details[0];
    }
    
    function getProjectsForTeam($team_id)
    {
        $projects = $this->executeQuery("
            SELECT project.*
            FROM project
            INNER JOIN team_project ON project.id = team_project.project_id
            WHERE team_project.team_id = {$team_id}
        ");
        
        return $projects;
    }
    
    function addTeam($name, $description, $projects, $company_id)
    {
        $this->executeUpdateQuery("
            INSERT INTO team (name, description, company_id)
            VALUES ('{$name}', '{$description}', '{$company_id}');
        ");
        
        $team_id = $this->executeQuery("
            SELECT id 
            FROM team
            WHERE company_id = {$company_id}
            ORDER BY id DESC 
            LIMIT 1
        ");
        
        $team_id = $team_id[0]['id'];
    
        if(is_array($projects)){
            foreach($projects AS $project_id)
            {
                $this->executeUpdateQuery("
                    INSERT INTO team_project (team_id, project_id)
                    VALUES ({$team_id}, {$project_id});
                ");
            }
        }
        
        return true;
    }
    
    function updateTeam($team_id, $name, $description, $projects, $company_id)
    {
        $success = $this->executeUpdateQuery("
            UPDATE team SET
            name = '{$name}',
            description = '{$description}'
            WHERE id = {$team_id}
            AND company_id = {$company_id}
        ");
        
        if(!$success){
            return false;
        }
        
        $this->executeUpdateQuery("
            DELETE 
            FROM team_project 
            WHERE team_id = {$team_id};
        ");
        
        if(is_array($projects)){
            foreach($projects AS $project_id)
            {
                $this->executeUpdateQuery("
                    INSERT INTO team_project (team_id, project_id)
                    VALUES ({$team_id}, {$project_id});
                ");
            }
        }
        
        return true;
    }
    
    function getThemeList()
    {
        $details =$this->executeQuery("
            SELECT * FROM theme;
        ");
        
        return $details;
    }

    function getUserDetails($user_email)
    {
        $details = $this->executeQuery("
            SELECT *
            FROM user
            WHERE email = '{$user_email}'
            ");

        return $details[0];
    }
    
    function getUserCompanyId($user_email)
    {
        $company_id = $this->executeQuery("
            SELECT company_id 
            FROM user 
            WHERE email = '{$user_email}'
        ");
        
        return $company_id[0]['company_id'];
    }

    /**
     * Check that a user has permission to view a page
     */
    function userHasPermission($user_email, $controller, $action, $get_params=null, $company_id, $team_id)
    {
        //echo "email{$user_email}";
        //echo "controller{$controller}";
        //echo "action{$action}";

        $UserModel = new UserModel();
        $user = $UserModel->getUserDetails($user_email);
        
        if($user_email == $_SESSION['company_admin_email']){
            $team_id = 'SUPER';
        }

        if(is_array($get_params)){
            foreach($get_params AS $key=>$value){
                if(strtoupper($value) == 'NULL' || $value == '' || !$value) continue;
                switch($key){
                    case "project_id":
                        $check['project_id'] = $value;
                        break;
                    case "feature_id":
                        $check['feature_id'] = $value;
                        break;
                    case "release_id":
                        $check['release_id'] = $value;
                        break;
                    case "task_id":
                        $check['task_id'] = $value;
                        break;
                    case "user_email":
                        $check['user_email'] = $value;
                        break;
                    case "impediment_id":
                        $check['impediment_id'] = $value;
                        break;
                }
            }
        }
        
        if(is_array($check)){
            $UserModel = new UserModel();
            $success = $UserModel->checkURLHack($check, $company_id, $team_id);
            if(!$success){
                return false;
            }
        }

        //Admin as all permissions
        if($user['is_admin']){
            return true;
        }

        //Non admins can only do some things
        if($controller == 'User' && $action == 'UserEdit'){
            if(is_array($get_params)){
                if($get_params['user_email'] == $user_email){
                    return true;
                }
            }
        }
        
        if($controller == 'Project' && ($action == 'Display' || $action == 'TaskEdit' || $action == 'TaskboardDisplay' || $action == 'ImpedimentAdd')){
            return true;
        }
        
        if($controller == 'Support'){
            return true;
        }  
        if($controller == 'Ajax'){
            return true;
        }
    }
    
    function disableUser($user_email)
    {
        if($user_email == $_SESSION['company_admin_email']){
            return FALSE;
        }
    
        $success = $this->executeUpdateQuery("
            UPDATE user SET
            active = 0
            WHERE email = '{$user_email}'
        ");
        
        return $success;
    }
    
    function enableUser($user_email)
    {
        $success = $this->executeUpdateQuery("
            UPDATE user SET
            active = 1
            WHERE email = '{$user_email}'
        ");
        
        return $success;
    }
    
    function checkURLHack($check_array, $company_id, $team_id)
    {
        $BaseModel = new BaseModel();
        foreach($check_array AS $key=>$check_value){
            if($team_id == 'SUPER'){
                switch($key){
                    case "project_id":
                        $sql = "SELECT count(project.id) AS count FROM project INNER JOIN company ON company.id = project.company_id WHERE project.id = {$check_value} AND company.id = {$company_id}";
                        break;
                    case "feature_id":
                        $sql = "SELECT count(feature.id) AS count FROM feature INNER JOIN project ON project.id = feature.project_id INNER JOIN company ON company.id = project.company_id WHERE feature.id = {$check_value} AND company.id = {$company_id}";
                        break;
                    case "release_id":
                        $sql = "SELECT count(`release`.id) AS count FROM `release` INNER JOIN project ON project.id = `release`.project_id INNER JOIN company ON company.id = project.company_id WHERE `release`.id = {$check_value} AND company.id = {$company_id}";
                        break;
                    case "task_id":
                        $sql = "SELECT count(task.id) AS count FROM task INNER JOIN feature ON feature.id = task.feature_id INNER JOIN project ON project.id = feature.project_id INNER JOIN company ON company.id = project.company_id WHERE task.id = {$check_value} AND company.id = {$company_id} ";
                        break;
                    case "user_email":
                        $sql = "SELECT count(user.email) AS count FROM user INNER JOIN company ON company.id = user.company_id WHERE user.email = '{$check_value}' AND company.id = {$company_id} ";
                        break;
                    case "impediment_id":
                        $sql = "SELECT count(impediment.id) AS count FROM impediment INNER JOIN user ON user.email = impediment.user_email INNER JOIN company ON company.id = user.company_id WHERE impediment.id = {$check_value} AND company.id = {$company_id} ";
                        break;
                }
            }else{
                switch($key){
                    case "project_id":
                        $sql = "SELECT count(project.id) AS count FROM project INNER JOIN company ON company.id = project.company_id INNER JOIN team ON team.company_id = company.id WHERE project.id = {$check_value} AND company.id = {$company_id} AND team.id = {$team_id}";
                        break;
                    case "feature_id":
                        $sql = "SELECT count(feature.id) AS count FROM feature INNER JOIN project ON project.id = feature.project_id INNER JOIN company ON company.id = project.company_id INNER JOIN team ON team.company_id = company.id WHERE feature.id = {$check_value} AND company.id = {$company_id} AND team.id = {$team_id}";
                        break;
                    case "release_id":
                        $sql = "SELECT count(`release`.id) AS count FROM `release` INNER JOIN project ON project.id = `release`.project_id INNER JOIN company ON company.id = project.company_id INNER JOIN team ON team.company_id = company.id WHERE `release`.id = {$check_value} AND company.id = {$company_id} AND team.id = {$team_id}";
                        break;
                    case "task_id":
                        $sql = "SELECT count(task.id) AS count FROM task INNER JOIN feature ON feature.id = task.feature_id INNER JOIN project ON project.id = feature.project_id INNER JOIN company ON company.id = project.company_id INNER JOIN team ON team.company_id = company.id WHERE task.id = {$check_value} AND company.id = {$company_id} AND team.id = {$team_id}";
                        break;
                    case "user_email":
                        $sql = "SELECT count(user.email) AS count FROM user INNER JOIN company ON company.id = user.company_id INNER JOIN team ON team.company_id = company.id WHERE user.email = '{$check_value}' AND company.id = {$company_id} AND team.id = {$team_id}";
                        break;
                    case "impediment_id":
                        $sql = "SELECT count(impediment.id) AS count FROM impediment INNER JOIN user ON user.email = impediment.user_email INNER JOIN company ON company.id = user.company_id INNER JOIN team ON team.company_id = company.id WHERE impediment.id = {$check_value} AND company.id = {$company_id} AND team.id = {$team_id}";
                        break;
                }
            }
                
            //echo $sql; exit;
            $success = $BaseModel->executeQuery($sql);
            if($success[0]['count'] == 0){
                return false;
            }
        }
        return true;
    }

}
?>
