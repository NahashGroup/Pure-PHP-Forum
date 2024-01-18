<?php

require('config/config.php');
require('config/functions.php');
require('config/class.php');

$everyone = new everyone($db);

AntiInjectionSQL();

$location = $_GET["location"] ?? "index";

$locations = array(
  "register" => "Register",
  "login" => "Log in",
  "index" => "Home",
  "profile" => "Profile",
  "disconnect" => "Disconnect",
  "answers" => "Reply to the topic",
  "user_answers" => "Replie(s)",
  "messaging" => "Messaging",
  "conversation" => "Conversation",
  "control_panel" => "Control Panel",
  "logs" => "Logs"
);

if (array_key_exists($location, $locations)) {
  $page_name = $locations[$location];

  require('header.php');

  $token = (string) filter_input(INPUT_POST, "token");

  if (!is_logged_in()) {
    $visitor = new visitor($db);
  }

  if (is_logged_in()) {
    $user = new user($db);
    if (check_permissions("control_panel")) {
      $control = new control($db);
    }
  }

  if ($location === "index") {
    if (is_logged_in() && isset($_GET["search"]) && validate_post($_GET["search"], 1, 70)) {
      $everyone->ratelimit('search', 1, true);
      $search = $_GET["search"];
    } else {
      $search = null;
    }
    if (isset($_GET["page"]) && ctype_digit($_GET["page"])) {
      $page = $_GET["page"];
    } else {
      $page = 0;
    }
    require('gears/place.php');
  }

  if ($location === "register") {
    if (!is_logged_in()) {
      $visitor->register($token);
      require('gears/register.php');
    } else {
      error_or_success_popup("error", "You are already registered.", "?location=index");
    }
  }

  if ($location === "login") {
    if (!is_logged_in()) {
      $visitor->login($token);
      require('gears/login.php');
    } else {
      error_or_success_popup("error", "You are already logged in.", "?location=index");
    }
  }

  if ($location === "profile") {
    if (isset($_GET["user"])) {
      if (validate_post($_GET["user"], 1, 20)) {
        $user_target = $_GET["user"];
      } else {
        error_or_success_popup("error", "The profile username must be between 1 and 20 characters long and in the correct format.", "?location=index");
      }
    } else if (is_logged_in()) {
      $user_target = $_SESSION['username'];
    } else {
      error_or_success_popup("error", "You cannot access the profile page if you are not logged in.", "?location=index");
    }

    require('gears/profil.php');

    if (check_permissions("ban_or_unban_user_by_username")) {
      $control->ban_or_unban_user_by_username($token);
    }
    if (check_permissions("mute_or_unmute_user_by_username")) {
      $control->mute_or_unmute_user_by_username($token);
    }
    if (check_permissions("delete_all_user_posts_by_username")) {
      $control->delete_all_user_posts_by_username($token);
    }

  }

  if ($location === "answers") {

    if (isset($_GET["id"]) && ctype_digit($_GET["id"])) {
      $topic_id = $_GET["id"];
      if (isset($_GET["page"]) && ctype_digit($_GET["page"])) {
        $page = $_GET["page"];
      } else {
        $page = 0;
      }
    } else {
      header("Location: ?location=index");
    }

    if (check_permissions("delete_post_by_id")) {
      $control->delete_post_by_id($token);
    }
    if (check_permissions("delete_topic_by_id")) {
      $control->delete_topic_by_id($token);
    }
    if (check_permissions("pin_or_unpin_topic_by_id")) {
      $control->pin_or_unpin_topic_by_id($token);
    }
    if (check_permissions("lock_or_unlock_topic_by_id")) {
      $control->lock_or_unlock_topic_by_id($token);
    }
    require('gears/answers.php');
  }

  if ($location === "user_answers") {
    if (check_permissions("delete_post_by_id")) {
      $control->delete_post_by_id($token);
    }
    if (isset($_GET["user_id"]) && ctype_digit($_GET["user_id"])) {
      $user_id = $_GET["user_id"];
    } else if (isset($_GET["answer_id"]) && ctype_digit($_GET["answer_id"])) {
      $answer_id = $_GET["answer_id"];
    }

    if (isset($_GET["page"]) && ctype_digit($_GET["page"])) {
      $page = $_GET["page"];
    } else {
      $page = 0;
    }
    require('gears/user_answers.php');
  }

  if ($location === "messaging") {
    if (is_logged_in()) {
      $user->read_notifications();
      if (isset($_GET["page"]) && ctype_digit($_GET["page"])) {
        $page = $_GET["page"];
      } else {
        $page = 0;
      }
      require('gears/messaging.php');
    } else {
      error_or_success_popup("error", "You must be logged in to access the messaging.", "?location=index");
    }
  }

  if ($location === "conversation") {
    if (is_logged_in()) {
      if (isset($_GET["user_id"]) && ctype_digit($_GET["user_id"])) {
        $user_id = $_GET["user_id"];
        if (isset($_GET["page"]) && ctype_digit($_GET["page"])) {
          $page = $_GET["page"];
        } else {
          $page = 0;
        }
      } else {
        error_or_success_popup("error", "User id is invalid.", "?location=index");
      }
      require('gears/conversation.php');
      $user->read_message_notification($user_id);
    } else {
      error_or_success_popup("error", "You must be logged in to access the messaging.", "?location=index");
    }
  }

  if ($location === "disconnect") {
    if (is_logged_in() && isset($_GET['nonce']) && $_GET['nonce'] === $_SESSION['nonce']) {
      $user->disconnect();
    } else {
      error_or_success_popup("error", "Unable to disconnect.", "?location=index");
    }
  }

  if ($location === "control_panel") {
    if (check_permissions("control_panel")) {
      if (check_permissions("modify_site_data")) {
        $control->modify_site_data($token);
      }
      if (check_permissions("delete_post_by_id")) {
        $control->delete_post_by_id($token);
      }
      if (check_permissions("delete_topic_by_id")) {
        $control->delete_topic_by_id($token);
      }
      if (check_permissions("pin_or_unpin_topic_by_id")) {
        $control->pin_or_unpin_topic_by_id($token);
      }
      if (check_permissions("lock_or_unlock_topic_by_id")) {
        $control->lock_or_unlock_topic_by_id($token);
      }
      if (check_permissions("ban_or_unban_user_by_username")) {
        $control->ban_or_unban_user_by_username($token);
      }
      if (check_permissions("mute_or_unmute_user_by_username")) {
        $control->mute_or_unmute_user_by_username($token);
      }
      if (check_permissions("delete_all_user_posts_by_username")) {
        $control->delete_all_user_posts_by_username($token);
      }
      if (check_permissions("refresh_database")) {
        $control->refresh_database($token);
      }
      if (check_permissions("delete_all_private_messages")) {
        $control->delete_all_private_messages($token);
      }
      if (check_permissions("grant_privileges_by_username")) {
        $control->grant_privileges_by_username($token);
      }
      if (check_permissions("enable_or_disable_registration_case")) {
        $control->enable_or_disable_registration_case($token);
      }
      if (check_permissions("blacklisting")) {
        $control->blacklist_therm($token);
        $control->unblacklist_therm($token);
      }
      require('gears/control_panel.php');
    } else {
      header("Location: ?location=index");
    }
  }

  if ($location === "logs") {
    if (check_permissions("logs")) {
      $control->logs_methods($token);
      require('gears/logs.php');
    } else {
      header("Location: ?location=index");
    }
  }

  require('config/process.php');
  require('footer.php');

} else {
  header("Location: ?location=index");
}




?>