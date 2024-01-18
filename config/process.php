<?php

if (is_logged_in()) {

  $userValues = $user->get_user_values(array("role", "notifications", "banned", "muted"));

  if (isset($userValues["role"]) && is_string($userValues["role"])) {
    if ($_SESSION['role'] !== $userValues["role"]) {
      $_SESSION['role'] = $userValues["role"];
    }
  }

  if (isset($userValues["banned"]) && $userValues["banned"] === "yes") {
    $user->disconnect();
  }
  if (isset($userValues["muted"]) && $userValues["muted"] === "yes") {
    if ($_SESSION['muted'] !== $userValues["muted"]) {
      $_SESSION['muted'] = $userValues["muted"];
    }
  }

  if (isset($userValues["notifications"]) && $userValues["notifications"] === "yes") {
    $_SESSION['notifications'] = $userValues["notifications"];
  }

}


?>