<?php

if (isset($user_id)) {

    $user->new_message($token, $user_id);

    $check_user_id_exists_statement = 'SELECT member_id, banned FROM members WHERE member_id = :user_id';
    $params[':user_id'] = $user_id;
    $types[':user_id'] = SQLITE3_INTEGER;
    $check_user_id_exists_statement = $everyone->execute_query($check_user_id_exists_statement, $params, $types, false)->fetchArray(SQLITE3_ASSOC);


    if ($check_user_id_exists_statement && $check_user_id_exists_statement["member_id"] == $user_id) {

        if ($check_user_id_exists_statement["member_id"] !== $_SESSION['member_id']) {

            if ($check_user_id_exists_statement["banned"] || $check_user_id_exists_statement["banned"] !== "yes") {


                $count_messages_statement = 'SELECT COUNT(*) AS message_count FROM messages WHERE (sender_id = :user_id AND recipient_id = :member_id) OR (recipient_id = :user_id AND sender_id = :member_id)';

                $params[':user_id'] = $user_id;
                $types[':user_id'] = SQLITE3_INTEGER;
                $params[':member_id'] = $_SESSION['member_id'];
                $types[':member_id'] = SQLITE3_INTEGER;

                $count_messages_statement = $everyone->execute_query($count_messages_statement, $params, $types, false)->fetchArray(SQLITE3_NUM);

                if ($count_messages_statement && $count_messages_statement[0] > 0) {


                    $pages_sum = ceil($count_messages_statement[0] / $nb_conversations_messages);
                    $total_pages = $pages_sum - 1;

                    if ($page > $total_pages) {
                        $page = $total_pages;
                    }

                    $offset = ($pages_sum - $page - 1) * $nb_conversations_messages;

                    $get_messages_statement = 'SELECT sender_id, recipient_id, message_data, message_timestamp, members.username, members.avatar
                                                   FROM messages
                                                   JOIN members ON sender_id = members.member_id
                                                   WHERE (sender_id = :user_id AND recipient_id = :member_id) OR (recipient_id = :user_id AND sender_id = :member_id)
                                                   LIMIT :nb_conversations_messages OFFSET :offset';

                    $params[':user_id'] = $user_id;
                    $types[':user_id'] = SQLITE3_INTEGER;
                    $params[':member_id'] = $_SESSION['member_id'];
                    $types[':member_id'] = SQLITE3_INTEGER;
                    $params[':offset'] = $offset;
                    $types[':offset'] = SQLITE3_INTEGER;
                    $params[':nb_conversations_messages'] = $nb_conversations_messages;
                    $types[':nb_conversations_messages'] = SQLITE3_INTEGER;
                    $get_messages_statement = $everyone->execute_query($get_messages_statement, $params, $types, false);

                    if (isset($get_messages_statement)) {

                        generate_pagination($total_pages, $page, $location, array('user_id' => $user_id));

                        echo '<div class="pm-container">';

                        while ($row = $get_messages_statement->fetchArray(SQLITE3_ASSOC)) {

                            if ($row['recipient_id'] === $_SESSION['member_id']) {
                                echo '<div class="pm-message received">'
                                    . '<img class="avatar" src="' . encode_avatar($row["avatar"]) . '" alt="avatar">'
                                    . '<div class="content">'
                                    . '<a href="?location=profile&user=' . encode_html($row['username']) . '"><div>' . encode_html($row["username"]) . '</div></a>'
                                    . '<p>' . bbcode(encode_html($row["message_data"])) . '</p>'
                                    . '<div class="meta">' . encode_html(convert_date($row["message_timestamp"])) . '</div>'
                                    . '</div>'
                                    . '</div>';
                            } else {
                                echo '<div class="pm-message sent">'
                                    . '<div class="content">'
                                    . '<div>Your message :</div>'
                                    . '<p>' . bbcode(encode_html($row["message_data"])) . '</p>'
                                    . '<div class="meta">' . encode_html(convert_date($row["message_timestamp"])) . '</div>'
                                    . '</div>'
                                    . '</div>';
                            }

                        }

                        echo '<form class="pm-form" method="post">'
                            . '<input type="text" name="new_message_data" maxlength="400" placeholder="Type your message...">'
                            . '<input type="hidden" name="token" value="' . encode_html(sha1(session_id())) . '">'
                            . '<button type="submit" name="new_message">Send</button>'
                            . '</form>';

                        echo '<div id="bottom"></div>';
                        echo '</div>';

                        generate_pagination($total_pages, $page, $location, array('user_id' => $user_id));

                    }

                } else {

                    echo '<div class="pm-container">'
                        . '<div class="center">'
                        . 'No message available. Send your first private message to this user.'
                        . '</div>'
                        . '<form class="pm-form" method="post">'
                        . '<input type="text" name="new_message_data" maxlength="400" placeholder="Type your message...">'
                        . '<input type="hidden" name="token" value="' . encode_html(sha1(session_id())) . '">'
                        . '<button type="submit" name="new_message">Send</button>'
                        . '</form>'
                        . '</div>';


                }
            } else {
                error_or_success_popup("error", "The user is banned.", "?location=index");
            }
        } else {
            error_or_success_popup("error", "Are you trying to send yourself a message or am I dreaming? This is a very strange thing to do, stop it right now! I'll give you a number for psychological support right now: 0 800 858 858.", "?location=index");
        }
    } else {
        error_or_success_popup("error", "The user doesn't exist.", "?location=index");
    }

}


?>