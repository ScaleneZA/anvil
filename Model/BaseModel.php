<?php
/*
 * This class is extended by the other models. It is used to run queries on the database.
 */
class BaseModel
{
    private $connection;

    /*
     * Connect to the database
     */
    private function connect()
    {
        $db_config = self::getDBConfig();
        
        // Connect to database server
        $this->connection = mysql_connect("localhost", $db_config['username'], $db_config['password']) or die("Anvil database is unavailible. We are working on the problem");
        if(!$this->connection){
                return false;
        }

        mysql_select_db ($db_config['db'], $this->connection) or die("Anvil database is unavailible. We are working on the problem");
    } 

    /*
     * Disconnect from the database.
     */
    private function disconnect()
    {
        if($this->connection){
                mysql_close($this->connection);
        }
    }

    /*
     * Used to run SELECT Statements on the database.
     * Returns a result.
     */
    function executeQuery($query_text, $debug=null)
    {
        //Very useful debug. To test the sql if problems are occuring.
        //If a value of 1 is supplied, print out the SQL that will be run.
        if($debug == 1){
            echo $query_text;exit;
        }
        if(!$query_text) return false;

        if(!$this->connection){
            $this->connect();
        }
        
        $query = mysql_query($query_text, $this->connection);
        if(!$query) return false;
        $data_array = array();
        while ($row = mysql_fetch_assoc($query)){
            $data_array[] = $row;
        }

        //$this->disconnect();

        //If a value of 2 is supplied as a debug, print out the array that gets returned.
        if($debug == 2){
            echo "<pre>";
            print_r($data_array);
            exit;
        }
        return $data_array;
    }

    /*
     * Used to run an update/insert/delete command on the database.
     */
    function executeUpdateQuery($query_text, $debug=null)
    {
        //Very useful debug. To test the sql if problems are occuring.
        //If a value of 1 is supplied, print out the SQL that will be run.
        if($debug == 1){
            echo $query_text;exit;
        }
        if(!$query_text) return false;

        if(!$this->connection){
            $this->connect();
        }
        
        $query = mysql_query($query_text, $this->connection);

        //$this->disconnect();

        //If a value of 2 is supplied as a debug, print out the array that gets returned.
        if($debug == 2){
            echo "<pre>";
            print_r($query);
            exit;
        }
        return $query;
    }
    
    /*
    * Function to write logs to the DB
    */
    function writeToLog($message)
    {
        $this->executeUpdateQuery("
            INSERT INTO log VALUES (NULL, '{$message}');
        ");
    }
    
    private static function getDBConfig()
    {
        $server = $_SERVER["SERVER_NAME"];
        if(strstr($server, 'localhost') || strstr($server, '127.0.0.1')){
            $filename = 'db_config_local';
        }else{
            $filename = 'db_config_live';
        }
        
        $file_lines = file("lib/{$filename}.cnf");
        
        foreach($file_lines AS $line){
            $line = trim($line);
            
            //skip comments
            if(substr($line, 0, 1) == '#') continue;
            
            if(substr($line, 0, 3) == 'db:'){
                $return_array['db'] = substr($line, 3);
            }else if(substr($line, 0, 9) == 'username:'){
                $return_array['username'] = substr($line, 9);
            }else if(substr($line, 0, 9) == 'password:'){
                $return_array['password'] = substr($line, 9);
            }
            
        }
        
        //echo "<pre>"; print_r($return_array); exit;
        return $return_array;
    }


    /*
    * Function to get a list of all motivational quips
    */
    function getQuipList()
    {
        $quips = $this->executeQuery("
            SELECT text
            FROM quip
            ");
        return $quips;
    }

    /*
     * Function that may be used in the future to check if a page exists
     */
    static function pageExists($controller, $action)
    {
        return true;
    }
    
    //Here instead of UserModel because it is used all over Anvil. Less lines of code to include everytime.
    function saveUserPreference($controller, $action, $key, $value, $email)
    {
        $user_preferences = $this->executeQuery("
            SELECT id
            FROM user_preference
            WHERE user_email = '{$email}'
            AND controller = '{$controller}'
            AND `action` = '{$action}'
            AND `key` = '{$key}'
        ");
        
        if(!$value || $value == 'null'){
            $this->executeUpdateQuery("
                DELETE FROM user_preference
                WHERE user_email = '{$email}'
                AND controller = '{$controller}'
                AND `action` = '{$action}'
                AND `key` = '{$key}'
            ");
            unset($_SESSION['preferences'][$controller][$action][$key]);
            return;
        }
        
        if(is_array($user_preferences) && count($user_preferences) > 0){
            $this->executeUpdateQuery("
                UPDATE user_preference 
                SET value = '{$value}'
                WHERE id = {$user_preferences[0]['id']};
            ");
        }else{
            $this->executeUpdateQuery("
                INSERT INTO user_preference(controller, `action`, `key`, value, user_email)
                VALUES ('{$controller}', '{$action}', '{$key}', '{$value}', '{$email}')
            ");
        }
        
        $_SESSION['preferences'][$controller][$action][$key] = $value;
    }

}

?>
