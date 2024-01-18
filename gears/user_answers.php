<?php

if (isset($page)) {

    if (isset($user_id) && !isset($answer_id)) {
        $count_posts_statement = 'SELECT COUNT(*) FROM posts WHERE member_id = :user_id';
        $params[':user_id'] = $user_id; $types[':user_id'] = SQLITE3_TEXT;
        $count_posts_statement = $everyone->execute_query($count_posts_statement, $params, $types, false)->fetchArray(SQLITE3_NUM);
    }

    if (isset($answer_id) && !isset($user_id)) {
        $count_posts_statement = 'SELECT COUNT(*) FROM posts WHERE post_id = :answer_id';
        $params[':answer_id'] = $answer_id; $types[':answer_id'] = SQLITE3_INTEGER;
        $count_posts_statement = $everyone->execute_query($count_posts_statement, $params, $types, false)->fetchArray(SQLITE3_NUM);
    }


    if ($count_posts_statement && $count_posts_statement[0] > 0) {

        $pages_sum = ceil($count_posts_statement[0] / $nb_user_posts);
        $total_pages = $pages_sum - 1;

        if ($page > $total_pages) {
            $page = $total_pages;
        }

        $offset = $page * $nb_user_posts;

        if (isset($answer_id) && !isset($user_id)) {

            $get_posts_statement = 'SELECT posts.post_id, posts.member_id, posts.post_data, posts.post_timestamp_creation, posts.topic_id, posts.main_post, members.username, members.avatar, members.role
                                                   FROM posts 
                                                   INNER JOIN members 
                                                   ON posts.member_id = members.member_id 
                                                   WHERE posts.post_id = :answer_id 
                                                   ORDER BY posts.post_timestamp_creation DESC';
            $params[':answer_id'] = $answer_id; $types[':answer_id'] = SQLITE3_INTEGER;

        }
        if (isset($user_id) && !isset($answer_id)) {

            $get_posts_statement = 'SELECT posts.post_id, posts.member_id, posts.post_data, posts.post_timestamp_creation, posts.topic_id, posts.main_post, members.username, members.avatar, members.role
                                                   FROM posts 
                                                   INNER JOIN members 
                                                   ON posts.member_id = members.member_id 
                                                   WHERE posts.member_id = :user_id
                                                   ORDER BY posts.post_timestamp_creation DESC 
                                                   LIMIT :nb_user_posts OFFSET :offset';
            $params[':user_id'] = $user_id; $types[':user_id'] = SQLITE3_INTEGER;
            $params[':nb_user_posts'] = $nb_user_posts; $types[':nb_user_posts'] = SQLITE3_INTEGER;
            $params[':offset'] = $offset; $types[':offset'] = SQLITE3_INTEGER;

        }

        $get_posts_statement = $everyone->execute_query($get_posts_statement, $params, $types, false);


        if (isset($get_posts_statement)) {

            if (!isset($answer_id)) {
                generate_pagination($total_pages, $page, $location, array('user_id' => $user_id));
            }

            $posts = array();

            while ($row = $get_posts_statement->fetchArray(SQLITE3_ASSOC)) {


                $message_object = array(
                    'user_answers' => true,
                    'post_id' => $row['post_id'],
                    'topic_id' => $row['topic_id'],
                    'post_data' => $row['post_data'],
                    'post_timestamp_creation' => $row['post_timestamp_creation'],
                    'username' => $row['username'],
                    'member_id' => $row['member_id'],
                    'avatar' => $row['avatar'],
                    'role' => $row['role'],
                    'main_post' => $row['main_post'],
                    'replies' => array()
                );


                $posts[$row['post_id']] = $message_object;

            }

            if (count($posts) > 0) {
                echo '<div class="message-list">';
                foreach ($posts as $post_display_id => $post) {
                    echo display_message($post_display_id);
                }
                echo '</div>';
            } 

            if (!isset($answer_id)) {
                generate_pagination($total_pages, $page, $location, array('user_id' => $user_id));
            }

        }

    } else {
        error_or_success_popup("error", "No results found - post(s) may have been deleted.", "?location=index");
    }


}