<?php
ini_set('error_reporting', 0);

require_once("Model/BaseModel.php");
require_once("Controller/BaseController.php");
require_once("Model/UserModel.php");

// start a session
session_start();

// gzip output buffering for smaller bandwidth
ob_start('ob_gzhandler');

// logout functionality
if(isset($_GET['logout'])
&& $_GET['logout'] == 1) {	
    $_SESSION['logged_in'] = 0;
}

// check if we are logged in
if(!isset($_SESSION['logged_in'])
|| $_SESSION['logged_in'] != '1') {
    UserModel::logUserOut();
    exit; //May never be reached, but it is put in here for safety
}

//This try will catch any exception thrown on any page.
try {
    //Sort out the Previous URL that is not the same as the current one. 
    //Used for going back to a previous page after a save etc.
    if(!strstr($_SERVER['REQUEST_URI'], 'Ajax')){
        if($_SESSION['temp_url'] != $_SESSION['url']){
            $_SESSION['previous_url'] = $_SESSION['temp_url'];
        }
        $_SESSION['temp_url'] = $_SESSION['url'];
        $_SESSION['url'] = BaseController::getAddress();
    }
    
    //echo "<pre>";print_r($_SESSION);

    // a) Check if this page EXISTS
    if(!BaseModel::pageExists($_GET['Controller'], $_GET['Action'])) {		
        throw new Exception ("The page you requested was not found.");
    // b) Check if this user has permission to view this page
    } else if(!UserModel::userHasPermission($_SESSION['user_email'], $_GET['Controller'], $_GET['Action'], $_GET, $_SESSION['company_id'], $_SESSION['team_id'])) {
        throw new Exception("You are not permitted to view the page you requested.");
    }

    $controller = $_GET['Controller'];
    $action = $_GET['Action'];
    
    if(!$controller || $controller == ''){
        UserModel::logUserOut();
        exit; //May never be reached, but it is put in here for safety
    }

    if(file_exists('Controller/'.$controller.'Controller.php')){
        require_once('Controller/'.$controller.'Controller.php');

        $class = $controller.'Controller';
        $method = $action;
        $page = new $class();
        if(method_exists($class, $method)){
            $page->$method();
        }else{
            throw new Exception("The Action '{$action}' does not exist. This has been logged, and will be looked into.");
        }
    }else{
        throw new Exception("The Controller '{$controller}' does not exist. This has been logged, and will be looked into.");
    }
        
} catch (Exception $exception) {
    // render properly
    require_once('View/BaseView.php');
    $view = new BaseView();
    $view->setTitle("An error has occurred");

    $error_message = $exception->getMessage();
    
    $view->render(
            "<center><div style='' class='ui-state-error ui-corner-all'>
            <p><span class='ui-icon ui-icon-alert' style='float: left; margin-right: .3em;'></span> 
                <strong>Error:</strong> {$error_message}.</p>
                <input type='button' class='ui-state-default ui-corner-all' value='Back' onclick='window.back();' /><br /><br />
            </div></center><br />"

    );

    $BaseModel = new BaseModel();
    $BaseModel->writeToLog($error_message." SESSION_DUMP: ".serialize($_SESSION));
}

// output
ob_end_flush();

?>
