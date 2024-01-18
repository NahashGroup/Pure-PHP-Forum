<?php

if (isset($user_target)) {

  if ($everyone->check_user_exists($user_target)) {

    if (is_logged_in() && strtolower($user_target) === strtolower($_SESSION['username'])) {

      $user->save_edit_profil($token);
      $user->save_edit_password($token);
      $user->save_edit_avatar($token);

    }

    $user_infos_statetement = 'SELECT member_id, username, role, member_timestamp_creation, member_timestamp_lastseen, address, description, avatar, posts_count, banned, muted FROM members WHERE LOWER(username) = :username;';
    $params[':username'] = strtolower($user_target); $types[':username'] = SQLITE3_TEXT;
    $user_infos_statetement = $everyone->execute_query($user_infos_statetement, $params, $types, false)->fetchArray(SQLITE3_ASSOC);

    if (isset($user_infos_statetement["member_id"])) {

      echo '<div class="wrapper">';

      if (check_permissions("ban_or_unban_user_by_username")) {
        echo '<form action="" method="POST">'
        . '<input type="hidden" value="' . encode_html($user_infos_statetement["username"]) . '" name="username">'
        . '<input type="hidden" name="token" value="' . encode_html(sha1(session_id())) . '">'
        . '<button type="submit" name="ban_or_unban_user_by_username">' . ($user_infos_statetement["banned"] === "yes" ? 'Unban' : 'Ban') . '</button>'
        . '</form>';
      }
      if (check_permissions("delete_all_user_posts_by_username")) {
        echo '<form action="" method="POST">'
        . '<input type="hidden" value="' . encode_html($user_infos_statetement["username"]) . '" name="username">'
        . '<input type="hidden" name="token" value="' . encode_html(sha1(session_id())) . '">'
        . '<button type="submit" name="delete_all_user_posts_by_username">Delete all messages</button>'
        . '</form>';
      }
      if (check_permissions("mute_or_unmute_user_by_username")) {
        echo '<form action="" method="POST">'
        . '<input type="hidden" value="' . encode_html($user_infos_statetement["username"]) . '" name="username">'
        . '<input type="hidden" name="token" value="' . encode_html(sha1(session_id())) . '">'
        . '<button type="submit" name="mute_or_unmute_user_by_username">' . ($user_infos_statetement["muted"] === "yes" ? 'Unmute' : 'Mute') . '</button>'
        . '</form>';
      }

      echo '</div>';

      echo '<div class="center">';

      echo '<img class="avatarsquare" src="' . encode_avatar($user_infos_statetement["avatar"]) . '" width="150" height="150" alt="avatar">';

      echo convert_role($user_infos_statetement["role"]);

      if ($user_infos_statetement["banned"] === "yes") {
        echo "<span style='color: red;'>BANNED USER</span>";
      }
      if(is_logged_in() && $user_target !== $_SESSION['username']){
        echo '<a href="?location=conversation&user_id=' . encode_html($user_infos_statetement["member_id"]) . '#bottom"><img src="design/img/private_message.png"/> Send a private message <img src="design/img/private_message.png"/></a>';
      }

      echo "<p>Username : " . encode_html($user_infos_statetement["username"]) . '</p>'
         . "<p>E-mail / Jabber : " . encode_html($user_infos_statetement["address"]) . '</p>'
         . "<p>Description : </p>"
         . '<div class="card opt">'
         . '<i>' . bbcode(encode_html($user_infos_statetement["description"])) . '</i>'
         . '</div>';

      if($user_infos_statetement["posts_count"] > 0){
      echo '<p><small>Number of posts : ' . encode_html($user_infos_statetement["posts_count"]) . '</small> <a href="?location=user_answers&user_id=' . encode_html($user_infos_statetement["member_id"]) . '">See</a></p>';
      }

      echo '<small>Last connection : ' . encode_html(convert_date($user_infos_statetement["member_timestamp_lastseen"])) . '</small>';
      echo '<small>Account creation : ' . encode_html(convert_date($user_infos_statetement["member_timestamp_creation"])) . '</small>';

      echo '</div>';

    }

  } else {
    error_or_success_popup("error", "No user exists under this username.", "?location=index");
  }

}


?>


<?php if (is_logged_in() && isset($user_target) && strtolower($user_target) === strtolower($_SESSION['username'])) { ?>

  <div class="center"><h2><strong>Edit Profile information : </strong></h2></div>

  <div class="wrapper">
    <form action="#bottom" method="post"><button type="submit" name="edit_password">Edit Password</button></form>
    <form action="#bottom" method="post"><button type="submit" name="edit_profil">Edit Profile</button></form>
    <form action="#bottom" method="post"><button type="submit" name="edit_avatar">Edit Profile Picture</button></form>
  </div>


  <?php if (isset($_POST["edit_profil"])) { ?>
    <div class="center">
      <div class="card opt">
        <form action="" method="post">
            <p>Description:</p>
            <textarea class="center-placeholder" maxlength="250" type="text" id="description" name="description" placeholder="Please respect the rules in force on the site before posting, you can also use BBCode here like: [center][/center], [color=][/color], [b][/b], [i][/i], [code][/code], [url][/url], [url=][/url], [img][/img] or put an @ before the username.">
              <?php if (isset($user_infos_statetement["description"])) { echo encode_html($user_infos_statetement["description"]); } ?>
            </textarea>

            <p>E-mail or Jabber :</p>
            <input class="center-placeholder" maxlength="80" type="text" id="address" name="address" placeholder="E-mail or Jabber here">
            
          <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">

          <button type="submit" name="save_edit_profil" style="display: block; margin: 20px auto;">Save</button>
        </form>
      </div>
    </div>
  <?php } ?>

  <?php if (isset($_POST["edit_password"])) { ?>
    <div class="center">
      <div class="card opt">
        <form action="" method="post">
            <p>Old Password :</p>
            <input class="center-placeholder" maxlength="65" type="password" name="old_password" placeholder="Old password here">

            <p>New Password :</p>
            <input class="center-placeholder" maxlength="65" type="password" name="new_password" placeholder="New Password here">

          <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">
          <button type="submit" name="save_edit_password" style="display: block; margin: 20px auto;">Change</button></p>
        </form>
      </div>
    </div>
  <?php } ?>


  <?php if (isset($_POST["edit_avatar"])) { ?>
    <div class="center">
      <div class="card opt">
        <form action="" method="post" enctype="multipart/form-data">

          <h2>Import Profile Picture</h2>
          <p>(2MB maximum, size 150x150 and formats only accepted: jpeg, jpeg, png, gif)</p>

            <input type="file" name="avatar" required="required" class="form-control" />

          <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">
          <button type="submit" name="save_edit_avatar" style="display: block; margin: 20px auto;">Import</button>
        </form>
      </div>
    </div>
  <?php } ?>

<div id="bottom"></div>

<?php } ?>