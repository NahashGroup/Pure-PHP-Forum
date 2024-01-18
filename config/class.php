<?php

class everyone
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    function clean_text($data)
    {

        $blacklist_array = [];
        $params = $types = [];
        $blacklist_statement = 'SELECT * FROM blacklist';
        $blacklist_statement = $this->execute_query($blacklist_statement, $params, $types, false);

        while ($blacklist_sample = $blacklist_statement->fetchArray(SQLITE3_ASSOC)) {
            array_push($blacklist_array, $blacklist_sample);
        }

        $count_array = count($blacklist_array);

        for ($row = 0; $row < $count_array; $row++) {
            if (stripos($data, $blacklist_array[$row]["blacklist"]) !== false) {
                error_or_success_popup("error", "You shouldn't write that.", "?location=index");
            }
        }

        #$data = $this->db->escapeString($data);
        #$data = stripslashes($data);
        $data = trim($data);
        
        return $data;
    }

    public function add_to_logs($type, $log)
    {
        $log_statement = 'INSERT INTO "logs" ("log_type","log_data", "log_timestamp") VALUES (:log_type, :log_data, :log_timestamp)';
        $params[':log_type'] = $type; $types[':log_type'] = SQLITE3_TEXT;
        $params[':log_data'] = $log; $types[':log_data'] = SQLITE3_TEXT;
        $params[':log_timestamp'] = date("Y-m-d H:i:s"); $types[':log_timestamp'] = SQLITE3_TEXT;
        $log_statement = $this->execute_query($log_statement, $params, $types, false);
    }

    public function execute_query($query, $params = [], $types = [], $rollback = false)
    {
        $statement = $this->db->prepare($query);

        if ($statement) {
            if (isset($params) && isset($types)) {
                foreach ($params as $key => $value) {
                    $type = isset($types[$key]) ? $types[$key] : SQLITE3_TEXT;
                    $statement->bindValue($key, $value, $type);
                }
            }

            $result = $statement->execute();

            if ($result) {
                $params = [];
                $types = [];
                return $result;
            } else {
                $debug = debug_backtrace()[0];
                $log = "Query execution error in file " . $debug['file'] . " to the line " . $debug['line'] . " : " . $this->db->lastErrorMsg();
                if ($rollback === true) {
                    $this->db->exec('ROLLBACK');
                }
                $this->add_to_logs("fatal", $log);
            }
        } else {
            $debug = debug_backtrace()[0];
            $log = "Query preparation error in the file " . $debug['file'] . " to the line " . $debug['line'] . " : " . $this->db->lastErrorMsg();
            if ($rollback === true) {
                $this->db->exec('ROLLBACK');
            }
            $this->add_to_logs("fatal", $log);
        }

    }


    public function check_user_exists($username)
    {
        $check_user_exists_statement = 'SELECT COUNT(*) FROM members WHERE LOWER(username) = :username LIMIT 1';
        $params[':username'] = strtolower($username); $types[':username'] = SQLITE3_TEXT;
        $check_user_exists_statement = $this->execute_query($check_user_exists_statement, $params, $types, false)->fetchArray(SQLITE3_NUM);
        if ($check_user_exists_statement[0] > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function ratelimit($action, $ratelimit_seconds, $main_redirect)
    {
        $session_key = $action . '_last_time';
        if (isset($_SESSION[$session_key])) {
            $time_since_last_action = time() - $_SESSION[$session_key];
            if ($time_since_last_action < $ratelimit_seconds) {
                $remaining_time = $ratelimit_seconds - $time_since_last_action;
                if ($remaining_time > 0) {
                    $ratelimit_message = "Please wait " . $remaining_time . " seconds before performing this action again.";
                    if ($main_redirect === true) {
                        error_or_success_popup("error", $ratelimit_message, "?location=index");
                    } else {
                        error_or_success_popup("error", $ratelimit_message, false);
                    }
                }
            }
        }
        $_SESSION[$session_key] = time();
    }

}

class visitor extends everyone
{
    public function __construct($db) {
        parent::__construct($db);
        if (is_logged_in()) {
            die();
        }
    }

    public function register($token){
        global $registration_case;
        if ($registration_case === "enabled") {
        if (isset($_POST["register"]) && sha1(session_id()) === $token) {
            if ($_SESSION['code'] === strtolower($_POST["code"])) {

                $username = (string) filter_input(INPUT_POST, "username");
                $password = (string) filter_input(INPUT_POST, "password");
                $confirm_password = (string) filter_input(INPUT_POST, "confirm_password");
                $address = (string) filter_input(INPUT_POST, "address");
        
                $this->register_gears($username, $password, $confirm_password, $address);

            } else {
                error_or_success_popup("error", "The captcha is incorrect. Please try again.", false);
              }
            
        }
    }

    }

    private function register_gears($username, $password, $confirm_password, $address)
    {

        if (validate_post($username, 1, 20) && preg_match('(^[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\'",./<>?~][A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\'",./<>?~]{0,18}[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\'",./<>?~]$)', $username)) {
            if (validate_post($password, 1, 65) && preg_match('(^[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\'",./<>?~][A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\'",./<>?~]{0,63}[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\'",./<>?~]$)', $password)) {
                if ($password === $confirm_password) {

                    if (empty($address) || validate_post($address, 1, 80) && check_email_or_jabber($address) !== false) {

                        $this->ratelimit('register', 60, false);

                        if (!$this->check_user_exists($username)) {

                            $password = password_hash($password, PASSWORD_BCRYPT);

                            if (empty($address)) {
                                $address = "None.";
                            }

                            $member_timestamp = date("Y-m-d H:i:s");

                            $register_statement = 'INSERT INTO "members" ("username", "password", "role", "member_timestamp_creation", "member_timestamp_lastseen", "address", "description", "posts_count") VALUES (:username, :password, :role, :member_timestamp_creation, :member_timestamp_lastseen, :address, :description, :posts_count)';

                            $params[':username'] = $username; $types[':username'] = SQLITE3_TEXT;
                            $params[':password'] = $password; $types[':password'] = SQLITE3_TEXT;
                            $params[':role'] = "member"; $types[':role'] = SQLITE3_TEXT;
                            $params[':member_timestamp_creation'] = $member_timestamp; $types[':member_timestamp_creation'] = SQLITE3_TEXT;
                            $params[':member_timestamp_lastseen'] = $member_timestamp; $types[':member_timestamp_lastseen'] = SQLITE3_TEXT;
                            $params[':address'] = $address; $types[':address'] = SQLITE3_TEXT;
                            $params[':description'] = "No description."; $types[':description'] = SQLITE3_TEXT;
                            $params[':posts_count'] = 0; $types[':posts_count'] = SQLITE3_INTEGER;

                            $register_statement = $this->execute_query($register_statement, $params, $types, false);

                            if ($register_statement) {
                                error_or_success_popup("success", "Your account has been successfully created and you can now log in.", "?location=login");
                            }

                        } else {
                            error_or_success_popup("error", "An account already exists under this username.", false);
                        }
                    } else {
                        error_or_success_popup("error", "E-mail or Jabber is not valid. It must be between 1 and 80 characters long and in the correct format.", false);
                    }
                } else {
                    error_or_success_popup("error", "The passwords do not match.", false);
                }
            } else {
                error_or_success_popup("error", "Password is invalid. It must be between 1 and 65 characters long. Passwords may contain alphanumeric characters and common special characters. Unicode characters are not allowed, and the password cannot contain spaces.", false);
            }
        } else {
            error_or_success_popup("error", "The username is not valid. It must be between 1 and 20 characters long. User names may contain alphanumeric characters and common special characters. Unicode characters are not allowed, and the username cannot contain spaces.", false);
        }

    }

    public function login($token){
        if (isset($_POST["login"]) && sha1(session_id()) === $token) {

            if ($_SESSION["code"] === strtolower($_POST["code"])) {

                $username = (string) filter_input(INPUT_POST, "username");
                $password = (string) filter_input(INPUT_POST, "password");
          
                $this->login_gears($username, $password);

            } else {
                error_or_success_popup("error", "The captcha is incorrect. Please try again.", false);
              }
        }
    }

    private function login_gears($username, $password)
    {

        if (validate_post($username, 1, 20) && validate_post($password, 1, 65)) {

            $get_member_id_and_password_and_role_and_banned_and_muted_statement = 'SELECT member_id, password, role, banned, muted FROM members WHERE LOWER(username) = :username;';
            $params[':username'] = strtolower($username); $types[':username'] = SQLITE3_TEXT;
            $get_member_id_and_password_and_role_and_banned_and_muted_statement = $this->execute_query($get_member_id_and_password_and_role_and_banned_and_muted_statement, $params, $types, false)->fetchArray(SQLITE3_ASSOC);

            if (isset($get_member_id_and_password_and_role_and_banned_and_muted_statement["member_id"])) {

                if (!isset($get_member_id_and_password_and_role_and_banned_and_muted_statement["banned"]) || $get_member_id_and_password_and_role_and_banned_and_muted_statement["banned"] !== "yes") {

                    if (password_verify($password, $get_member_id_and_password_and_role_and_banned_and_muted_statement["password"])) {

                        $this->ratelimit('login', 30, false);

                        $update_member_timestamp_lastseen_statement = 'UPDATE members SET member_timestamp_lastseen = :member_timestamp_lastseen WHERE member_id = :member_id';
                        $params[':member_timestamp_lastseen'] = date("Y-m-d H:i:s"); $types[':member_timestamp_lastseen'] = SQLITE3_TEXT;
                        $params[':member_id'] = $get_member_id_and_password_and_role_and_banned_and_muted_statement["member_id"]; $types[':member_id'] = SQLITE3_INTEGER;
                        $update_member_timestamp_lastseen_statement = $this->execute_query($update_member_timestamp_lastseen_statement, $params, $types, false);

                        if ($update_member_timestamp_lastseen_statement) {

                            $_SESSION['member_id'] = $get_member_id_and_password_and_role_and_banned_and_muted_statement["member_id"];
                            $_SESSION['username'] = $username;
                            $_SESSION['role'] = $get_member_id_and_password_and_role_and_banned_and_muted_statement["role"];
                            $_SESSION['muted'] = $get_member_id_and_password_and_role_and_banned_and_muted_statement["muted"];
                            $_SESSION['loggedin'] = true;
                            $_SESSION['nonce'] = bin2hex(random_bytes(16));

                            error_or_success_popup("success", "You are logged in! Your connection time is 3 hours, after which you will be automatically disconnected.", "?location=index");

                        }
                    } else {
                        error_or_success_popup("error", "Incorrect password.", false);
                    }
                } else {
                    error_or_success_popup("error", "Get out of here.", false);
                }
            } else {
                error_or_success_popup("error", "No user exists under this username.", false);
            }
        } else {
            error_or_success_popup("error", "Please enter the correct login details.", false);
        }
    }
}


class user extends everyone
{

    public function __construct($db) {
        parent::__construct($db);
        if (!is_logged_in()) {
            die();
        }
    }
    

    public function disconnect()
    {
        session_unset();
        session_destroy();
        header("Location: ?location=index");
        die();
    }

    public function user_ratelimit($action, $ratelimit_seconds, $main_redirect, $manual_username = false){

        if($manual_username !== false){
            $username = $manual_username;
        } else{
            $username = $_SESSION['username'];
        }

        $get_username_ratelimit_statement = "SELECT username FROM user_ratelimit WHERE LOWER(username) = :username";
        $params[':username'] = strtolower($username); $types[':username'] = SQLITE3_TEXT;
        $get_username_ratelimit_statement = $this->execute_query($get_username_ratelimit_statement, $params, $types, false)->fetchArray(SQLITE3_ASSOC);

        if ($get_username_ratelimit_statement && $get_username_ratelimit_statement["username"]) {

            $get_ratelimit_statement = "SELECT $action FROM user_ratelimit WHERE LOWER(username) = :username";
            $params[':username'] = strtolower($username); $types[':username'] = SQLITE3_TEXT;
            $get_ratelimit_statement = $this->execute_query($get_ratelimit_statement, $params, $types, false)->fetchArray(SQLITE3_ASSOC);

            $time_since_last_action = time() - $get_ratelimit_statement[$action];

            if ($time_since_last_action < $ratelimit_seconds) {
                $remaining_time = $ratelimit_seconds - $time_since_last_action;
                if ($remaining_time > 0) {
                    $ratelimit_message = "Please wait " . $remaining_time . " seconds before performing this action again.";
                    if ($main_redirect === true) {
                        error_or_success_popup("error", $ratelimit_message, "?location=index");
                    } else {
                        error_or_success_popup("error", $ratelimit_message, false);
                    }
                }

            } else{
                $update_user_ratelimit_statement = "UPDATE user_ratelimit SET $action = :time WHERE username = :username";
                $params[':username'] = strtolower($username); $types[':username'] = SQLITE3_TEXT;
                $params[':time'] = time(); $types[':time'] = SQLITE3_INTEGER;
                $update_user_ratelimit_statement = $this->execute_query($update_user_ratelimit_statement, $params, $types, false);
            }
            
        } else{
            $insert_user_ratelimit_statement = "INSERT INTO user_ratelimit (username, $action) VALUES (:username, :time)";
            $params[':username'] = strtolower($username); $types[':username'] = SQLITE3_TEXT;
            $params[':time'] = time(); $types[':time'] = SQLITE3_INTEGER;
            $insert_user_ratelimit_statement = $this->execute_query($insert_user_ratelimit_statement, $params, $types, false);
        }

    }

    public function get_user_values($values) {

        $columns = implode(", ", $values);
      
        $get_user_values = "SELECT " . $columns . " FROM members WHERE member_id = :member_id";
        $params[':member_id'] = $_SESSION['member_id']; $types[':member_id'] = SQLITE3_INTEGER;
      
        $get_user_values = $this->execute_query($get_user_values, $params, $types, false)->fetchArray(SQLITE3_ASSOC);
      
        $userValues = array();
      
        foreach ($values as $value) {
          if (isset($get_user_values[$value])) {
            $userValues[$value] = $get_user_values[$value];
          }
        }
      
        return $userValues;
      }

    public function save_edit_profil($token){
        if (isset($_POST["save_edit_profil"]) && sha1(session_id()) === $token) {
  
            $address = (string) filter_input(INPUT_POST, "address");
            $description = (string) filter_input(INPUT_POST, "description");
        
            $this->save_edit_profil_gears($address, $description);
        
          }
    }

    private function save_edit_profil_gears($address, $description)
    {

        if (!empty($address) || !empty($description)) {
            if (empty($address) || validate_post($address, 1, 80) && check_email_or_jabber($address) !== false) {
                if (empty($description) || validate_post($description, 1, 250)) {

                    $this->user_ratelimit('save_edit_profil', 20, false, false);

                    if (!empty($description)) {
                        $description = $this->clean_text($description);
                    }

                    if (empty($address) && !empty($description)) {
                        $update_member_infos_statement = 'UPDATE members SET description = :description WHERE member_id = :member_id';
                        $params[':description'] = $description; $types[':description'] = SQLITE3_TEXT;
                    }
                    elseif (!empty($address) && empty($description)) {
                        $update_member_infos_statement = 'UPDATE members SET address = :address WHERE member_id = :member_id';
                        $params[':address'] = $address; $types[':address'] = SQLITE3_TEXT;
                    } else {
                        $update_member_infos_statement = 'UPDATE members SET address = :address, description = :description WHERE member_id = :member_id';
                        $params[':address'] = $address; $types[':address'] = SQLITE3_TEXT;
                        $params[':description'] = $description; $types[':description'] = SQLITE3_TEXT;
                    }

                    $params[':member_id'] = $_SESSION['member_id']; $types[':member_id'] = SQLITE3_INTEGER;
                    $update_member_infos_statement = $this->execute_query($update_member_infos_statement, $params, $types, false);

                    if ($update_member_infos_statement) {
                        error_or_success_popup("success", "Your profile picture has been successfully modified.", false);
                    }

                } else {
                    error_or_success_popup("error", "The description must be between 1 and 250 characters long and in the correct format.", false);
                }
            } else {
                error_or_success_popup("error", "E-mail or Jabber is not valid. It must be between 1 and 80 characters long and in the correct format.", false);
            }
        } else {
            error_or_success_popup("error", "Please fill in at least one field.", false);
        }
    }

    public function save_edit_password($token){
        if (isset($_POST["save_edit_password"]) && sha1(session_id()) === $token) {
  
            $old_password = (string) filter_input(INPUT_POST, "old_password");
            $new_password = (string) filter_input(INPUT_POST, "new_password");
        
            $this->save_edit_password_gears($old_password, $new_password);
        
          }
    }

    private function save_edit_password_gears($old_password, $new_password)
    {

        if (validate_post($old_password, 1, 65)) {
            if (validate_post($new_password, 1, 65) && preg_match('(^[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\'",./<>?~][A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\'",./<>?~]{0,63}[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\'",./<>?~]$)', $new_password)) {

                $this->user_ratelimit('save_edit_password', 60, false, false);

                $get_member_password_statement = 'SELECT password FROM members WHERE member_id = :member_id;';
                $params[':member_id'] = $_SESSION['member_id']; $types[':member_id'] = SQLITE3_INTEGER;
                $get_member_password_statement = $this->execute_query($get_member_password_statement, $params, $types, false)->fetchArray(SQLITE3_ASSOC);

                if (isset($get_member_password_statement["password"])) {

                    if (password_verify($old_password, $get_member_password_statement["password"])) {

                        $new_password = password_hash($new_password, PASSWORD_BCRYPT);

                        $update_member_password = 'UPDATE members SET password = :password WHERE member_id = :member_id';
                        $params[':password'] = $new_password; $types[':password'] = SQLITE3_TEXT;
                        $params[':member_id'] = $_SESSION['member_id']; $types[':member_id'] = SQLITE3_INTEGER;
                        $update_member_password = $this->execute_query($update_member_password, $params, $types, false);

                        if ($update_member_password) {
                            error_or_success_popup("success", "Your password has been successfully changed.", false);
                        }
                    } else {
                        error_or_success_popup("error", "The old password is incorrect.", false);
                    }
                }
            } else {
                error_or_success_popup("error", "The new password is invalid. It must be between 1 and 65 characters long. Passwords can contain alphanumeric characters and common special characters. Unicode characters are not allowed, and passwords cannot contain spaces.", false);
            }
        } else {
            error_or_success_popup("error", "The old password must be between 1 and 65 characters long and in the correct format.", false);
        }
    }


    public function save_edit_avatar($token){
        if (isset($_POST["save_edit_avatar"]) && sha1(session_id()) === $token) {
  
            if (isset($_FILES['avatar'])) {
        
              $this->save_edit_avatar_gears($_FILES['avatar']);
        
            } else {
              error_or_success_popup("error", "Please insert a new profile picture.", false);
            }
          }
    }

    private function save_edit_avatar_gears($new_avatar)
    {

        if ($new_avatar['error'] !== UPLOAD_ERR_OK) {

            if ($new_avatar['error'] == UPLOAD_ERR_INI_SIZE) {
                error_or_success_popup("error", "The downloaded file exceeds the maximum file size this server is configured to support.", false);
            } elseif ($new_avatar['error'] == UPLOAD_ERR_FORM_SIZE) {
                error_or_success_popup("error", "Downloaded file exceeds maximum size.", false);
            } elseif ($new_avatar['error'] == UPLOAD_ERR_PARTIAL) {
                error_or_success_popup("error", "The downloaded file has not finished downloading completely. Please try again.", false);
            } elseif ($new_avatar['error'] == UPLOAD_ERR_NO_FILE) {
                error_or_success_popup("error", "No files have been downloaded.", false);
            } elseif ($new_avatar['error'] == UPLOAD_ERR_NO_TMP_DIR) {
                error_or_success_popup("error", "Your profile picture could not be processed because the server does not have a temporary directory to manage the download.", false);
            } elseif ($new_avatar['error'] == UPLOAD_ERR_CANT_WRITE) {
                error_or_success_popup("error", "Your profile picture could not be processed due to a problem writing data to the server. Please try again later.", false);
            }
        } elseif ($new_avatar['size'] > 2024000) {
            error_or_success_popup("error", "Your profile picture file is too large. Try to keep it under 2MB.", false);
        } else {
            if ($new_avatar['error'] === UPLOAD_ERR_OK && $this->upload_avatar($new_avatar)) {
                error_or_success_popup("success", "Your profile picture has been successfully modified.", false);
            } else {
                error_or_success_popup("error", "An error occurred while uploading your profile picture. Please try again.", false);
            }
        }
    }


    private function upload_avatar($new_avatar)
    {

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        $image_mime_type = mime_content_type($new_avatar['tmp_name']);

        if (!in_array($image_mime_type, $allowedTypes)) {
            return false;
        }

        if ($new_avatar['size'] > 2024000) {
            return false;
        }

        $this->user_ratelimit('save_edit_avatar', 30, false, false);

        $dimensions = getimagesize($new_avatar['tmp_name']);
        $img = imagecreatefromstring(file_get_contents($new_avatar['tmp_name']));
        $resized = imagecreatetruecolor(150, 150);
        imagecopyresampled($resized, $img, 0, 0, 0, 0, 150, 150, $dimensions[0], $dimensions[1]);

        ob_start();
        imagepng($resized);
        $imageData = ob_get_clean();

        $avatar_update_statement = 'UPDATE members SET avatar = :avatar WHERE member_id = :member_id';
        $params[':avatar'] = $imageData; $types[':avatar'] = SQLITE3_BLOB;
        $params[':member_id'] = $_SESSION['member_id']; $types[':member_id'] = SQLITE3_INTEGER;
        $avatar_update_statement = $this->execute_query($avatar_update_statement, $params, $types, false);

        ob_end_clean();

        if ($avatar_update_statement) {
            return true;
        } else {
            return false;
        }
    }


    public function new_topic($token){
        if (isset($_POST["new_topic"]) && sha1(session_id()) === $token) {

            $new_topic_name = (string) filter_input(INPUT_POST, "new_topic_name");
            $new_topic_post_data = (string) filter_input(INPUT_POST, "new_topic_post_data");
        
            $this->new_topic_gears($new_topic_name, $new_topic_post_data);
        
          }
    }

    private function new_topic_gears($new_topic_name, $new_topic_post_data)
    {

        $userValues = $this->get_user_values(array("muted"));

        if(!isset($userValues["muted"]) || $userValues["muted"] !== "yes"){

        if (validate_post($new_topic_name, 1, 130)) {
            if (validate_post($new_topic_post_data, 1, 30000)) {

                $this->user_ratelimit('place', 60, true, false);

                $new_topic_name = $this->clean_text($new_topic_name);
                $new_topic_post_data = $this->clean_text($new_topic_post_data);
                $new_topic_and_post_data_timestamp = date("Y-m-d H:i:s");

                $this->db->exec('BEGIN TRANSACTION');

                $new_topic_statement = 'INSERT INTO "topics" ("topic_name", "member_creator_id", "posts_number", "topic_last_post_timestamp", "topic_last_post_username") VALUES (:topic_name, :member_creator_id, :posts_number, :topic_last_post_timestamp, :topic_last_post_username)';
                $params[':topic_name'] = $new_topic_name; $types[':topic_name'] = SQLITE3_TEXT;
                $params[':member_creator_id'] = $_SESSION['member_id']; $types[':member_creator_id'] = SQLITE3_INTEGER;
                $params[':posts_number'] = 0; $types[':posts_number'] = SQLITE3_INTEGER;
                $params[':topic_last_post_timestamp'] = $new_topic_and_post_data_timestamp; $types[':topic_last_post_timestamp'] = SQLITE3_TEXT;
                $params[':topic_last_post_username'] = $_SESSION['username'];
                $new_topic_statement = $this->execute_query($new_topic_statement, $params, $types, true);

                if ($new_topic_statement) {

                    $new_topic_id_statement = $this->db->lastInsertRowID();

                    if ($new_topic_id_statement) {

                        $new_post_statement = 'INSERT INTO "posts" ("topic_id", "member_id", "post_data", "post_timestamp_creation", "main_post") VALUES (:topic_id, :member_id, :post_data, :post_timestamp_creation, :main_post)';
                        $params[':topic_id'] = $new_topic_id_statement; $types[':topic_id'] = SQLITE3_INTEGER;
                        $params[':member_id'] = $_SESSION['member_id']; $types[':member_id'] = SQLITE3_INTEGER;
                        $params[':post_data'] = $new_topic_post_data; $types[':post_data'] = SQLITE3_TEXT;
                        $params[':post_timestamp_creation'] = $new_topic_and_post_data_timestamp; $types[':post_timestamp_creation'] = SQLITE3_TEXT;
                        $params[':main_post'] = "yes"; $types[':main_post'] = SQLITE3_TEXT;

                        $new_post_statement = $this->execute_query($new_post_statement, $params, $types, true);

                        if ($new_post_statement) {

                            $new_post_id_statement = $this->db->lastInsertRowID();

                            if ($new_post_id_statement) {

                                $update_topic_last_post_id_statement = 'UPDATE topics SET topic_last_post_id = :topic_last_post_id WHERE topic_id = :topic_id';
                                $params[':topic_last_post_id'] = $new_post_id_statement; $types[':topic_last_post_id'] = SQLITE3_INTEGER;
                                $params[':topic_id'] = $new_topic_id_statement; $types[':topic_id'] = SQLITE3_INTEGER;

                                $update_topic_last_post_id_statement = $this->execute_query($update_topic_last_post_id_statement, $params, $types, true);

                                if ($update_topic_last_post_id_statement) {

                                    $update_member_posts_count_statement = 'UPDATE members SET posts_count = posts_count + 1 WHERE member_id = :member_id';
                                    $params[':member_id'] = $_SESSION['member_id']; $types[':member_id'] = SQLITE3_INTEGER;

                                    $update_member_posts_count_statement = $this->execute_query($update_member_posts_count_statement, $params, $types, true);

                                    if ($update_member_posts_count_statement) {

                                        $this->db->exec('COMMIT');

                                        error_or_success_popup("success", "Your topic has been created!", "?location=answers&id=$new_topic_id_statement");

                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                error_or_success_popup("error", "The content of your topic content should be between 1 and 30,000 characters and in the correct format.", false);
            }
        } else {
            error_or_success_popup("error", "The title of your topic must be between 1 and 130 characters long and in the correct format.", false);
        }

    } else {
        error_or_success_popup("error", "Apparently you have been muted, you can no longer post topics. Please send a private message to moderation if you think this is a mistake.", false);
    }

    }
    
    public function new_post_on_topic($token, $topic_id, $targeted_post){
        if (isset($_POST["new_answer"]) && sha1(session_id()) === $token) {

            $new_post_on_topic = (string) filter_input(INPUT_POST, "new_post_on_topic");
    
            if (isset($targeted_post)) {
                $this->new_post_on_topic_gears($new_post_on_topic, $topic_id, $targeted_post);
            } else {
                $this->new_post_on_topic_gears($new_post_on_topic, $topic_id);
            }
        }
    }

    private function new_post_on_topic_gears($new_post_on_topic, $topic_id, $targeted_post = null)
    {

        $userValues = $this->get_user_values(array("muted"));

        if(!isset($userValues["muted"]) || $userValues["muted"] !== "yes"){

        if (validate_post($new_post_on_topic, 1, 10000)) {

            $get_topic_id_and_locked = 'SELECT topic_id, locked FROM topics WHERE topic_id = :topic_id';
            $params[':topic_id'] = $topic_id; $types[':topic_id'] = SQLITE3_INTEGER;
            $get_topic_id_and_locked = $this->execute_query($get_topic_id_and_locked, $params, $types, false)->fetchArray(SQLITE3_ASSOC);

            if(isset($get_topic_id_and_locked["topic_id"]) && (!isset($get_topic_id_and_locked["locked"]) || $get_topic_id_and_locked["locked"] !== "yes") || check_permissions("full_access")){

                $this->user_ratelimit('answers', 30, false, false);

                $new_post_on_topic = $this->clean_text($new_post_on_topic);
                $new_post_and_last_post_timestamp = date("Y-m-d H:i:s");

            $this->db->exec('BEGIN TRANSACTION');

            if (!is_null($targeted_post)) {

                $get_post_data_and_username_statement = 'SELECT posts.post_data, members.username 
                                                                   FROM posts 
                                                                   INNER JOIN members 
                                                                   ON posts.member_id = members.member_id
                                                                   WHERE posts.topic_id = :topic_id 
                                                                   AND posts.post_id = :targeted_post';

                $params[':topic_id'] = $topic_id; $types[':topic_id'] = SQLITE3_INTEGER;
                $params[':targeted_post'] = $targeted_post; $types[':targeted_post'] = SQLITE3_INTEGER;

                $get_post_data_and_username_statement = $this->execute_query($get_post_data_and_username_statement, $params, $types, false)->fetchArray(SQLITE3_ASSOC);

                if (isset($get_post_data_and_username_statement["username"])) {

                        $new_post_on_topic_statement = 'INSERT INTO "posts" ("topic_id", "member_id", "post_data", "post_timestamp_creation") VALUES (:topic_id, :member_id, :post_data, :post_timestamp_creation)';
                        $new_post_on_topic = "[quote=@" . $get_post_data_and_username_statement['username'] . "]" . $get_post_data_and_username_statement['post_data'] . "[/quote] " . $new_post_on_topic;
                        $params[':post_data'] = $new_post_on_topic; $types[':post_data'] = SQLITE3_TEXT;                

                } else {
                    error_or_success_popup("error", "The reply you want to quote does not exist.", false);
                }

            } else {
                $new_post_on_topic_statement = 'INSERT INTO "posts" ("topic_id", "member_id", "post_data", "post_timestamp_creation") VALUES (:topic_id, :member_id, :post_data, :post_timestamp_creation)';
                $params[':post_data'] = $new_post_on_topic; $types[':post_data'] = SQLITE3_TEXT;
            }

            $params[':topic_id'] = $topic_id; $types[':topic_id'] = SQLITE3_INTEGER;
            $params[':member_id'] = $_SESSION['member_id']; $types[':member_id'] = SQLITE3_INTEGER;
            $params[':post_timestamp_creation'] = $new_post_and_last_post_timestamp; $types[':post_timestamp_creation'] = SQLITE3_TEXT;

            $new_post_on_topic_statement = $this->execute_query($new_post_on_topic_statement, $params, $types, true);

            if ($new_post_on_topic_statement) {

                $new_post_id_statement = $this->db->lastInsertRowID();

                if ($new_post_id_statement) {

                    $update_topic_last_post_timestamp_statement = 'UPDATE topics SET topic_last_post_timestamp = :topic_last_post_timestamp, posts_number = posts_number + 1, topic_last_post_id = :topic_last_post_id, topic_last_post_username = :topic_last_post_username WHERE topic_id = :topic_id';
                    $params[':topic_last_post_timestamp'] = $new_post_and_last_post_timestamp; $types[':topic_last_post_timestamp'] = SQLITE3_TEXT;
                    $params[':topic_last_post_id'] = $new_post_id_statement; $types[':topic_last_post_id'] = SQLITE3_INTEGER;
                    $params[':topic_last_post_username'] = $_SESSION['username'];
                    $params[':topic_id'] = $topic_id; $types[':topic_id'] = SQLITE3_INTEGER;

                    $update_topic_last_post_timestamp_statement = $this->execute_query($update_topic_last_post_timestamp_statement, $params, $types, true);

                    if ($update_topic_last_post_timestamp_statement) {

                        $update_member_posts_count_statement = 'UPDATE members SET posts_count = posts_count + 1 WHERE member_id = :member_id';
                        $params[':member_id'] = $_SESSION['member_id']; $types[':member_id'] = SQLITE3_INTEGER;
                        $update_member_posts_count_statement = $this->execute_query($update_member_posts_count_statement, $params, $types, true);

                        if ($update_member_posts_count_statement) {

                            $this->db->exec('COMMIT');

                            error_or_success_popup("success", "Your reply has been sent!", false);

                        }

                    }
                }
            }
         }
        } else {
            error_or_success_popup("error", "The content of your reply must be between 1 and 10,000 characters long and in the correct format.", false);
        }
    } else {
        error_or_success_popup("error", "Apparently you have been muted, you can no longer post. Please send a private message to moderation if you think this is a mistake.", false);
    }
    }

    public function new_message($token, $user_id){
        if (isset($_POST["new_message"]) && sha1(session_id()) === $token) {

            $new_message_data = (string) filter_input(INPUT_POST, "new_message_data");
        
            $this->new_message_gears($new_message_data, $user_id);
        
        }
    }

    public function new_message_gears($new_message_data, $user_id)
    {
        if (validate_post($new_message_data, 1, 400)) {
            if (ctype_digit($user_id)) {

                $new_message_data = $this->clean_text($new_message_data);

                $this->user_ratelimit('conversation', 30, false, false);

                $check_user_id_exists_statement = 'SELECT member_id, banned FROM members WHERE member_id = :user_id';
                $params[':user_id'] = $user_id; $types[':user_id'] = SQLITE3_INTEGER;
                $check_user_id_exists_statement = $this->execute_query($check_user_id_exists_statement, $params, $types, false)->fetchArray(SQLITE3_ASSOC);
                if ($check_user_id_exists_statement["member_id"] == $user_id && $check_user_id_exists_statement["member_id"] !== $_SESSION['member_id'] && (!isset($check_user_id_exists_statement["banned"]) || $check_user_id_exists_statement["banned"] !== "yes")) {

                    $this->db->exec('BEGIN TRANSACTION');

                    $new_message_statement = 'INSERT INTO "messages" ("sender_id", "recipient_id", "message_data", "message_timestamp") VALUES (:sender_id, :recipient_id, :message_data, :message_timestamp)';
                    $params[':sender_id'] = $_SESSION['member_id']; $types[':sender_id'] = SQLITE3_INTEGER;
                    $params[':recipient_id'] = $user_id; $types[':recipient_id'] = SQLITE3_INTEGER;
                    $params[':message_data'] = $new_message_data; $types[':message_data'] = SQLITE3_TEXT;
                    $params[':message_timestamp'] = date("Y-m-d H:i:s"); $types[':message_timestamp'] = SQLITE3_TEXT;
                    $new_message_statement = $this->execute_query($new_message_statement, $params, $types, true);
                    if ($new_message_statement) {
                        $new_notification_statement = 'UPDATE members SET notifications = "yes" WHERE member_id = :member_id';
                        $params[':member_id'] = $user_id; $types[':member_id'] = SQLITE3_INTEGER;
                        $new_notification_statement = $this->execute_query($new_notification_statement, $params, $types, true);
                        if($new_notification_statement){

                            $this->db->exec('COMMIT');

                            error_or_success_popup("success", "Your message has been sent!", false);
                        }
                    }
                }

            }
        } else {
            error_or_success_popup("error", "The content of your message must be between 1 and 400 characters long and in the correct format.", false);
        }

    }

    public function read_message_notification($user_id){
        if (ctype_digit($user_id)) {

            $check_user_id_exists_statement = 'SELECT member_id FROM members WHERE member_id = :user_id';
            $params[':user_id'] = $user_id; $types[':user_id'] = SQLITE3_INTEGER;
            $check_user_id_exists_statement = $this->execute_query($check_user_id_exists_statement, $params, $types, false)->fetchArray(SQLITE3_ASSOC);
            if ($check_user_id_exists_statement["member_id"] == $user_id && $check_user_id_exists_statement["member_id"] !== $_SESSION['member_id']) {

                $read_message_notification_statement = 'UPDATE messages SET message_read = "yes" WHERE recipient_id = :recipient_id
                                                                                         AND sender_id = :sender_id
                                                                                         AND message_timestamp = (
                                                                                            SELECT MAX(message_timestamp)
                                                                                            FROM messages
                                                                                            WHERE recipient_id = :recipient_id
                                                                                            AND sender_id = :sender_id)';

                $params[':recipient_id'] = $_SESSION['member_id']; $types[':recipient_id'] = SQLITE3_INTEGER;
                $params[':sender_id'] = $user_id; $types[':sender_id'] = SQLITE3_INTEGER;
                $read_message_notification_statement = $this->execute_query($read_message_notification_statement, $params, $types, false);

            }

        }

    }

    public function read_notifications(){
        if(isset($_SESSION['notifications']) && $_SESSION['notifications'] === "yes"){
            $read_notifications_statement = "UPDATE members SET notifications = NULL WHERE member_id = :member_id";
            $params[':member_id'] = $_SESSION['member_id']; $types[':member_id'] = SQLITE3_INTEGER;
            $read_notifications_statement = $this->execute_query($read_notifications_statement, $params, $types, false);

            if($read_notifications_statement){

              unset($_SESSION['notifications']);
              
            }
        }
    }

}

class control extends user
{

    public function __construct($db) {
        parent::__construct($db);
        if (!check_permissions("control_panel")) {
            die();
        }
    }

    public function modify_site_data($token){

        if (isset($_POST["modify_site_data"]) && check_permissions("modify_site_data") && sha1(session_id()) === $token) {

            $new_site_name = (string) filter_input(INPUT_POST, "site_name");
            $new_site_description = (string) filter_input(INPUT_POST, "site_description");
            $new_site_keywords = (string) filter_input(INPUT_POST, "site_keywords");
            $new_site_footer = (string) filter_input(INPUT_POST, "site_footer");
        
            $new_nb_topics = (string) filter_input(INPUT_POST, "nb_topics");
            $new_nb_posts = (string) filter_input(INPUT_POST, "nb_posts");
            $new_nb_user_posts = (string) filter_input(INPUT_POST, "nb_user_posts");
            $new_nb_conversations = (string) filter_input(INPUT_POST, "nb_conversations");
            $new_nb_conversations_messages = (string) filter_input(INPUT_POST, "nb_conversations_messages");
        
            $this->modify_site_data_gears($new_site_name, $new_site_description, $new_site_keywords, $new_site_footer, $new_nb_topics, $new_nb_posts, $new_nb_user_posts, $new_nb_conversations, $new_nb_conversations_messages);
        }

    }

    private function modify_site_data_gears($new_site_name, $new_site_description, $new_site_keywords, $new_site_footer, $new_nb_topics, $new_nb_posts, $new_nb_user_posts, $new_nb_conversations, $new_nb_conversations_messages)
    {
        $set_clause = array();
        $params = array();

        if (validate_post($new_site_name, 1, 20)) {
            $new_site_name = $this->clean_text($new_site_name);
            $set_clause[] = 'name = :new_site_name';
            $params[":new_site_name"] = $new_site_name;
        }

        if (validate_post($new_site_description, 1, 200)) {
            $new_site_description = $this->clean_text($new_site_description);
            $set_clause[] = 'description = :new_site_description';
            $params[":new_site_description"] = $new_site_description;
        }

        if (validate_post($new_site_keywords, 1, 200)) {
            $new_site_keywords = $this->clean_text($new_site_keywords);
            $set_clause[] = 'keywords = :new_site_keywords';
            $params[":new_site_keywords"] = $new_site_keywords;
        }

        if (validate_post($new_site_footer, 1, 120)) {
            $new_site_footer = $this->clean_text($new_site_footer);
            $set_clause[] = 'footer = :new_site_footer';
            $params[":new_site_footer"] = $new_site_footer;
        }

        if (ctype_digit($new_nb_topics) && $new_nb_topics > 0 && $new_nb_topics <= 99) {
            $new_nb_topics = $this->clean_text($new_nb_topics);
            $set_clause[] = 'nb_topics = :new_nb_topics';
            $params[":new_nb_topics"] = $new_nb_topics;
        }

        if (ctype_digit($new_nb_posts) && $new_nb_posts > 0 && $new_nb_posts <= 99) {
            $new_nb_posts = $this->clean_text($new_nb_posts);
            $set_clause[] = 'nb_posts = :new_nb_posts';
            $params[":new_nb_posts"] = $new_nb_posts;
        }

        if (ctype_digit($new_nb_user_posts) && $new_nb_user_posts > 0 && $new_nb_user_posts <= 99) {
            $new_nb_user_posts = $this->clean_text($new_nb_user_posts);
            $set_clause[] = 'nb_user_posts = :new_nb_user_posts';
            $params[":new_nb_user_posts"] = $new_nb_user_posts;
        }

        if (ctype_digit($new_nb_conversations) && $new_nb_conversations > 0 && $new_nb_conversations <= 99) {
            $new_nb_conversations = $this->clean_text($new_nb_conversations);
            $set_clause[] = 'nb_conversations = :new_nb_conversations';
            $params[":new_nb_conversations"] = $new_nb_conversations;
        }

        if (ctype_digit($new_nb_conversations_messages) && $new_nb_conversations_messages > 0 && $new_nb_conversations_messages <= 99) {
            $new_nb_conversations_messages = $this->clean_text($new_nb_conversations_messages);
            $set_clause[] = 'nb_conversations_messages = :new_nb_conversations_messages';
            $params[":new_nb_conversations_messages"] = $new_nb_conversations_messages;
        }

        if (empty($set_clause)) {
            error_or_success_popup("error", "Please change at least one value in the site parameters.", false);
        }

        $set_clause_str = implode(", ", $set_clause);

        $modify_site_data_statement = "UPDATE site SET $set_clause_str";

        $modify_site_data_statement = $this->execute_query($modify_site_data_statement, $params);

        if ($modify_site_data_statement) {
            error_or_success_popup("success", "The site parameters have been successfully edited.", false);
        }
    }

    public function show_logs(){
        if(check_permissions("logs")){
            $this->show_logs_gears();
        }
    }
    private function show_logs_gears()
    {

        $show_logs_statement = 'SELECT * FROM logs ORDER BY log_timestamp DESC';
        $params = $types = [];
        $show_logs_statement = $this->execute_query($show_logs_statement, $params, $types, false);

        if ($show_logs_statement) {
            while ($logs_array = $show_logs_statement->fetchArray(SQLITE3_ASSOC)) {
                if ($logs_array['log_type'] == "fatal") {
                    echo '<div class="log-message log-fatal">[' . encode_html(convert_date($logs_array['log_timestamp'])) . '] ' . encode_html($logs_array['log_type']) . ': ' . encode_html($logs_array['log_data']) . '</div>';
                }
                if ($logs_array['log_type'] == "error") {
                    echo '<div class="log-message log-error">[' . encode_html(convert_date($logs_array['log_timestamp'])) . '] ' . encode_html($logs_array['log_type']) . ': ' . encode_html($logs_array['log_data']) . '</div>';
                }
                if ($logs_array['log_type'] == "success") {
                    echo '<div class="log-message log-success">[' . encode_html(convert_date($logs_array['log_timestamp'])) . '] ' . encode_html($logs_array['log_type']) . ': ' . encode_html($logs_array['log_data']) . '</div>';
                }
            }
        }

    }

    public function logs_methods($token){

        if (isset($_POST["clear_logs"]) && check_permissions("logs") && sha1(session_id()) === $token) {

            $this->clear_logs();
        
        }

        /*
        if (isset($_POST["save_logs"]) && check_permissions("logs") && sha1(session_id()) === $token) {
        
            $this->save_logs();
        
        } 
        */

    }
    private function clear_logs()
    {
        $clear_logs_statement = 'DELETE FROM logs';
        $params = $types = [];
        $clear_logs_statement = $this->execute_query($clear_logs_statement, $params, $types, false);
        if ($clear_logs_statement) {
            error_or_success_popup("success", "Site logs cleaned successfully.", false);
        }
    }

    
    /*
    private function save_logs()
    {
        $save_logs_statement = 'SELECT * FROM logs';
        $params = $types = [];
        $save_logs_statement = $this->execute_query($save_logs_statement, $params, $types, false);
        $saved_logs = fopen('saved_logs.csv', 'w');
        $column_names = array('log_id', 'log_type', 'log_data', 'log_timestamp');
        fputcsv($saved_logs, $column_names);
        while ($row = $save_logs_statement->fetchArray(SQLITE3_ASSOC)) {
            fputcsv($saved_logs, $row);
        }
        fclose($saved_logs);

        if ($save_logs_statement && file_exists('saved_logs.csv') && filesize('saved_logs.csv') > 0) {
            error_or_success_popup("success", "Successful log backup.", false);
        }
    }
    */

    public function show_users($token){
        if (isset($_POST["show_users"]) && check_permissions("show_users") && sha1(session_id()) === $token) {
            $this->show_users_gears();
        }
    }
    private function show_users_gears()
    {
        $users_list_statement = 'SELECT username, member_timestamp_creation FROM members ORDER BY member_id ASC';
        $params = $types = [];
        $users_list_statement = $this->execute_query($users_list_statement, $params, $types, false);
        if ($users_list_statement) {
            $users_list_result = 0;
            while ($users_list_array = $users_list_statement->fetchArray(SQLITE3_ASSOC)) {
                echo "<option value=\"" . encode_html($users_list_array['username']) . "\">" . encode_html($users_list_array['username']) . " | " . encode_html(convert_date($users_list_array['member_timestamp_creation'])) . "</option>";
                $users_list_result++;
            }
            echo "<option value='0' selected>" . "Number of users :" . $users_list_result . "</option>";
            
        }
    }

    public function delete_post_by_id($token){

        if (isset($_POST["delete_post_by_id"]) && check_permissions("delete_post_by_id") && sha1(session_id()) === $token) {

            $post_id = (string) filter_input(INPUT_POST, "post_id");
        
            $this->delete_post_by_id_gears($post_id);
        }

    }


    private function delete_post_by_id_gears($post_id)
    {
        if (ctype_digit($post_id)) {

            $check_delete_post_by_id_statement = 'SELECT topic_id, main_post FROM posts WHERE post_id = :post_id';
            $params[':post_id'] = $post_id; $types[':post_id'] = SQLITE3_INTEGER;
            $check_delete_post_by_id_statement = $this->execute_query($check_delete_post_by_id_statement, $params, $types, false)->fetchArray(SQLITE3_ASSOC);

            if ($check_delete_post_by_id_statement) {

                if ($check_delete_post_by_id_statement["main_post"] === "yes") {

                    $this->delete_topic_by_id_gears($check_delete_post_by_id_statement["topic_id"]);

                } else {

                    $delete_post_by_id_statement = 'DELETE FROM posts WHERE post_id = :post_id';
                    $params[':post_id'] = $post_id; $types[':post_id'] = SQLITE3_INTEGER;
                    $delete_post_by_id_statement = $this->execute_query($delete_post_by_id_statement, $params, $types, false);

                    if ($delete_post_by_id_statement) {

                        error_or_success_popup("success", "The post has been deleted.", false);
                    }
                    
                }
            }
            else{
                error_or_success_popup("error", "No post exists under this id.", false);
            }
        } else {
            error_or_success_popup("error", "The id for deleting the post must be between 0 and + infinity and must be in numeric format.", false);
        }
    }

    public function delete_topic_by_id($token){

        if (isset($_POST["delete_topic_by_id"]) && check_permissions("delete_topic_by_id") && sha1(session_id()) === $token) {

            $topic_id = (string) filter_input(INPUT_POST, "topic_id");
        
            $this->delete_topic_by_id_gears($topic_id);
        
        }

    }

    private function delete_topic_by_id_gears($topic_id)
    {
        if (ctype_digit($topic_id)) {

            $check_delete_topic_by_id_statement = 'SELECT COUNT(*) FROM topics WHERE topic_id = :topic_id';
            $params[':topic_id'] = $topic_id; $types[':topic_id'] = SQLITE3_INTEGER;
            $check_delete_topic_by_id_statement = $this->execute_query($check_delete_topic_by_id_statement, $params, $types, false)->fetchArray(SQLITE3_NUM);

            if ($check_delete_topic_by_id_statement && $check_delete_topic_by_id_statement[0] > 0) {

                $this->db->exec('BEGIN TRANSACTION');

                $delete_topic_by_id_statement = 'DELETE FROM topics WHERE topic_id = :topic_id';
                $params[':topic_id'] = $topic_id; $types[':topic_id'] = SQLITE3_INTEGER;
                $delete_topic_by_id_statement = $this->execute_query($delete_topic_by_id_statement, $params, $types, true);

                if ($delete_topic_by_id_statement) {
                    $delete_posts_from_topic_by_id_statement = 'DELETE FROM posts WHERE topic_id = :topic_id';
                    $params[':topic_id'] = $topic_id; $types[':topic_id'] = SQLITE3_INTEGER;
                    $delete_posts_from_topic_by_id_statement = $this->execute_query($delete_posts_from_topic_by_id_statement, $params, $types, true);
                    if ($delete_posts_from_topic_by_id_statement) {

                        $this->db->exec('COMMIT');

                        error_or_success_popup("success", "The topic has been deleted.", "?location=index");

                    }
                }
            } else {
                error_or_success_popup("error", "The topic to be deleted does not exist.", false);
            }
        } else {
            error_or_success_popup("error", "The id for topic deletion must be between 0 and + infinity and must be in numeric format.", false);
        }
    }

    public function pin_or_unpin_topic_by_id($token){

        if (isset($_POST["pin_or_unpin_topic_by_id"]) && check_permissions("pin_or_unpin_topic_by_id") && sha1(session_id()) === $token) {

            $topic_id = (string) filter_input(INPUT_POST, "topic_id");
        
            $this->pin_or_unpin_topic_by_id_gears($topic_id);
        }

    }

    private function pin_or_unpin_topic_by_id_gears($topic_id)
    {
        if (ctype_digit($topic_id)) {

            $check_pin_or_unpin_topic_by_id_statement = 'SELECT pinned FROM topics WHERE topic_id = :topic_id';
            $params[':topic_id'] = $topic_id; $types[':topic_id'] = SQLITE3_INTEGER;
            $check_pin_or_unpin_topic_by_id_statement = $this->execute_query($check_pin_or_unpin_topic_by_id_statement, $params, $types, false)->fetchArray(SQLITE3_ASSOC);

            if ($check_pin_or_unpin_topic_by_id_statement) {

                if ($check_pin_or_unpin_topic_by_id_statement["pinned"] === "yes") {
                    $pin_or_unpin_topic_by_id_statement = 'UPDATE topics SET pinned = NULL WHERE topic_id = :topic_id';
                } else if ($check_pin_or_unpin_topic_by_id_statement["pinned"] !== "yes") {
                    $pin_or_unpin_topic_by_id_statement = 'UPDATE topics SET pinned = "yes" WHERE topic_id = :topic_id';
                }

                $params[':topic_id'] = $topic_id; $types[':topic_id'] = SQLITE3_INTEGER;
                $pin_or_unpin_topic_by_id_statement = $this->execute_query($pin_or_unpin_topic_by_id_statement, $params, $types, false);

                if ($pin_or_unpin_topic_by_id_statement) {
                    error_or_success_popup("success", "Topic successfully pinned/unpinned.", false);
                }

            } else {
                error_or_success_popup("error", "No topic exists under this id.", false);
            }
        } else {
            error_or_success_popup("error", "The id for pinning the topic must be between 0 and + infinity and must be in numeric format.", false);
        }
    }

    public function lock_or_unlock_topic_by_id($token){

        if (isset($_POST["lock_or_unlock_topic_by_id"]) && check_permissions("lock_or_unlock_topic_by_id") && sha1(session_id()) === $token) {

            $topic_id = (string) filter_input(INPUT_POST, "topic_id");
        
            $this->lock_or_unlock_topic_by_id_gears($topic_id);
        
        }

    }
    private function lock_or_unlock_topic_by_id_gears($topic_id)
    {
        if (ctype_digit($topic_id)) {

            $check_lock_or_unlock_topic_by_id_statement = 'SELECT locked FROM topics WHERE topic_id = :topic_id';
            $params[':topic_id'] = $topic_id; $types[':topic_id'] = SQLITE3_INTEGER;
            $check_lock_or_unlock_topic_by_id_statement = $this->execute_query($check_lock_or_unlock_topic_by_id_statement, $params, $types, false)->fetchArray(SQLITE3_ASSOC);

            if ($check_lock_or_unlock_topic_by_id_statement) {

                if ($check_lock_or_unlock_topic_by_id_statement["locked"] === "yes") {
                    $lock_or_unlock_topic_by_id_statement = 'UPDATE topics SET locked = NULL WHERE topic_id = :topic_id';
                } else if ($check_lock_or_unlock_topic_by_id_statement["locked"] !== "yes") {
                    $lock_or_unlock_topic_by_id_statement = 'UPDATE topics SET locked = "yes" WHERE topic_id = :topic_id';
                }

                $params[':topic_id'] = $topic_id; $types[':topic_id'] = SQLITE3_INTEGER;
                $lock_or_unlock_topic_by_id_statement = $this->execute_query($lock_or_unlock_topic_by_id_statement, $params, $types, false);

                if ($lock_or_unlock_topic_by_id_statement) {
                    error_or_success_popup("success", "Topic successfully locked/unlocked.", false);
                }

            } else {
                error_or_success_popup("error", "No topic exists under this id.", false);
            }
        } else {
            error_or_success_popup("error", "The id for locking the topic must be between 0 and + infinity and must be in numeric format.", false);
        }
    }

    public function ban_or_unban_user_by_username($token){

        if (isset($_POST["ban_or_unban_user_by_username"]) && check_permissions("ban_or_unban_user_by_username") && sha1(session_id()) === $token) {

            $username = (string) filter_input(INPUT_POST, "username");
        
            $this->ban_or_unban_user_by_username_gears($username);
        
        }

    }

    private function ban_or_unban_user_by_username_gears($username)
    {

        if (validate_post($username, 1, 20)) {

            $check_ban_or_unban_user_by_username_statement = 'SELECT role, banned FROM members WHERE LOWER(username) = :username';
            $params[':username'] = strtolower($username); $types[':username'] = SQLITE3_TEXT;
            $check_ban_or_unban_user_by_username_statement = $this->execute_query($check_ban_or_unban_user_by_username_statement, $params, $types, false)->fetchArray(SQLITE3_ASSOC);

            if ($check_ban_or_unban_user_by_username_statement) {

                if (!in_array($check_ban_or_unban_user_by_username_statement["role"], array('super-admin'))) {

                    if ($check_ban_or_unban_user_by_username_statement["banned"] !== "yes") {
                        $ban_or_unban_user_by_username_statement = 'UPDATE members SET banned = "yes" WHERE LOWER(username) = :username';
                    } else if ($check_ban_or_unban_user_by_username_statement["banned"] === "yes") {
                        $ban_or_unban_user_by_username_statement = 'UPDATE members SET banned = NULL WHERE LOWER(username) = :username';
                    }

                    $params[':username'] = strtolower($username); $types[':username'] = SQLITE3_TEXT;
                    $ban_or_unban_user_by_username_statement = $this->execute_query($ban_or_unban_user_by_username_statement, $params, $types, false);

                    if ($ban_or_unban_user_by_username_statement) {
                        error_or_success_popup("success", "Update of ban status successfully completed.", false);
                    }
                } else {
                    $this->grant_privileges_by_username_gears($_SESSION['username'], "member");
                    $this->disconnect();
                }
            } else {
                error_or_success_popup("error", "Error retrieving value: banned, account does not exist?", false);
            }
        } else {
            error_or_success_popup("error", "Please enter a username between 1 and 20 characters long, in the correct format.", false);
        }
    }

    public function mute_or_unmute_user_by_username($token){

        if (isset($_POST["mute_or_unmute_user_by_username"]) && check_permissions("mute_or_unmute_user_by_username") && sha1(session_id()) === $token) {

            $username = (string) filter_input(INPUT_POST, "username");
        
            $this->mute_or_unmute_user_by_username_gears($username);
        
        }

    }

    private function mute_or_unmute_user_by_username_gears($username)
    {

        if (validate_post($username, 1, 20)) {

            $check_mute_or_unmute_user_by_username_statement = 'SELECT role, muted FROM members WHERE LOWER(username) = :username';
            $params[':username'] = strtolower($username); $types[':username'] = SQLITE3_TEXT;
            $check_mute_or_unmute_user_by_username_statement = $this->execute_query($check_mute_or_unmute_user_by_username_statement, $params, $types, false)->fetchArray(SQLITE3_ASSOC);

            if ($check_mute_or_unmute_user_by_username_statement) {

                if (!in_array($check_mute_or_unmute_user_by_username_statement["role"], array('super-admin', 'admin'))) {

                    if ($check_mute_or_unmute_user_by_username_statement["muted"] !== "yes") {
                        $mute_or_unmute_user_by_username_statement = 'UPDATE members SET muted = "yes" WHERE LOWER(username) = :username';
                    } else if ($check_mute_or_unmute_user_by_username_statement["muted"] === "yes") {
                        $mute_or_unmute_user_by_username_statement = 'UPDATE members SET muted = NULL WHERE LOWER(username) = :username';
                    }

                    $params[':username'] = strtolower($username); $types[':username'] = SQLITE3_TEXT;
                    $mute_or_unmute_user_by_username_statement = $this->execute_query($mute_or_unmute_user_by_username_statement, $params, $types, false);

                    if ($mute_or_unmute_user_by_username_statement) {
                        error_or_success_popup("success", "Mute status update successfully completed.", false);
                    }
                } 
            } else {
                error_or_success_popup("error", "Error retrieving value: muted, account does not exist?", false);
            }
        } else {
            error_or_success_popup("error", "Please enter a username between 1 and 20 characters long, in the correct format.", false);
        }
    }

    public function delete_all_user_posts_by_username($token){

        if (isset($_POST["delete_all_user_posts_by_username"]) && check_permissions("delete_all_user_posts_by_username") && sha1(session_id()) === $token) {

            $username = (string) filter_input(INPUT_POST, "username");
        
            $this->delete_all_user_posts_by_username_gears($username);
        
        }

    }

    private function delete_all_user_posts_by_username_gears($username)
    {

        if (validate_post($username, 1, 20)) {

            if ($this->check_user_exists($username)) {

                $check_delete_all_user_posts_by_username_statement = 'SELECT COUNT(*) FROM posts 
                                                                      JOIN members ON posts.member_id = members.member_id 
                                                                      WHERE LOWER(members.username) = :username';
                $params[':username'] = strtolower($username); $types[':username'] = SQLITE3_TEXT;
                $check_delete_all_user_posts_by_username_statement = $this->execute_query($check_delete_all_user_posts_by_username_statement, $params, $types, false)->fetchArray(SQLITE3_NUM);

                if ($check_delete_all_user_posts_by_username_statement && $check_delete_all_user_posts_by_username_statement[0] > 0) {

                    $this->db->exec('BEGIN TRANSACTION');

                    
                    $delete_all_user_posts_and_answers_by_username_statement = 'DELETE FROM posts
                    WHERE member_id IN (
                        SELECT member_id
                        FROM members
                        WHERE LOWER(username) = :username
                    ) OR topic_id IN (
                        SELECT topic_id
                        FROM topics
                        WHERE member_creator_id IN (
                            SELECT member_id
                            FROM members
                            WHERE LOWER(username) = :username
                        )
                    )';

                    $delete_all_user_posts_and_answers_by_username_statement = $this->execute_query($delete_all_user_posts_and_answers_by_username_statement, $params, $types, false);

                    if ($delete_all_user_posts_and_answers_by_username_statement) {
                        

                    $delete_all_user_topics_by_username_statement = 'DELETE FROM topics
                                                                     WHERE member_creator_id IN (
                                                                        SELECT member_id
                                                                        FROM members
                                                                        WHERE LOWER(username) = :username)';

                    $delete_all_user_topics_by_username_statement = $this->execute_query($delete_all_user_topics_by_username_statement, $params, $types, false);

                        if ($delete_all_user_topics_by_username_statement) {

                            $this->db->exec('COMMIT');

                            error_or_success_popup("success", "All the user's posts have been deleted successfully.", false);

                        }

                    }

                } else {
                    error_or_success_popup("error", "Error retrieving user posts, no posts exist?", false);
                }
            } else {
                error_or_success_popup("error", "No user exists under this username.", false);
            }
        } else {
            error_or_success_popup("error", "The username must be between 1 and 20 characters long and in the correct format.", false);
        }
    }
    

    public function refresh_database($token){
        if (isset($_POST["refresh_database"]) && check_permissions("refresh_database") && sha1(session_id()) === $token) {
        
            $this->refresh_database_gears();
        
        }
    }

    private function refresh_database_gears(){

        $this->db->exec('BEGIN TRANSACTION');

        $delete_orphaned_topics_statement = 'DELETE FROM topics WHERE topic_id NOT IN (SELECT DISTINCT topic_id FROM posts)';
        $params = $types = [];
        $delete_orphaned_topics_statement = $this->execute_query($delete_orphaned_topics_statement, $params, $types, true);

        if($delete_orphaned_topics_statement){

            $delete_orphaned_posts_statement = 'DELETE FROM posts WHERE topic_id NOT IN (SELECT DISTINCT topic_id FROM topics)';
            $params = $types = [];
            $delete_orphaned_posts_statement = $this->execute_query($delete_orphaned_posts_statement, $params, $types, true);

            if($delete_orphaned_posts_statement){

                $refresh_topics_statement = 'UPDATE topics SET posts_number = (SELECT COUNT(*) - 1 FROM posts WHERE posts.topic_id = topics.topic_id),
                                                    topic_last_post_timestamp = (SELECT MAX(post_timestamp_creation) FROM posts WHERE posts.topic_id = topics.topic_id),
                                                    topic_last_post_id = (SELECT post_id FROM posts WHERE posts.topic_id = topics.topic_id ORDER BY post_id DESC LIMIT 1),
                                                    topic_last_post_username = (SELECT username FROM members WHERE members.member_id = (SELECT member_id FROM posts WHERE posts.topic_id = topics.topic_id ORDER BY post_id DESC LIMIT 1))';
                $params = $types = [];
                $refresh_topics_statement = $this->execute_query($refresh_topics_statement, $params, $types, true);

                if($refresh_topics_statement){

                    $refresh_members_statement = 'UPDATE members SET posts_count = (SELECT COUNT(*) FROM posts WHERE posts.member_id = members.member_id)';
                    $params = $types = [];
                    $refresh_members_statement = $this->execute_query($refresh_members_statement, $params, $types, true);

                    if($refresh_members_statement){

                        $update_zero_posts_number_statement = 'UPDATE members SET posts_count = NULL WHERE posts_count = 0';
                        $params = $types = [];
                        $update_zero_posts_number_statement = $this->execute_query($update_zero_posts_number_statement, $params, $types, true);

                        if($update_zero_posts_number_statement){
                            $this->db->exec('COMMIT');
                            error_or_success_popup("success", "Updated database.", false);
                        }

                    }

                }
                
            }
        }

    }

    public function delete_all_private_messages($token){

        if (isset($_POST["delete_all_private_messages"]) && check_permissions("delete_all_private_messages") && sha1(session_id()) === $token) {
        
            $this->delete_all_private_messages_gears();
        
        }

    }

    private function delete_all_private_messages_gears(){

        $check_private_messages_statement = 'SELECT COUNT(*) FROM messages';
        $params = $types = [];
        $check_private_messages_statement = $this->execute_query($check_private_messages_statement, $params, $types, false)->fetchArray(SQLITE3_NUM);

        if ($check_private_messages_statement && $check_private_messages_statement[0] > 0) {

            $delete_private_messages_statement = 'DELETE FROM messages';
            $params = $types = [];
            $delete_private_messages_statement = $this->execute_query($delete_private_messages_statement, $params, $types, false);

            if($delete_private_messages_statement){
                error_or_success_popup("success", "All private messages have been deleted.", false);
            }
        } else{
            error_or_success_popup("error", "There are no private messages to delete.", false);
        }

    }




    public function grant_privileges_by_username($token){

        if (isset($_POST["grant_privileges_by_username"]) && check_permissions("grant_privileges_by_username") && sha1(session_id()) === $token) {
            $username = (string) filter_input(INPUT_POST, "username");
            $selected_role = (string) filter_input(INPUT_POST, "role_select");
        
            $this->grant_privileges_by_username_gears($username, $selected_role);
        }

    }
    private function grant_privileges_by_username_gears($username, $selected_role)
    {
        if (validate_post($username, 1, 20)) {
            if ($this->check_user_exists($username)) {
                if (in_array($selected_role, array("super-admin", "admin", "moderator", "member"))) {
                    $grant_privileges_by_username_statement = 'UPDATE members SET role = :role WHERE LOWER(username) = :username';
                    $params[':role'] = $selected_role;
                    $types[':role'] = SQLITE3_TEXT;
                    $params[':username'] = strtolower($username);
                    $types[':username'] = SQLITE3_TEXT;
                    $grant_privileges_by_username_statement = $this->execute_query($grant_privileges_by_username_statement, $params, $types, false);

                    if ($grant_privileges_by_username_statement) {
                        error_or_success_popup("success", "The role has been successfully added to the user.", false);
                    }
                } else {
                    error_or_success_popup("error", "Please select a valid user role.", false);
                }
            } else {
                error_or_success_popup("error", "No user exists under this username.", false);
            }
        } else {
            error_or_success_popup("error", "The username must be between 1 and 20 characters long and must be in the correct format.", false);
        }
    }

    public function enable_or_disable_registration_case($token){

        if (isset($_POST["enable_or_disable_registration_case"]) && check_permissions("enable_or_disable_registration_case") && sha1(session_id()) === $token) {

            $registration_case = (string) filter_input(INPUT_POST, "registration_case");
        
            $this->enable_or_disable_registration_case_gears($registration_case);
        }

    }
    private function enable_or_disable_registration_case_gears($registration_case)
    {
                
                if (in_array($registration_case, array("enabled", "disabled"))) {
                    $enable_or_disable_registration_case_statement = 'UPDATE site SET registration_case = :registration_case';
                    $params[':registration_case'] = $registration_case; $types[':registration_case'] = SQLITE3_TEXT;
                    $enable_or_disable_registration_case_statement = $this->execute_query($enable_or_disable_registration_case_statement, $params, $types, false);

                    if ($enable_or_disable_registration_case_statement) {
                        error_or_success_popup("success", "The registration case has been changed.", false);
                    }
                } else {
                    error_or_success_popup("error", "Please select a valid registration case.", false);
                }
    }

    public function blacklist_therm($token){
        if (isset($_POST["blacklist_therm"]) && check_permissions("blacklisting") && sha1(session_id()) === $token) {
            $string_to_blacklist = (string) filter_input(INPUT_POST, "string_to_blacklist");
        
            $this->blacklist_therm_gears($string_to_blacklist);
        
        }
    }

    private function blacklist_therm_gears($string_to_blacklist)
    {
        if (validate_post($string_to_blacklist, 1, 200)) {
            $string_to_blacklist = $this->clean_text($string_to_blacklist);
            $blacklist_check_statement = 'SELECT blacklist FROM blacklist WHERE blacklist = :string_to_blacklist';
            $params[':string_to_blacklist'] = $string_to_blacklist;
            $types[':string_to_blacklist'] = SQLITE3_TEXT;
            $blacklist_check_statement = $this->execute_query($blacklist_check_statement, $params, $types, false)->fetchArray(SQLITE3_ASSOC);

            if (!$blacklist_check_statement["blacklist"]) {
                $blacklist_statement = 'INSERT INTO "blacklist" ("blacklist") VALUES (:string_to_blacklist)';
                $params[':string_to_blacklist'] = $string_to_blacklist;
                $types[':string_to_blacklist'] = SQLITE3_TEXT;
                $blacklist_statement = $this->execute_query($blacklist_statement, $params, $types, false);
                if ($blacklist_statement) {
                    error_or_success_popup("success", "The term has been successfully added to the blacklist.", false);
                }
            } else {
                error_or_success_popup("error", "The term to be added to the blacklist is already in the blacklist.", false);
            }
        } else {
            error_or_success_popup("error", "The term to be added to the blacklist must be between 1 and 200 characters long and in the correct format.", false);
        }
    }

    public function unblacklist_therm($token){

        if (isset($_POST["unblacklist_therm"]) && check_permissions("blacklisting") && sha1(session_id()) === $token) {
            $string_to_unblacklist = (string) filter_input(INPUT_POST, "string_to_unblacklist");
        
            $this->unblacklist_therm_gears($string_to_unblacklist);
        
        }

    }
    private function unblacklist_therm_gears($string_to_unblacklist)
    {
        if (validate_post($string_to_unblacklist, 1, 200)) {
            $unblacklist_check_statement = 'SELECT blacklist FROM blacklist WHERE blacklist = :string_to_unblacklist';
            $params[':string_to_unblacklist'] = $string_to_unblacklist;
            $types[':string_to_unblacklist'] = SQLITE3_TEXT;
            $unblacklist_check_statement = $this->execute_query($unblacklist_check_statement, $params, $types, false)->fetchArray(SQLITE3_ASSOC);

            if ($unblacklist_check_statement["blacklist"]) {
                $unblacklist_statement = 'DELETE FROM blacklist WHERE blacklist = :string_to_unblacklist';
                $params[':string_to_unblacklist'] = $string_to_unblacklist;
                $types[':string_to_unblacklist'] = SQLITE3_TEXT;
                $unblacklist_statement = $this->execute_query($unblacklist_statement, $params, $types, false);
                if ($unblacklist_statement) {
                    error_or_success_popup("success", "The term has been correctly removed from the blacklist.", false);
                }
            } else {
                error_or_success_popup("error", "The term to be removed from the blacklist is not in the blacklist.", false);
            }
        } else {
            error_or_success_popup("error", "The term to be removed from the blacklist must be between 1 and 200 characters long and in the correct format.", false);
        }
    }

    public function show_blacklist($token){
        if (isset($_POST["show_blacklist"]) && check_permissions("blacklisting") && sha1(session_id()) === $token) {
            $this->show_blacklist_gears();
        }
    }
    private function show_blacklist_gears()
    {
        $blacklist_statement = 'SELECT * FROM blacklist';
        $params = $types = [];
        $blacklist_statement = $this->execute_query($blacklist_statement, $params, $types, false);
        if ($blacklist_statement) {
            while ($blacklist_array = $blacklist_statement->fetchArray(SQLITE3_ASSOC)) {
                echo "<option value=\"" . encode_html($blacklist_array['blacklist']) . "\">" . encode_html($blacklist_array['blacklist']) . "</option>";
            }
        }
    }


}


?>