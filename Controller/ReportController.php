<?php
require_once("Controller/BaseController.php");

class ReportController extends BaseController
{  
    function __construct()
    {
        //temp
        //throw new Exception('The reporting functionality of Anvil is not yet available. This is coming soon!');
        
        if(!isset($_SESSION['pro']) || $_SESSION['pro'] != 1){
            //throw new Exception("The Reporting functionality is only for Pro users. <a href='index.php'>Upgrade to Pro now</a>");
           // exit;
        }
    }
    
    function summary()
    {
        require_once('Controller/Modules/Report/ReportSummary.php');
        $ReportSummary = new ReportSummary();
        $ReportSummary->display();
    }
    
    function burnDown()
    {
        require_once('Controller/Modules/Report/ReportBurnDown.php');
        $ReportBurnDown = new ReportBurnDown();
        $ReportBurnDown->display();
    }
    
    function hoursCompare()
    {
        require_once('Controller/Modules/Report/ReportHoursCompare.php');
        $ReportHoursCompare = new ReportHoursCompare();
        $ReportHoursCompare->display();
    }
    
}