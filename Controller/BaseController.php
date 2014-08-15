<?php
require_once("View/BaseView.php");
require_once("Model/BaseModel.php");
class BaseController
{
    var $view = null;
    function __construct()
    {
        $this->view = new BaseView();
    }
    
    function makePostSafe($post_array)
    {
        $server = $_SERVER["SERVER_NAME"];
        if(strstr($server, 'localhost') || strstr($server, '127.0.0.1')){

            foreach($post_array AS $key=>$value){
                $return_post_array[$key] = mysql_escape_string($value);
            }
            
        }else{
            //Bluehost already accounts for this.
            return $post_array;
        }
        
        return $return_post_array;
    }

    /*
     * Custom function to validate a field according to the type supplied.
     * Used in quite a few places, so its best to be in this BaseController.
     */
    function validate($type, $value)
    {
        switch($type){
            case 'email':
                $pattern = '/^[^@]+@[a-zA-Z0-9._-]+\.[a-zA-Z]+$/';
                break;
            case 'password':
                $pattern = '/.{6,20}/';
                break;
            case 'minimum_text':
                $pattern = '/.{1,9999}/';
                break;
            case 'title':
                $pattern = '/.{1,250}/';
                break;
            case 'description':
                $pattern = '/.{0,9999}/';
                break;
            default:
                return true;
        }

        if(preg_match($pattern, $value)){
            return true;
        }		

    }
    
    function sendEmail($email_to, $subject, $email_content, $include_butternet=1)
    {
        require_once('lib/phpmailer/class.phpmailer.php');
        $BaseModel = new BaseModel();
        
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host = 'mail.butternet.com';
        $mail->Username = 'support@butternet.com';
        $mail->Password = 's0l0m0nb';
        $mail->SMTPAuth = TRUE;
        $mail->Port = 26;
        $mail->isHTML(true);
        
        $mail->From = 'support@butternet.com';
        $mail->FromName = 'Butternet Support';
        $mail->Subject = $subject;
        
        $mail->AddAddress($email_to);
        
        if($include_butternet){
            $mail->AddAddress('support@butternet.com');
        }
        
        $mail->Body = $email_content;
        
        if(!$mail->send()){
            $BaseModel->writeToLog("Failed to send email to: {$email_to}\n ");
            return false;
        }
        return true;
        
    }
    
    public static function getAddress()
    {
        /*** check for https ***/
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
        /*** return the full address ***/
        return $protocol.'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    }

}
?>
