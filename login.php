<?php
unset($_SESSION);

if(substr($_SERVER['REQUEST_URI'], -1) == "/"){
    header('location:../login.php');
    exit;
}

require_once('Model/UserModel.php');
require_once('Controller/BaseController.php');
require_once('Model/BaseModel.php');
session_start();

$displayErrorMessage = '';
//Check that the correct browser is being used:

$discontinue = false;
if(!strstr(strtolower($_SERVER['HTTP_USER_AGENT']), 'firefox') && !strstr(strtolower($_SERVER['HTTP_USER_AGENT']), 'chrome')){
    $displayErrorMessage .= "You are not using Firefox or Google Chrome. This development tool was inteded to be used in Firefox (and Google Chrome) only, and may cause incorrect alignment, and uncaught exceptions when used with another web browser.<br/>";
    $discontinue = true;
}


//Handle post
$UserModel = new UserModel();
if(isset($_POST['btnLogin'])){
    $success = $UserModel->logUserIn($_POST['Email'], $_POST['Password']);
    if($success['success']){
        header('location: index.php?Controller=Project&Action=TaskboardDisplay');
        exit;
    }else{
        $displayErrorMessage = $success['reason'];
    }
}else if(isset($_GET['forgotten_password']) && isset($_GET['email'])){
    if(generateForgotPasswordEmail($_GET['email'])){
        echo "<script language='javascript'>alert('An email has been sent to your account containing your password.'); window.location = 'login.php';</script>";
        exit;
    }else{
        echo "<script language='javascript'>alert('There is something wrong with the email address you supplied.'); window.location = 'login.php';</script>";
        exit;
    }
}else if(isset($_GET['confirm']) && isset($_GET['company_id']) && isset($_GET['key'])){
    $success = $UserModel->updateCompanyStatus($_GET['company_id'], $_GET['key'], 'active');
       
    if($success){
        echo "<script language='javascript'>alert('Welcome to Anvil. You may now log in with the username and password supplied in the email that contained this link.'); window.location = 'login.php';</script>";
        exit;
    }

    BaseModel::writeLog("Something went wrong activating a company. Company ID: {$_GET['company_id']} key: {$_GET['key']}");
    echo "<script language='javascript'>alert('Something went wrong with the registration of your company. It has been logged, and will be looked into.');  window.location = 'login.php';</script>";
    //header('location: login.php');
    exit;
}else if(!empty($_POST['btnRegister'])){
    if($_POST['password'] != $_POST['confirm_password']) {
        $displayErrorMessage .= "Passwords did not match.";
    }
    if(!BaseController::validate('minimum_text', $_POST['name']) && !$displayErrorMessage){
        $displayErrorMessage .= 'Company Name does not contain enough characters.<br/>';
    }
    //if(!BaseController::validate('email', $_POST['email']) && !$displayErrorMessage){
      //  $displayErrorMessage .= 'Email Address is not valid.<br/>';
    //}
    if(!BaseController::validate('password', $_POST['password']) && !$displayErrorMessage){
        $displayErrorMessage .= 'Password needs to be at least 6 characters and less than 20 characters<br/>';
    } 
    if(!isset($displayErrorMessage) || $displayErrorMessage == ''){
        $UserModel = new UserModel();
        $success = $UserModel->addCompany($_POST['name'], $_POST['company_information'], $_POST['email'], $_POST['password']);
        $company_id = $UserModel->getCompanyByEmail($_POST['email']);

        if($success){
            //$success = $UserModel->addUser($_POST['email'], $_POST['name'].' Admin', '1', $_POST['password'], $company_id);
            if($success){
                if(generateConfirmation($_POST['email'], $_POST['password'], $company_id)){
                    ?>
                    <script language='javascript'>
                    var email =  '<?php echo $_POST['email']; ?>';
                    var password =  '<?php echo $_POST['password']; ?>';
                    confirm("An email has been sent to your address supplied. Follow the link sent in this email to confirm the log-in details.");
                        window.location = 'login.php';
                    </script>
                    <?php
                }else{
                    $displayErrorMessage = 'The email failed to send. Please contact us for more information.';
                }
            }else{
                $displayErrorMessage = 'The email address is already registered with us under a different company! ';
            }

        }else{
            $displayErrorMessage = 'Something went wrong with the registration process. Please email us!';
        }

    }
}

ob_start();
generateAllHTML($displayErrorMessage, $discontinue);
ob_end_flush();

function generateAllHTML($errorMessage, $discontinue)
{
    ?>
    <html style="background: #151311;">
    <head>
        <script type="text/javascript" src="js/jquery-1.5.1.min.js"></script>
        <script type="text/javascript" src="js/jquery-ui-1.8.14.custom.min.js"></script>
        <link type="text/css" href="css/themes/brown/jquery-ui-1.8.14.custom.css" rel="stylesheet" />
	    <link rel="stylesheet" href="css/queryLoader.css" type="text/css" />
	    <script type='text/javascript' src='js/queryLoader.js'></script>
    </head>
    
    <center>
    <table style='height:7%'><td></td></table> <!--Hack table -->
    <?php

    echo "
            <div style='width: 350px;' class='ui-state-error ui-corner-all' id='JSError' name='JSError'>
            <p><span class='ui-icon ui-icon-alert' style='float: left; margin-right: .3em;'></span>
                <strong>Error:</strong>You do not have Javascript enabled. Please enable Javascript before using this development tool.</p>
            </div>
    ";
    
    //Used echo instead of closing PHP tags because I'm accessing a PHP variable ($message)
    if(isset($errorMessage) && $errorMessage != ''){
        echo ("
            <div style='width: 350px;' class='ui-state-error ui-corner-all' id='errorbox' name='errorbox'>
            <p><span class='ui-icon ui-icon-alert' style='float: left; margin-right: .3em;'></span>
                <strong>Error:</strong> {$errorMessage}</p>
            </div>
            <br />
        "
        );
    }else{
        //So that the slide down still works.
        echo ("<div name='errorbox' id='errorbox'></div>");
    }

    ?>

    <script language='javascript'>
        $(document).ready(function() {
            $('#JSError').hide();
            $('#errorbox').hide();
            //setTimeout(function(){$('#errorbox').slideUp('slow');}, 8000);
            $('#tabs').tabs({
                select: function(event, ui) {
                    if(ui.index == 0){
                        $('#content').css('width', '350px');
                        window.location.hash = 'tabs-login';
                    }else{
                        $('#content').css('width', '400px');
                        window.location.hash = 'tabs-register';
                    }
                }
            });
            
            //alert(window.location.hash);
            if(window.location.hash == '#tabs-register')
            {
                $('#content').css('width', '400px');
                $('#tabs').tabs('select', 1);
            }
            
        <?php if(!$discontinue){ ?>
            $('#registerButton').html("<center><input style='font-size:10pt' name='btnRegister' id='btnRegister' type='submit' value='Submit' /></center>");
            $('#loginButton').html("<br /><input type='submit' class='ui-state-default ui-corner-all' style='font-size:10pt' name='btnLogin' id='btnLogin' value='Login' />");
            $('input:submit').button();
            $('#btnBack').button();
            $('#errorbox').slideDown('slow');
            $('#tabs').fadeIn('slow');
        <?php } ?>
        
            QueryLoader.init();
        });
        
        function handleForgotPassword(){
            if($('#Email').val() == ''){
                alert('Please enter an email address into the email box for the password to be sent to.');
            }else{
                window.location = 'login.php?forgotten_password=1&email='+$('#Email').val();
            }
        }
    </script>

    <div id='content' style="width:350px; padding: 1px;">
        <center>
            <img style='height:50px;' src='img/50px.png' /><br />
        </center>
        
    <div style= 'position:fixed; bottom:0; right:0; text-align:center; width:350px; height:150px; background: url("img/Butternet_1.png") 50% 50% no-repeat; cursor:pointer' onclick='window.location = "http://www.butternet.com"' title='Go to Butternet.'>
        <span style='color:white'>Powered by:</span><br/>
    </div>
    
        <div id='tabs' style='font-size:10pt; display:none'>
            <ul>
                <li><a href='#tabs-login'>Login</a></li>
                <li><a href='#tabs-register'>Register</a></li>
            </ul>
            <?php
                echo "
                    <div id='tabs-login'>
                    ";
                generateLoginHTML();  
                echo "
                    </div>
                    <div id='tabs-register'>
                    ";
                generateRegistrationHTML();
                echo "
                    </div>
                ";
            ?>
        </div>
    </div>
    </center>

    </html>
    <?php
}

function generateLoginHTML()
{
?>
    <center>
    <form action='login.php' method='POST'>
        <table style='padding-top:20px; color:#FFFFCC'>
            <tr>
                <td width='80px'>Email: </td>
                <td> <input type='text' style='font-size:10pt; width:200px' class='ui-corner-all' name='Email' id='Email'></input> </td>
            </tr>
            <tr>
                <td>Password: </td>
                <td> <input type='password' style='font-size:10pt; width:200px' class='ui-corner-all' name='Password' id='Password'></input> </td>
            </tr>
            <tr>
                <td colspan=2 style='text-align:center' id='loginButton'><br/>

                </td>
            </tr>
        </table>
         <a style='font-size:8pt; color:#FFFFCC; position:absolute; left:118px; top:210px' name="btnForgot" href='javascript:handleForgotPassword();' title='Forgot your password? Click here to send yourself an email with your password.'>I forgot my password</a>
    </form>
    </center>
<?php
}

function generateRegistrationHTML()
{
    ?>
    <style>
    input {
        background-color:#FFFFEE;
        border:1px solid black;
        padding:5px;
    }
    </style>
    <span style='font-size: 14pt; color:#FFFFCC;'>Register your company</span>
    <form action='login.php#tabs-register' method='POST' autocomplete="off">
        <table class='ui-corner-all ui-widget-content box' style='padding:15px; color:#FFFFCC; width:80%; text-align:center'> 
            <tr title='The name of your company.'>
                <td style='text-align:left'>Company Name:</td>
            </tr><tr height=50px valign=top>
                <td> <input style='width:300px' class='ui-corner-all' type='text' name='name' id='name' value='<?php echo empty($_POST['name']) ? '' : $_POST['name']; ?>'></input> </td>
            </tr>
            </tr>
    <!--     
             <tr title='Some details about your company. Who you are, what type of company you are, etc.'>
                <td width='150px'>Company Info:</td><td><textarea rows=4 style='width:300px;' id='company_information' name='company_information' ><?php //echo $_POST['company_information']; ?></textarea></td>
            </tr>
    -->
            <tr title="The email address of the administrator that will be managing this company's account."> 
                <td style='text-align:left'>Admin Email:</td></tr>
            <tr height=50px valign=top>
                <td> <input style='width:300px' class='ui-corner-all' type='text' name='email' id='email' value='<?php echo empty($_POST['email']) ? '' : $_POST['email']; ?>'></input> </td>
            </tr>
            <tr>

            <tr title="The password for the administrator that will be managing this company's account."> 
                <td style='text-align:left'>Admin Password: </td> 
                </tr><tr height=30px valign=top>
                <td> <input style='width:300px' class='ui-corner-all' type='password' name='password' id='password' value='<?php echo empty($_POST['password']) ? '' : $_POST['password']; ?>'></input> </td>
            </tr>
            <tr title="Retype the password.">
                <td style='text-align:left'>Confirm Admin Password:</td> 
            </tr><tr height=50px valign=top>
                <td> <input style='width:300px' class='ui-corner-all' type='password' name='confirm_password' id='confirm_password' value='<?php echo empty($_POST['confirm_password']) ? '' : $_POST['confirm_password']; ?>'></input> </td>
            </tr>
            <tr>
                <td colspan='2' id='registerButton'>
                </td>
            </tr>
            <br/>
    </form>

<?php
}

function generateConfirmation($email, $password, $company_id)
{
    $UserModel = new UserModel();
    $key = rand(100, 1000);
    $success = $UserModel->updateCompanyKey($company_id, $key);
    if($success){
        $link = selfURL();
        $link .= "?confirm=1&company_id={$company_id}&key={$key}";
        
        $message = "
            <h1>Anvil</h1>
            <h2>Registration confirmation</h2>
            Email: {$email}
            <br/>
            Password: {$password}
            <br/>
            <br/>
            Please click the link below to finalize registration.
            <br/>
            <a href='{$link}'>{$link}</a>
            <br/>
            <br/>
            Regards,<br/>
            Anvil Admin.
        ";

        if(BaseController::sendEmail($email, "Anvil Confirm Registration", $message)){
            return true;
        }else{
            $UserModel->deleteCompany($company_id);
        }
    }
}

function generateForgotPasswordEmail($email)
{
    $UserModel = new UserModel();
    $password = $UserModel->getUserDetails($email);
    $password = $password['password'];
    if($password){
        $message = "
            <h1>Anvil</h1>
            <h2>ForgottenPassword</h2>
            You requested for your password with the 'forgotten password' link.<br/><br/>
            Email: {$email}<br/>
            Password: {$password}<br/><br/>
            Regards,<br/>
            Anvil Admin.
        ";
        if(BaseController::sendEmail($email, "Anvil Forgotten password", $message)){
            return true;
        }
    }
}

function selfURL()
{ 
    if(!isset($_SERVER['REQUEST_URI'])){ 
        $serverrequri = $_SERVER['PHP_SELF']; 
    }else{ 
        $serverrequri = $_SERVER['REQUEST_URI']; 
    } 
        
    $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : ""; 
    $protocol = strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s; 
    $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]); 
        
    return $protocol."://".$_SERVER['SERVER_NAME'].$port.$serverrequri;
}
function strleft($s1, $s2) { return substr($s1, 0, strpos($s1, $s2)); }
