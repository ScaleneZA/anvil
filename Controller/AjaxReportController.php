<?php
class AjaxReportController
{
    function buildSummary()
    {
        require_once('Model/ReportModel.php');
        $ReportModel = new ReportModel();
        
        $data = $ReportModel->getSummaryReportData($_POST['project_id'], $_SESSION['company_id']);
        echo json_encode($data);
    }
    
    function getBurnDownValues()
    {
        require_once('Model/ReportModel.php');
        $ReportModel = new ReportModel();
        
        $release_id = $_POST['release_id'];
        $project_id = $_POST['project_id'];
        
        $data = $ReportModel->getBurnDownChartData($project_id, $release_id, $_SESSION['company_id']);
        echo json_encode($data);
    }
    
    function getHoursComparisonValues()
    {
        require_once('Model/ReportModel.php');
        $ReportModel = new ReportModel();
        
        $release_id = $_POST['release_id'];
        $project_id = $_POST['project_id'];
        
        $data = $ReportModel->getHoursComparisonData($project_id, $release_id, $_SESSION['company_id']);
        echo json_encode($data);
    }
}
?>
