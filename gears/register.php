<?php if ($registration_case === "enabled") { ?>

    <div class="center">
      <form action="" method="POST">
        <p>Username: (up to 20 characters)</p>
        <input class="center-placeholder" maxlength="20" type="text" name="username" placeholder="Username here" style="width: 200px;">

        <p>Password: (up to 65 characters)</p>
        <input class="center-placeholder" maxlength="65" type="password" name="password" placeholder="Password here" style="width: 260px;">

        <p>Confirm Password :</p>
        <input class="center-placeholder" maxlength="65" type="password" name="confirm_password" placeholder="Confirm Password here" style="width: 260px;">

        <p>E-mail or Jabber: (up to 80 characters, optional)</p>
        <input class="center-placeholder" maxlength="80" type="text" name="address" placeholder="E-mail or Jabber here"
          style="width: 250px;">

        <figure>
          <figcaption>Please fill in the captcha before submitting your request:</figcaption>
          <br>
          <img src="captcha/image.php" width="130" height="35" alt="captcha">
        </figure>

        <input class="center-placeholder" maxlength="4" type="text" name="code" placeholder="captcha here">

        <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">

        <button type="submit" name="register" style="display: block; margin: 20px auto;">Register</button>

      </form>
    </div>


  <?php } else {
    echo '<div class="center"> Registration is now closed. </div>';
  } ?>