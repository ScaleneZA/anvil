<?php
require_once("Controller/BaseController.php");
//require_once("Model/SupportModel.php");
class SupportController extends BaseController
{
    function __construct(){
        parent::__construct();
    }
    
    function display()
    {
        ob_start();
        
        echo "
        <div id='support-tabs'>
            <ul>
                <li><a href='#tab-user-documentation'>User documentation</a></li>
                <!-- <li><a href='#tab-suggestion-box'>Suggestion box</a></li> -->
                <li><a href='#tab-log-ticket'>Log a ticket</a></li>
            </ul>
            
            <div id='tab-user-documentation' class='ui-tabs-panel ui-widget-content ui-corner-bottom'>
        ";
        
        $this->sectionUserDocumentation();
        
        /*
        echo "
            </div>
            <div id='tab-suggestion-box' class='ui-tabs-panel ui-widget-content ui-corner-bottom'>
        ";
        
        $this->sectionSuggestionBox();
        */
        
        echo "
            </div>
            <div id='tab-log-ticket' class='ui-tabs-panel ui-widget-content ui-corner-bottom'>
        ";
        
        $this->sectionOpenTicket();
        
        echo "
            </div>
        </div>
        
        <script>
            $(document).ready(function(){
                $('#support-tabs').tabs();
            });
        </script>
        ";
        
        $content = ob_get_contents();
        ob_clean();

        $this->view->setTitle("Anvil support");
        $this->view->render($content);
    }
    
    function sectionUserDocumentation()
    {
        ob_start();
        ?>
            <table class='ui-corner-all mainGrid'>
                <tr>
                    <th colspan=2 style='font-size:14pt'>User documentation</th>
                </tr>
        <?php
        
        if($_SESSION['is_admin']){
            if($_SESSION['user_email'] == $_SESSION['company_admin_email']){
                ?>
                <tr>
                    <td style='border-width:0px'>
                        <a href='docs/Anvil_User_Manual_Company_Admin.pdf' target="_blank" onclick="alert('Warning: This documentation is out of date. We are working on creating up-to-date documentation, but you know how it is.');">
                            <table style='width:100%; border-width:0px' cellpadding=5px><tr>
                                <td style='width:100px'><img src='img/pdfIcon.png' style='width:100px' title='Download this PDF document'></td>
                                <td><b>Company Administrator</b> - Documentation for all the features that the company administrator will need to know.</td>
                            </tr></table>
                        </a>
                    </td>
                </tr>
                <?php
            }
                ?>
                <tr>
                    <td style='border-width:0px'>
                        <a href='docs/Anvil_User_Manual_Team_Admin.pdf' target="_blank" onclick="alert('Warning: This documentation is out of date. We are working on creating up-to-date documentation, but you know how it is.');">
                            <table style='width:100%; border-width:0px' cellpadding=5px><tr>
                                <td style='width:100px'><img src='img/pdfIcon.png' style='width:100px' title='Download this PDF document'></td>
                                <td><b>Team Administrator</b> - Documentation for all the features that the team administrator(s) will need to know.</td>
                            </tr></table>
                        </a>
                    </td>
                </tr>
                <?php
        }
                ?>
                <tr>
                    <td style='border-width:0px'>
                        <a href='docs/Anvil_User_Manual_Team_Member.pdf' target="_blank" onclick="alert('Warning: This documentation is out of date. We are working on creating up-to-date documentation, but you know how it is.');">
                            <table style='width:100%; border-width:0px' cellpadding=5px><tr>
                                <td style='width:100px'><img src='img/pdfIcon.png' style='width:100px' title='Download this PDF document'></td>
                                <td><b>Team Member</b> - Documentation for all the features that the standard team members will need to know.</td>
                            </tr></table>
                        </a>
                    </td>
                </tr>
            </table>
        <?php
        ob_end_flush();
    }
    
    function sectionOpenTicket()
    {
        ob_start();
        ?>
            <table class='ui-corner-all mainGrid' style='width:600px; text-align:center'>
                <tr>
                    <th colspan=2 style='font-size:14pt'>Something wrong with Anvil?</th>
                </tr>
                <tr id='loading' style='display:none'>
                    <td style='padding:5px'>Sending email...<br/><img src='img/loading.gif' /></td>
                </tr>
                <tr id='content'>
                    <td class='ui-corner-all'>
                        <table style='width:500px'>
                        <table>
                            <tr>
                                <td  style='border-width:0px'>
                                    Title:
                                </td>
                                <td  style='border-width:0px'>
                                    <input type='text' style='width:300px' id='title' />
                                </td>
                            </tr>
                            <tr>
                                <td  style='border-width:0px'>
                                    Type:
                                </td>
                                <td  style='border-width:0px'>
                                    <select id='type' style='width:300px' />
                                        <option value='other'>Other</option>
                                        <option value='bug'>Program bug</option>
                                        <option value='spelling'>Spelling mistake</option>
                                        <option value='ui'>Difficulty to use interface.</option>
                                        <option value='speed'>Slow response time</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td  style='border-width:0px'>
                                    Description:
                                </td>
                                <td  style='border-width:0px'>
                                    <textarea style='width:500px; height:150px' id='description'></textarea>
                                </td>
                            </tr>
                            <tr>
                                <td colspan=2 style='text-align:center;border-width:0px'><hr/><input type='button' value='Log ticket' onclick='submitTicket();' /></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <script>
              function submitTicket(){
                $('#content').hide();
                $('#loading').show();
                $.post("index.php?Controller=Ajax&Action=sendTicketEmail", { title: $('#title').val(), type: $('#type').val(), description: $('#description').val()},
                    function(data) {
                        window.location = 'index.php?Controller=Support&Action=Display';
                    }
                );
              }
           </script>
        <?php
        ob_end_flush();
    }
    
    function sectionSuggestionBox()
    {
        ob_start();
        ?>
            <table>
                <tr>
                    <td></td>
                </tr>
            </table>
        <?php
        ob_end_flush();
    }
}
?>
