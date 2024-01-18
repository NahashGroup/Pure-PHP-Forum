<div class="containersquares">
    <?php if (check_permissions("modify_site_data")) { ?>
        <div class="boxsquares">
            <div class="contentsquares">
                <h2>⚠️ Edit Site Information ⚠️</h2>
                <form action="" method="POST">
                    <input type="text" maxlength="20" placeholder="<?php if (isset($site_name)) {
                        echo encode_html($site_name);
                    } ?>" name="site_name">
                    <input type="text" maxlength="200" placeholder="<?php if (isset($site_description)) {
                        echo encode_html($site_description);
                    } ?>" name="site_description">
                    <input type="text" maxlength="200" placeholder="<?php if (isset($site_keywords)) {
                        echo encode_html($site_keywords);
                    } ?>" name="site_keywords">
                    <input type="text" maxlength="120" placeholder="<?php if (isset($site_footer)) {
                        echo encode_html($site_footer);
                    } ?>" name="site_footer">

                    <input type="text" maxlength="2" placeholder="Topics by Page : <?php if (isset($nb_topics)) {
                        echo encode_html($nb_topics);
                    } ?>" name="nb_topics">
                    <input type="text" maxlength="2" placeholder="Posts by Topic : <?php if (isset($nb_posts)) {
                        echo encode_html($nb_posts);
                    } ?>" name="nb_posts">
                    <input type="text" maxlength="2" placeholder="Posts by Profile : <?php if (isset($nb_user_posts)) {
                        echo encode_html($nb_user_posts);
                    } ?>" name="nb_user_posts">
                    <input type="text" maxlength="2" placeholder="Conv. by User : <?php if (isset($nb_conversations)) {
                        echo encode_html($nb_conversations);
                    } ?>" name="nb_conversations">
                    <input type="text" maxlength="2" placeholder="Messages by Conv. : <?php if (isset($nb_conversations_messages)) {
                        echo encode_html($nb_conversations_messages);
                    } ?>" name="nb_conversations_messages">

                    <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">
                    <input type="submit" name="modify_site_data" value="Edit">
                </form>
            </div>
        </div>
    <?php }
    if (check_permissions("logs")) { ?>
        <div class="boxsquares">
            <div class="contentsquares">
                <h2>Tools</h2>
                <form action="?location=logs" method="POST">
                    <button type="submit">Logs <img src="design/img/logs.png" height="20" width="20"></button>
                </form>
            </div>
        </div>
    <?php }
    if (check_permissions("show_users")) { ?>
        <div class="boxsquares">
            <div class="contentsquares">
                <h2> User List </h2>
                <h3> (From Old to Recent) </h3>
                <form method="GET" action="">
                    <input type="hidden" name="location" value="profile">
                    <select name="user" id="users_list_select">
                        <?php $control->show_users($token); ?>
                    </select>
                    <button type="submit" formtarget="_blank">View Profile</button>
                </form>
                <form action="" method="POST">
                    <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">
                    <input type="submit" name="show_users" value="Show Users">
                </form>
                <br>
            </div>
        </div>
    <?php }
    if (check_permissions("delete_post_by_id")) { ?>
        <div class="boxsquares">
            <div class="contentsquares">
                <h2>Delete a Post</h2>
                <form action="" method="POST">
                    <input type="text" placeholder="Post ID to be deleted" name="post_id">
                    <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">
                    <input type="submit" name="delete_post_by_id" value="Delete">
                </form>
            </div>
        </div>
    <?php }
    if (check_permissions("delete_topic_by_id")) { ?>
        <div class="boxsquares">
            <div class="contentsquares">
                <h2>Delete a Topic</h2>
                <form action="" method="POST">
                    <input type="text" placeholder="Topic ID to be deleted" name="topic_id">
                    <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">
                    <input type="submit" name="delete_topic_by_id" value="Delete">
                </form>
            </div>
        </div>
    <?php }
    if (check_permissions("pin_or_unpin_topic_by_id")) { ?>
        <div class="boxsquares">
            <div class="contentsquares">
                <h2>Pin or Unpin a Topic</h2>
                <form action="" method="POST">
                    <input type="text" placeholder="Topic ID to Pin/Unpin" name="topic_id">
                    <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">
                    <input type="submit" name="pin_or_unpin_topic_by_id" value="Pin/Unpin">
                </form>
            </div>
        </div>
    <?php }
    if (check_permissions("lock_or_unlock_topic_by_id")) { ?>
        <div class="boxsquares">
            <div class="contentsquares">
                <h2>Lock or Unlock a Topic</h2>
                <form action="" method="POST">
                    <input type="text" placeholder="Topic ID to Lock/Unlock" name="topic_id">
                    <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">
                    <input type="submit" name="lock_or_unlock_topic_by_id" value="Lock/Unlock">
                </form>
            </div>
        </div>
    <?php }
    if (check_permissions("ban_or_unban_user_by_username")) { ?>
        <div class="boxsquares">
            <div class="contentsquares">
                <h2>Ban or Unban a User</h2>
                <form action="" method="POST">
                    <input type="text" maxlength="20" placeholder="Username" name="username">
                    <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">
                    <input type="submit" name="ban_or_unban_user_by_username" value="Ban/Unban">
                </form>
            </div>
        </div>
    <?php }
    if (check_permissions("mute_or_unmute_user_by_username")) { ?>
        <div class="boxsquares">
            <div class="contentsquares">
                <h2>Mute or unmute a user</h2>
                <form action="" method="POST">
                    <input type="text" maxlength="20" placeholder="Username" name="username">
                    <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">
                    <input type="submit" name="mute_or_unmute_user_by_username" value="Mute/Unmute">
                </form>
            </div>
        </div>
    <?php }
    if (check_permissions("delete_all_user_posts_by_username")) { ?>
        <div class="boxsquares">
            <div class="contentsquares">
                <h2>Delete all user messages</h2>
                <form action="" method="POST">
                    <input type="text" maxlength="20" placeholder="Username" name="username">
                    <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">
                    <input type="submit" name="delete_all_user_posts_by_username" value="Delete">
                </form>
            </div>
        </div>
    <?php }
    if (check_permissions("refresh_database")) { ?>
        <div class="boxsquares">
            <div class="contentsquares">
                <h2>⚠️ Update database ⚠️</h2>
                <form action="" method="POST">
                    <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">
                    <input type="submit" name="refresh_database" value="Update">
                </form>
            </div>
        </div>
    <?php }
        if (check_permissions("delete_all_private_messages")) { ?>
            <div class="boxsquares">
                <div class="contentsquares">
                    <h2>⚠️ Delete all private messages ⚠️</h2>
                    <form action="" method="POST">
                        <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">
                        <input type="submit" name="delete_all_private_messages" value="Delete All">
                    </form>
                </div>
            </div>
        <?php }
    if (check_permissions("grant_privileges_by_username")) { ?>
        <div class="boxsquares">
            <div class="contentsquares">
                <h2>⚠️ Granting rights to a user ⚠️</h2>
                <form action="" method="POST">
                    <input type="text" maxlength="20" placeholder="Username" name="username">
                    <select name="role_select">
                        <option value="super-admin">Super-Administrator</option>
                        <option value="admin">Administrator</option>
                        <option value="moderator">Moderator</option>
                        <option value="member">Member</option>
                    </select>
                    <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">
                    <input type="submit" name="grant_privileges_by_username" value="Grant">
                </form>
            </div>
        </div>
    <?php }
    if (check_permissions("enable_or_disable_registration_case")) { ?>
        <div class="boxsquares">
            <div class="contentsquares">
                <h2>⚠️ Enable/Disable registration ⚠️</h2>
                <form action="" method="POST">
                    <select name="registration_case">
                        <?php if (isset($registration_case) && $registration_case === "enabled") {
                            echo '<option value="enabled">Enabled</option>';
                            echo '<option value="disabled">Disabled</option>';
                        } else {
                            echo '<option value="disabled">Disabled</option>';
                            echo '<option value="enabled">Enabled</option>';
                        } ?>
                    </select>
                    <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">
                    <input type="submit" name="enable_or_disable_registration_case" value="Enable/Disable">
                </form>
            </div>
        </div>
    <?php }
    if (check_permissions("blacklisting")) { ?>
        <div class="boxsquares">
            <div class="contentsquares">
                <h2> Blacklist system </h2>
                <br>
                <h3>Add a Term to the Blacklist</h3>
                <form action="" method="POST">
                    <p><input type="text" maxlength="200" placeholder="Anything" name="string_to_blacklist"></p>
                    <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">
                    <input type="submit" name="blacklist_therm" value="Add">
                </form>
                <h3>Remove a Term from the Blacklist</h3>
                <form action="" method="POST">
                    <p><input type="text" maxlength="200" placeholder="Anything" name="string_to_unblacklist"></p>
                    <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">
                    <input type="submit" name="unblacklist_therm" value="Remove">
                </form>
                <br>
                <h3>Current Blacklist:</h3>
                <select name="blacklist_select">
                    <?php $control->show_blacklist($token); ?>
                </select>
                <form action="" method="POST">
                    <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">
                    <input type="submit" name="show_blacklist" value="Show Blacklist">
                </form>
                <br>
            </div>
        </div>
    <?php } ?>
</div>