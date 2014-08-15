<?php
//ini_set('include_path', '../');
//echo ini_get('include_path');
require_once('Controller/BaseController.php');
require_once('Model/BaseModel.php');

$BaseModel = new BaseModel();

$pending_emails = $BaseModel->executeQuery("
    SELECT * 
    FROM pending_email
    WHERE status = 'pending'
");

if(!is_array($pending_emails)){
    exit;
}

foreach($pending_emails AS $email_information){
    $id = $email_information['id'];
    $to = $email_information['to'];
    //$from = $email_information['from'];
    $subject = $email_information['subject'];
    $body = $email_information['body'];
    
    $success = BaseController::sendEmail($to, $subject, $body, 0);
    
    if($success){
        $BaseModel->executeUpdateQuery("
            UPDATE pending_email
            SET status = 'sent'
            WHERE id = {$id}
        ");
    }else{
        $BaseModel->executeUpdateQuery("
            UPDATE pending_email
            SET status = 'failed'
            WHERE id = {$id}
        ");
        
        BaseController::sendEmail('support@butternet.com', 'Failed to send email '.$to, "<b style='font-color:red'>Failed to send email</b><br/>{$body}", 0);
    }
}

?>