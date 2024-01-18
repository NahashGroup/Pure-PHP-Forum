<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title><?php if (isset($site_name)) { echo encode_html($site_name); } ?> | <?php if (isset($page_name)) { echo encode_html($page_name); } ?>
  </title>
  <meta name="description" content="<?php if (isset($site_description)) { echo encode_html($site_description); } ?>">
  <meta name="keywords" content="<?php if (isset($site_keywords)) { echo encode_html($site_keywords); } echo ", " . date("Y"); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="design/img/icon.png">
  <link rel="stylesheet" href="design/style.css" />
</head>

<body>
  <div class="topnav">
    <a <?php if (isset($location) && $location === "index") { echo 'class="active"'; } ?> href="?location=index">Home
      <img src="design/img/home.png" height="15" width="15"></a>

    <?php if (!is_logged_in()) { ?>
      <a <?php if (isset($location) && $location === "register") { echo 'class="active"'; } ?> href="?location=register">Register <img src="design/img/register.png" height="15" width="15"></a>
      <a <?php if (isset($location) && $location === "login") { echo 'class="active"'; } ?> href="?location=login">Log in <img src="design/img/login.png" height="15" width="15"></a>
    <?php } ?>

    <?php if (is_logged_in()) { ?>
      <?php if (check_permissions("control_panel")) { ?>
        <a <?php if (isset($location) && $location === "control_panel") { echo 'class="active"'; } ?> href="?location=control_panel">Control Panel <img src="design/img/control_panel.png" height="15" width="15"></a>
      <?php } ?>
      <a <?php if (isset($location) && $location === "profile") { echo 'class="active"'; } ?> href="?location=profile">Profile <img src="design/img/profile.png" height="15" width="15"></a>
      <a <?php if (isset($location) && $location === "messaging") { echo 'class="active"'; } ?> href="?location=messaging">Messaging <img src="design/img/messaging.png" height="15" width="15"> <?php if (isset($_SESSION['notifications']) && $_SESSION['notifications'] === "yes") { ?> <img src="design/img/red_bubble.png" alt="Notifications" height="15" width="15">
        <?php } ?>
      </a>
      <a href="<?php echo '?location=disconnect&nonce=' . encode_html($_SESSION['nonce']); ?>">Disconnect <img src="design/img/disconnect.png" height="15" width="15"></a>
      <div class="search-container">
        <form action="" method="GET">
          <input maxlength="70" type="text" placeholder="Search.." name="search">&nbsp;
          <button type="submit"><img src="design/img/search.png" height="20" width="20"></button>
        </form>
      </div>
    <?php } ?>
  </div>

  <div class="center">
    <h1><?php if (isset($site_name)) { echo encode_html($site_name); } ?></h1>
    
    <h2><strong><?php if (isset($page_name)) { echo encode_html($page_name); } ?></h2></strong>
  </div>