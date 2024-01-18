<?php

if (isset($page)) {

    if (is_logged_in() && ((!isset($get_topic_id_and_topic_name_and_pinned_and_locked_statement["locked"]) || $get_topic_id_and_topic_name_and_pinned_and_locked_statement["locked"] !== "yes") || check_permissions("full_access"))) {

        if (isset($_GET["targeted_post"]) && ctype_digit($_GET["targeted_post"])) {
            $targeted_post = $_GET["targeted_post"];
            echo '<div class="center">'
               . "You have just quoted a message, the next message you send on this topic will automatically be linked to the quoted message. If you do not wish this, please click on 'abandon' to stop quoting.<a href='?location=answers&id=$topic_id&page=$page'>Abandon</a>"
               . '</div>';
    
            if (isset($_COOKIE['loaded'])) {
                header("Location: ?location=answers&id=$topic_id&page=$page");
            } else {
                setcookie('loaded', true, time() + 5);
            }
        }
        
        $user->new_post_on_topic($token, $topic_id, $targeted_post);
    
    }

    $check_topic_id_exists_statement = 'SELECT COUNT(*) FROM topics WHERE topic_id = :topic_id';
    $params[':topic_id'] = $topic_id; $types[':topic_id'] = SQLITE3_INTEGER;
    $check_topic_id_exists_statement = $everyone->execute_query($check_topic_id_exists_statement, $params, $types, false)->fetchArray(SQLITE3_NUM);

    if ($check_topic_id_exists_statement && $check_topic_id_exists_statement[0] > 0) {

        $count_posts_by_id_statement = 'SELECT COUNT(*) FROM posts WHERE topic_id = :topic_id';
        $params[':topic_id'] = $topic_id; $types[':topic_id'] = SQLITE3_INTEGER;
        $count_posts_by_id_statement = $everyone->execute_query($count_posts_by_id_statement, $params, $types, false)->fetchArray(SQLITE3_NUM);

        if ($count_posts_by_id_statement && $count_posts_by_id_statement[0] > 0) {

            $pages_sum = ceil($count_posts_by_id_statement[0] / $nb_posts);
            $total_pages = $pages_sum - 1;

            if ($page > $total_pages) {
                $page = $total_pages;
            }

            $offset = $page * $nb_posts;

            $get_topic_id_and_topic_name_and_pinned_and_locked_statement = 'SELECT topic_id, topic_name, pinned, locked FROM topics WHERE topic_id = :topic_id';
            $params[':topic_id'] = $topic_id; $types[':topic_id'] = SQLITE3_INTEGER;
            $get_topic_id_and_topic_name_and_pinned_and_locked_statement = $everyone->execute_query($get_topic_id_and_topic_name_and_pinned_and_locked_statement, $params, $types, false)->fetchArray(SQLITE3_ASSOC);

            if (isset($get_topic_id_and_topic_name_and_pinned_and_locked_statement["topic_name"])) {

                echo '<div class="center">'
                   . '<h3>'
                   . ($get_topic_id_and_topic_name_and_pinned_and_locked_statement['pinned'] == "yes" ? '<img src="design/img/pinned.png"/> ' : '')
                   . ($get_topic_id_and_topic_name_and_pinned_and_locked_statement['locked'] == "yes" ? '<img src="design/img/locked.png"/> ' : '')
                   . encode_html($get_topic_id_and_topic_name_and_pinned_and_locked_statement["topic_name"])
                   . '</h3>'
                   . '</div>'
                   . '<div class="wrapper">';

                if (check_permissions("pin_or_unpin_topic_by_id")) {
                    echo '<form action="" method="post">'
                       . '<input type="hidden" value="' . encode_html($get_topic_id_and_topic_name_and_pinned_and_locked_statement['topic_id']) . '" name="topic_id">'
                       . '<input type="hidden" name="token" value="' . encode_html(sha1(session_id())) . '">'
                       . '<button type="submit" name="pin_or_unpin_topic_by_id">' . ($get_topic_id_and_topic_name_and_pinned_and_locked_statement['pinned'] === "yes" ? '<img src="design/img/unpin.png"/> ' : '<img src="design/img/pin.png"/>') . '</button>'
                       . '</form>';
                }
                if (check_permissions("delete_topic_by_id")) {
                    echo '<form action="" method="post">'
                       . '<input type="hidden" value="' . encode_html($get_topic_id_and_topic_name_and_pinned_and_locked_statement['topic_id']) . '" name="topic_id">'
                       . '<input type="hidden" name="token" value="' . encode_html(sha1(session_id())) . '">'
                       . '<button type="submit" name="delete_topic_by_id">Delete the topic</button>'
                       . '</form>';
                }
                if (check_permissions("lock_or_unlock_topic_by_id")) {
                    echo '<form action="" method="post">'
                       . '<input type="hidden" value="' . encode_html($get_topic_id_and_topic_name_and_pinned_and_locked_statement['topic_id']) . '" name="topic_id">'
                       . '<input type="hidden" name="token" value="' . encode_html(sha1(session_id())) . '">'
                       . '<button type="submit" name="lock_or_unlock_topic_by_id">' . ($get_topic_id_and_topic_name_and_pinned_and_locked_statement['locked'] === "yes" ? '<img src="design/img/unlock.png"/> ' : '<img src="design/img/lock.png"/>') . '</button>'
                       . '</form>';
                }
                echo '</div>';


            }


            $get_posts_statement = 'SELECT posts.*, members.username, members.avatar, members.role
                       FROM posts
                       JOIN members
                       ON posts.member_id = members.member_id
                       WHERE posts.topic_id = :topic_id
                       ORDER BY post_timestamp_creation ASC
                       LIMIT :nb_posts OFFSET :offset;';


            $params[':topic_id'] = $topic_id; $types[':topic_id'] = SQLITE3_INTEGER;
            $params[':nb_posts'] = $nb_posts; $types[':nb_posts'] = SQLITE3_INTEGER;
            $params[':offset'] = $offset; $types[':offset'] = SQLITE3_INTEGER;

            $get_posts_statement = $everyone->execute_query($get_posts_statement, $params, $types, false);

            if (isset($get_posts_statement)) {

                generate_pagination($total_pages, $page, $location, array('id' => $topic_id));

                $posts = array();

                while ($row = $get_posts_statement->fetchArray(SQLITE3_ASSOC)) {

                    $message_object = array(
                        'post_id' => $row['post_id'],
                        'topic_id' => $topic_id,
                        'post_data' => $row['post_data'],
                        'post_timestamp_creation' => $row['post_timestamp_creation'],
                        /*
                        'parent_id' => $row['parent_id'],
                        */
                        'username' => $row['username'],
                        'member_id' => $row['member_id'],
                        'avatar' => $row['avatar'],
                        'role' => $row['role'],
                        'main_post' => $row['main_post'],
                        /*
                        'child' => $row['child'],
                        */
                        'replies' => array()
                    );

                    $posts[$row['post_id']] = $message_object;

                    /*
                    if ($row['parent_id'] != 0) {
                        $posts[$row['parent_id']]['replies'][] = $row['post_id'];
                    }
                    */
                }

                echo '<div class="message-list">';
                foreach ($posts as $post_display_id => $post) {
                    /*
                    if ($post['parent_id'] == 0) {
                        echo display_message($post_display_id);
                    }
                    */
                    echo display_message($post_display_id);
                }
                echo '</div>';

                generate_pagination($total_pages, $page, $location, array('id' => $topic_id));

            }

        } else {
            error_or_success_popup("error", "No posts exist under this topic.", "?location=index");
        }

    } else {
        error_or_success_popup("error", "The topic does not exist or has been deleted.", "?location=index");
    }
}


if (is_logged_in() && ((!isset($get_topic_id_and_topic_name_and_pinned_and_locked_statement["locked"]) || $get_topic_id_and_topic_name_and_pinned_and_locked_statement["locked"] != "yes") || check_permissions("full_access"))) {
    echo '<div class="new-form">'
     . '<h2>Reply to the topic</h2>'
    . '<form action="" method="POST">'
    . '<textarea maxlength="10000" name="new_post_on_topic" type="text" placeholder="Please respect the rules in force on the site before posting, you can also use BBCode here like: [center][/center], [color=][/color], [b][/b], [i][/i], [code][/code], [url][/url], [url=][/url], [img][/img] or put an @ before the username."></textarea>'
    . '<input type="hidden" name="token" value="'
    . encode_html(sha1(session_id()))
    . '">'
    . '<input type="submit" name="new_answer" value="Send">'
    . '</form>'
    .'</div>';
}


?>

