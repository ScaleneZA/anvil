<?php
require_once('Model/UserModel.php');
class BaseView
{
    private $version_number = 'BETA';
    var $title = '';
    var $theme = 'brown';

    function __construct()
    {	
    }

    function render($content)
    {
        //echo"<pre>";print_r($_SESSION);echo"</pre>";
        
        ob_start();
        //If not title is set, use the Action and Controller as the title (Makes some sense at least)
        if(!$this->title){
            $this->title = $_GET['Action'].' '.$_GET['Controller'];
        }
        
        $UserModel = new UserModel();
        $this->theme = $UserModel->getThemeName($_SESSION['theme_id']);

    ?> 

        <html>
        <head>
        <title>Anvil: <?php echo ($this->title); ?></title>

        <link type="text/css" href="css/themes/<?php echo $this->theme ?>/jquery-ui-1.8.14.custom.css" rel="stylesheet" />	
        <link type="text/css" href="css/themes/<?php echo $this->theme ?>/main.css" rel="stylesheet" />	
        <link type="text/css" href="css/date.css" rel="stylesheet" />
        <link type="text/css" href="css/freeow/freeow.css" rel="stylesheet" />
        <script type="text/javascript" src="js/jquery-1.5.1.min.js"></script>
        <script type="text/javascript" src="js/jquery.tools.min.js"></script>
        <script type="text/javascript" src="js/jquery-ui-1.8.11.custom.min.js"></script>
        <script type="text/javascript" src="js/jquery.freeow.min.js"></script>

        <script language='javascript'>
            $(window).load(function() {
                checkTab();
                $('#errorbox').hide();
                
                //To stop the glitching
                $('#tabs').fadeIn();
            });
            
            //Function to load the tabs (JQuery)
            $(document).ready(function(){
                $('#tabs').tabs();
                $('button').button();
                $('input:button').button();
                $('input:submit').button();
                $("input:date").dateinput({
                    format: "yyyy-mm-dd"
                });
                $("*.big_title").tooltip({
                    delay:0,
                    predelay: 300,
                    tipClass:'tooltip2',
                    track: true
                });
                
        <?php 
            if(!isset($_SESSION['preferences']['All']['All']['disable_ajax_warnings'])){
          ?>
                $.ajaxSetup({
                    timeout: 15000,
                    cache: false,
                    error: function(data){
                        $('#freeow').freeow('Ajax fail','A request to the server failed due to a network issue.',{
                            classes: ["gray", "error"]
                        });
                        
                        setTimeout(
                            function(){ 
                                if(confirm("A request to the server recently failed due to a network issue, would you like to reload the page? This may result in the latest change you made not being saved. (You can disable these warnings if they are too frequent.)")){
                                    window.location.reload();
                                } 
                            }, 
                            3000
                        );
                    }
                });
       <?php
                }
        ?>
                //$("[title]").tooltip({
                  //  delay:0,
                    //predelay: 1000                    
                //});
                                
            });

            //Array for the selected tab.
            controller_tab_array = new Array();
            controller_tab_array['Project'] = new Array();
            controller_tab_array['User'] = new Array();
            controller_tab_array['Support'] = new Array();
            controller_tab_array['Report'] = new Array();
            controller_tab_array['Issue'] = new Array();

            // 0 = tabMain
            // 1 = tabAdmin
            // 2 = tabUser
            // 3 = tabSupport
            controller_tab_array['Project']['TaskboardDisplay'] = 0;
            <?php
              if($_SESSION['is_admin']){
                  ?>
                controller_tab_array['Project']['ProjectList'] = 1;
                controller_tab_array['Project']['BacklogDisplay'] = 1;
                controller_tab_array['Project']['BacklogProjectList'] = 1;
                controller_tab_array['Project']['BacklogTaskList'] = 1;
                controller_tab_array['Project']['BacklogFeatureList'] = 1;
                controller_tab_array['Project']['ProjectEdit'] = 1;
                controller_tab_array['Project']['FeatureList'] = 1;
                controller_tab_array['Project']['FeatureEdit'] = 1;
                controller_tab_array['Project']['ReleaseEdit'] = 1;
                controller_tab_array['Project']['TaskList'] = 1;
                controller_tab_array['Project']['TaskEdit'] = 1;
                controller_tab_array['Project']['ReleaseArchive'] = 1;
                controller_tab_array['User']['UserList'] = 2;
                controller_tab_array['User']['UserEdit'] = 2;
                controller_tab_array['User']['TeamList'] = 2;
                controller_tab_array['User']['TeamEdit'] = 2;
                controller_tab_array['Report']['Summary'] = 3;
                controller_tab_array['Report']['BurnDown'] = 3;
                controller_tab_array['Report']['HoursCompare'] = 3;
                //controller_tab_array['Issue']['Summary'] = 4;
                controller_tab_array['Support']['Display'] = 4;
                <?php
              }else{
                  ?>
                controller_tab_array['Support']['Display'] = 2;
                controller_tab_array['Report']['Summary'] = 1;
                controller_tab_array['Report']['BurnDown'] = 1;
                <?php
              }
              ?>


            //Check that a tab is selected. If not, reload the page trying to find a tab.
            function checkTab(){
                url = window.location.href;
                var controller = url.substr(url.indexOf('Controller=')+11, url.indexOf('&') - (url.indexOf('Controller=')+11));
                var action = url.substr(url.indexOf('Action=')+7, url.indexOf('&', url.indexOf('&')+1) - (url.indexOf('Action=')+7));
                if(action == ''){
                     action = url.substr(url.indexOf('Action=')+7);
                }

                if(controller_tab_array[controller][action]){
                    var index = controller_tab_array[controller][action];
                    $('#tabs').tabs('select', index);
                }else{
                    //alert(controller);
                    //alert(action);
                }
            }

            //Originally this function was used for a lot more, but now it just redirects.
            function loadPage(url, fade_content_out){
                if(fade_content_out){
                    $('#tabs').fadeOut('fast', function(){
                        window.location = url;
                    });
                }else{
                    window.location = url;
                }
            }

            //Show the message box with the text in it
            function showMessage(text){
                $('#message_box').html(text);
                $('#message_box').slideDown('slow');
                setTimeout(function(){$('#message_box').slideUp('slow');}, 5000);
            }

            function checkValid(type, element, name){
                value = element.value;
                if(!validate(type, value)){
                    if(type == 'email'){
                        $('#freeow').freeow('Invalid email', name+' does not contain a valid email address.',{
                            classes: ["gray", "error"]
                        });
                    }else if(type == 'minimum_text'){
                        $('#freeow').freeow('Not enough text', name+' does not have enough characters.',{
                            classes: ["gray", "error"]
                        });
                    }else if(type == 'password'){
                        $('#freeow').freeow('Invalid password', name+' does not contain a valid password field (Between 6 and 20 characters)',{
                            classes: ["gray", "error"]
                        });                    
                    }else if(type == 'title'){
                        $('#freeow').freeow('Invalid title', name+' does not contain a valid value. (Between 1 and 250 characters)',{
                            classes: ["gray", "error"]
                        });                    
                    }else if(type == 'description'){
                        $('#freeow').freeow('Invalid description', name+' does not contain a valid value. (No more than 9999 characters)',{
                            classes: ["gray", "error"]
                        });                    
                    } 
                    $(element).css('border-color', 'red');
                    $(element).css('border-style', 'solid');
                }else{
                    $(element).css('border-color', '');
                    $(element).css('border-style', '');
                    return true;
                }
            }

            function validate(type, value){
                if(type == 'email'){
                    pattern = /^[^@]+@[a-zA-Z0-9._-]+\.[a-zA-Z]+$/;
                }else if(type == 'password'){
                    pattern = /.{6,20}/;
                }else if(type == 'minimum_text'){
                    pattern = /.{1,9999}/;
                }else if(type == 'title'){
                    pattern = /.{1,250}/;
                }else if(type == 'description'){
                    pattern = /.{0,9999}/;
                }else{
                    return true;
                }
                
                //alert(type);
                //alert(value);
                //alert(pattern);
                //alert(value.match(pattern));
                if(value.match(pattern)){
                    return true;
                }else{
                    return false;
                }
            }
        </script>

        </head>		
        <body>
        <div id="freeow" class="freeow freeow-top-right"></div>
        
        <br />
        <div style='width:1024px; height:50px; margin-right:auto; margin-left:auto; position:relative'>
            <img style='height:50px; position:absolute; left:0; top:0;' src='img/50px.png' />
            <img style='position:absolute; left:25; top:60; z-index:-1' src='img/loading.gif' />
            <span style='color:#FFFFCC; position:absolute; left:280; top:30;'><?php echo "<span style='font-size:9pt'>{$this->version_number}" ?></td>
        </div>
        <div id="tabs" style='width:1024px; margin-right:auto; margin-left:auto; display:none'>

            <ul>
                <li><a style='cursor:pointer' href="#tabMain" onclick="loadPage('index.php?Controller=Project&Action=TaskboardDisplay')" title='Click here for the main taskboard.'>Taskboard</a></li>
            <?php
            //Check if the user that is logged in is an administrator. If yes, show the admin tabs.
            if($_SESSION['is_admin']){
                if($_SESSION['project_count'] == 1){
                    $project_tab_url = "index.php?Controller=Project&Action=BacklogDisplay&project_id={$_SESSION['top_project_id']}";
                }else{
                    $project_tab_url = 'index.php?Controller=Project&Action=BacklogProjectList';
                }
            ?>
                <li><a style='cursor:pointer' href="#tabAdmin" onclick="loadPage('<?php echo $project_tab_url; ?>', true)" title='Click here to manage projects, stories and tasks'>Admin</a></li>
                <li><a style='cursor:pointer' href="#tabUser" onclick="loadPage('index.php?Controller=User&Action=UserList', true)" title='Click here to manage the users in your company.'>Users</a></li>
            <?php
            }
            ?>
                <li style=''><a style='cursor:pointer;' href="#tabReport" onclick="loadPage('index.php?Controller=Report&Action=Summary', true)" title='Click here for Anvil reports.'>Reporting</a></li>
                <!--<li style='border-color:brown;'><a style='cursor:pointer;' href="#tabIssue" onclick="loadPage('index.php?Controller=Support&Action=Display', true)" title="Click here for Anvil's issue tracking system.">Issues</a></li>-->
                <li><a style='cursor:pointer' href="#tabSupport" onclick="loadPage('index.php?Controller=Support&Action=Display', true)" title='Click here for Anvil support.'>Help</a></li>
            </ul>
            <div id="tabMain"></div>
            <div id="tabAdmin"></div>
            <div id="tabUser"></div>
            <div id="tabReport"></div>
            <div id="tabIssue"></div>
            <div id="tabSupport"></div>

            <table style='width:100%; height:80px'>
            
            <tr><td colspan=2><center><div style='color:#FFFFCC;'><?php echo $this->getRandomQuip(); ?></div></center></td></tr>
            
            <tr><td style='width:100%'><div class='title ui-widget-header ui-corner-all' style='width:350px'><?php echo $this->limit_text($this->title, 35); ?></div></td>

            <td><input type='button' id='btnLogout' class='ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only' style='text-align:center; width:100px; font-size:12px' value='Logout' title='Click here to log out of Anvil' onClick='loadPage("index.php?logout=1");' \></td></tr>

            <tr><td style='height:25px'><div id='message_box' style='text-align:center; height:20px; width:350px' class='title ui-widget-header ui-corner-all'>
            <script language='javascript'>
                $('#message_box').hide();
            </script>
            </div></td>

            <td><input type='button' id='btnEditOwnDetails' class='ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only' style='text-align:center; width:100px; font-size:12px' value='Edit profile' title="Click here to edit your details" onClick='loadPage("index.php?Controller=User&Action=UserEdit&user_email=<?php echo $_SESSION['user_email']; ?>")' \></td></tr>

            </table>
            <br/>
            <div class='ui-corner-all ui-widget-content box' name='contentBox' id='contentBox'>
                <?php
                    //All the content from the Controller
                    echo $content;
                ?>
 
            </div>
            </div>

        </div>
        <div style='height:200px'></div>
        </body>

        </html>

        <?php

        //Check if a message must be shown.
        if(isset($_SESSION['show_message']) && $_SESSION['show_message'] != ''){
            ?>
            <script language = 'javascript'>
                    showMessage('<?php echo mysql_escape_string($_SESSION['show_message'])?>');
            </script>
            <?php

            //Unset the message so that it doesnt carry on appearing.
            unset($_SESSION['show_message']);
        }
        
      //  echo"<pre>";print_r($_SESSION);echo"</pre>";

        ob_end_flush();
    }

    function setTitle($title)
    {
        $this->title = $title;
    }
    
    /*
     * Function to make text fit into places by limiting it. (Mostly for the tasks)
     */
    function limit_text($text, $limit)
    {
        //changing limit to include 3 (.)s
        $limit = $limit >= 3 && strlen($text) >= 3 ? $limit-3 : $limit;
        $result = substr($text, 0, $limit);
        if(strlen($result) < strlen($text)){
            $result .= '...';
        }
        return $result;
    }
    
    function showErrorMessage($message)
    {
        echo "
        <script language='javascript'>
            $('#freeow').freeow('Error','{$message}',{
                classes: [\"gray\", \"error\"]
            });
        </script>
        ";
    }
    
    /*
    * Function to get a random motivational quip
    */
    function getRandomQuip()
    {
        $BaseModel = new BaseModel();
        $quips = $BaseModel->getQuipList();
        $id = rand(0, count($quips)-1);
        
        return isset($quips[$id]['text']) ? $quips[$id]['text'] : '';
    } 

}

?>
