<?php

if (isset($page)) {

    $count_conversations_statement = 'SELECT COUNT(DISTINCT CASE
                                                   WHEN sender_id = :member_id THEN recipient_id
                                                   ELSE sender_id
                                                   END) AS conversation_count
                                            FROM messages
                                            WHERE :member_id IN (sender_id, recipient_id);';

    $params[':member_id'] = $_SESSION['member_id'];
    $types[':member_id'] = SQLITE3_INTEGER;

    $count_conversations_statement = $everyone->execute_query($count_conversations_statement, $params, $types, false)->fetchArray(SQLITE3_NUM);

    if ($count_conversations_statement && $count_conversations_statement[0] > 0) {

        $pages_sum = ceil($count_conversations_statement[0] / $nb_conversations);
        $total_pages = $pages_sum - 1;

        if ($page > $total_pages) {
            $page = $total_pages;
        }

        $offset = $page * $nb_conversations;

        $get_conversations_statement = 'SELECT DISTINCT
                                        CASE
                                        WHEN sender_id = :member_id THEN recipient_id
                                        ELSE sender_id
                                        END AS conversation_partner_id,
                                        MAX(message_timestamp) AS message_timestamp, sender_id, message_read, members.username, members.avatar, members.member_id
                                        FROM messages
                                        INNER JOIN members ON members.member_id = CASE
                                        WHEN sender_id = :member_id THEN recipient_id
                                        ELSE sender_id
                                        END
                                        WHERE :member_id IN (sender_id, recipient_id)
                                        GROUP BY conversation_partner_id
                                        ORDER BY message_timestamp DESC
                                        LIMIT :nb_conversations OFFSET :offset';

        $params[':offset'] = $offset;
        $types[':offset'] = SQLITE3_INTEGER;
        $params[':member_id'] = $_SESSION['member_id'];
        $types[':member_id'] = SQLITE3_INTEGER;
        $params[':nb_conversations'] = $nb_conversations;
        $types[':nb_conversations'] = SQLITE3_INTEGER;

        $get_conversations_statement = $everyone->execute_query($get_conversations_statement, $params, $types, false);

        if (isset($get_conversations_statement)) {


            generate_pagination($total_pages, $page, $location);

            $rows = array();

            echo '<div class="conversation-list">';

            while ($row = $get_conversations_statement->fetchArray(SQLITE3_ASSOC)) {

                echo '<a href="?location=conversation&user_id=' . encode_html($row["member_id"]) . '#bottom" class="conversation">'
                    . ' <div class="conversation-avatar">'
                    . '<img src="' . encode_avatar($row["avatar"]) . '" alt="avatar" width="50" height="50">'
                    . '</div>'
                    . '<div class="conversation-info">'
                    . '<h3>' . encode_html($row["username"]) . '</h3>'
                    . '<span class="conversation-time">' . encode_html(convert_date($row["message_timestamp"])) . '</span>'
                    . '</div>';
                if ($row["sender_id"] !== $_SESSION['member_id'] && $row["message_read"] !== "yes") {

                    echo '<img src="design/img/red_bubble.png" alt="Unread" class="conversation-read" height="20" width="20">';
                }
                echo '</a>';

            }
            echo '</div>';

            generate_pagination($total_pages, $page, $location);

        }

    } else {
        echo '<div class="center">';
        echo 'No conversation available.';
        echo '</div>';
    }

}

?>