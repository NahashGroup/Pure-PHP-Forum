

  <div class="center">
    <form action="" method="POST">
      <p>Username :</p>
      <input class="center-placeholder" maxlength="20" type="text" name="username" placeholder="Username here" style="width: 200px;">

      <p> Password : </p>
      <input class="center-placeholder" maxlength="65" type="password" name="password" placeholder="Password here" style="width: 250px;">

      <figure>
        <figcaption>Please fill in the captcha before submitting your request :</figcaption>
        <br>
        <img src="captcha/image.php" width="130" height="35" alt="captcha">
      </figure>

      <input class="center-placeholder" maxlength="4" type="text" name="code" placeholder="captcha here">

      <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">

      <button type="submit" name="login" style="display: block; margin: 20px auto;">Log in</button>
    </form>
  </div>

