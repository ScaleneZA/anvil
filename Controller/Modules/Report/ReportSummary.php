<?php
require_once("Model/ReportModel.php");
require_once("Model/ProjectModel.php");

class ReportSummary extends BaseController
{
    protected $ReportModel;
    protected $ProjectModel;
    
    function __construct()
    {
        parent::__construct();
        $this->ReportModel = new ReportModel();
        $this->ProjectModel = new ProjectModel();
    }
    
    function display()
    {
        //$this->handlePost();
        ob_start();
        $this->buildJS();
        $this->buildSummaryTable();
        
        $contents = ob_get_contents();
        ob_clean();
        $this->view->render($contents);
    }
    
    function buildSummaryTable()
    {
        $project_array = $this->ProjectModel->getProjectArray($_SESSION['company_id'], $_SESSION['team_id']);
        ?>
        <center>
        <div class='ui-corner-all ui-widget-content' style='padding: 5px; width:37%' id='current_selection'>
        <table style='color:#FFFFCC;width:100%; text-align:center'>
            <tr>
                <td style='width:120px;' title='Select the project which you wish to report on.'>Project: </td>
                <td>
                    <select class='ui-widget-header' style='width:300px; font-weight:normal;' name='selectFeatureStatus' id='selectProjectId' onChange='buildTables();'>
                        <?php
                            foreach($project_array AS $project_id=>$project_title)
                            {
                                echo "<option style='font-weight:normal' value='{$project_id}'>{$project_title}</option>";
                            }
                        ?>	
                    </select>
                </td>
            </tr>
        </table>
        </div>
        
        <br/>
        
        <div id='loading' style='height:100px; display:none'>
            <img src='img/loading.gif' />
        </div>
        <div style='width:50%; padding:10px' class='mainGrid ui-corner-all' id='report_content'>
        <table class='mainEditGrid' style='width:100%'>
            <tr>
                <th style='width:60%'>Total Hours Completed</th>
                <td id='total_hours' style='text-align:center'></td>
            </tr>
            <tr>
                <th>Hours Completed Today</th>
                <td id='today_hours' style='text-align:center'></td>
            </tr>
        </table>
        </div>
        </center>        
        <?php
    }
    
    function buildJS()
    {
    ?>
    <script language='javascript'>
        function buildTables()
        {
            $('#loading').show();
            $('#report_content').hide();
            
            $.ajax({
                url: "index.php?Controller=AjaxReport&Action=BuildSummary",
                type: 'post',
                dataType: 'json',
                data: {
                    project_id: $('#selectProjectId').val()
                },
                success: (function(data){
                    $('#loading').hide();
                    $('#report_content').show();
                    //alert(data.total_hours);
                    $('#total_hours').html(data.total_hours);
                    $('#today_hours').html(data.today_hours);
                    
                    $('#report_content').append('<br/><hr/><br/>');
                    $.each(data.release_data, function(key, value){
                        $('#report_content').append("<table class='mainEditGrid' style='width:100%'><tr><th style='width:60%'>"+value.title+"</th><td style='text-align:center' title='Total hours done in release'>"+value.total_hours+"</td><td style='text-align:center; width:10%;' title='Click here for the different report charts of this feature.'><button style='padding:3px' class='ui-button ui-state-default ui-corner-all ui-button-text-only'  onclick='loadPage(\"index.php?Controller=Report&Action=HoursCompare&project_id="+$('#selectProjectId').val()+"&release_id="+key+"\")'><img src='img/burndown.png'/></button></td></tr></table><br/>");
                    });
                }),
                error: function(){
                    alert("Something went wrong loading the report data. Please reload the page.");
                }
            });
        }
        
        $(document).ready(function(){
            buildTables();
        });
    </script>
    <?php
    }
}