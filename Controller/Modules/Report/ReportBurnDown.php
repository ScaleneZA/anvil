<?php
require_once("Model/ReportModel.php");
require_once("Model/ProjectModel.php");

class ReportBurnDown extends BaseController
{
    protected $ReportModel;
    protected $ProjectModel;
    
    function __construct()
    {
        //throw new exception("This feature is currently broken and has been shut down automatically for your safety. We are working on the problem. ");
        parent::__construct();
        $this->ReportModel = new ReportModel();
        $this->ProjectModel = new ProjectModel();
    }
    
    function display()
    {
        //$this->handlePost();
        ob_start();
        $this->buildJS();
        $this->buildHtml();
        
        $contents = ob_get_contents();
        ob_clean();
        $this->view->title = 'Burn down chart';
        $this->view->render($contents);
    }
    
    function buildHtml()
    {
        ?>
            
        <button onClick="loadPage('index.php?Controller=Report&Action=Summary&project_id=<?php echo $_GET['project_id']; ?>');" class='ui-state-default ui-corner-all' title='Click here to go back to the summary report.'>Summary Report</button>
        
        <hr/>
        
        <div id='loading' style='height:100px; text-align:center; display:none'>
            <img src='img/loading.gif' />
        </div>
        <div id='report_content_outer'>
        <center>
            <div style='width:50%; padding:10px;' class='' id='report_content'>
            
            </div>
        </center>
        </div>
        
        <?php
    }
    
    function buildJS()
    {
        ?>
        <script type="text/javascript" src="js/jqplot/jquery.jqplot.min.js"></script>
        <script type="text/javascript" src="js/jqplot/plugins/jqplot.canvasTextRenderer.min.js"></script>
        <script language="javascript" type="text/javascript" src="js/jqplot/plugins/jqplot.canvasAxisLabelRenderer.js"></script>
        <script language="javascript" type="text/javascript" src="js/jqplot/plugins/jqplot.canvasTextRenderer.js"></script>
        <script language="javascript" type="text/javascript" src="js/jqplot/plugins/jqplot.canvasAxisTickRenderer.js"></script>
        <script language="javascript" type="text/javascript" src="js/jqplot/plugins/jqplot.dateAxisRenderer.js"></script>
        <script language="javascript" type="text/javascript" src="js/jqplot/plugins/jqplot.highlighter.js"></script>
        <script language="javascript" type="text/javascript" src="js/jqplot/plugins/jqplot.cursor.js"></script>
        
        <script language='javascript'>
        $(window).load(function(){
            getBurnDownData();
        });
        
        function getBurnDownData()
        {
            $('#loading').show();
            //$('#report_content_outer').hide();
            
            $.ajax({
                url: "index.php?Controller=AjaxReport&Action=GetBurnDownValues",
                type: 'post',
                dataType: 'json',
                data: {
                    //could change later on to be dynamic with a DL List.
                    release_id: '<?php echo $_GET['release_id']; ?>',
                    project_id: '<?php echo $_GET['project_id']; ?>'
                },
                success: (function(data){
                    buildGraph(data);
                    $('#loading').hide();
                    //$('#report_content_outer').show();
                }),
                error: function(){
                    alert("Something went wrong loading the report data. Please reload the page.");
                }
            });
        }
        
        function buildGraph(json_data)
        {
            var report_array = Array();
            var count = 0;
            $.each(json_data, function(key, value){
                report_array.push([key, value]);
                count ++;
            });
            
            //console.log(report_array);
            
            $.jqplot.config.enablePlugins = true;
            
            //var line1=[['2011-09-30 4:00PM',4], ['2011-10-30 4:00PM',6.5], ['2011-11-30 4:00PM',5.7], ['2011-12-30 4:00PM',9], ['2011-01-30 4:00PM',8.2]];
            var plot = $.jqplot('report_content',  [report_array],
            { 
                title:'Burn down chart',
                axes:
                {
                    yaxis:
                    {
                        min:0,
                        labelRenderer: $.jqplot.CanvasAxisLabelRenderer
                    },
                    xaxis:{
                        renderer:$.jqplot.DateAxisRenderer,
                        label:"DAYS",
                        tickInterval:'1 days',
                        rendererOptions:{
                            tickRenderer:$.jqplot.CanvasAxisTickRenderer
                        },
                        tickOptions:{formatString:'%Y-%m-%d', fontSize:'8pt', fontFamily:'Tahoma', angle:-40, fontWeight:'normal', fontStretch:1, color:'black'}
                    },
                },
                highlighter:
                {
                    bringSeriesToFront: true,
                    tooltipLocation: "e",
                    tooltipOffset: 0,
                    formatString: "<div style='color:black; border: 1px solid black; background-color:#F3DEB4; padding:2px; ' class=\'jqplot-highlighter\'>%s: <strong>%s</strong></div>"
                    
                },
                cursor:{show:false},
                series:[{lineWidth:4, color:"brown"}],
                seriesDefaults: {
                    pointLabels: { show:true, edgeTolerance: 5 }
                }
            }); 
            
            //$('#report_content').append('<br/><hr/><br/>');
        }
        
       </script>
        <?php
    }

}