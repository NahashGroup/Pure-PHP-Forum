<?php

function AntiInjectionSQL()
{
    $injection = 'INSERT|UNION|SELECT|NULL|COUNT|FROM|LIKE|DROP|TABLE|WHERE|COUNT|COLUMN|TABLES|INFORMATION_SCHEMA|OR|UPDATE|TRUNCATE|DELETE';
    foreach ($_GET as $getSearchs) {
        $getSearch = explode(" ", $getSearchs);
        foreach ($getSearch as $k => $v) {
            if (in_array(strtoupper(trim($v)), explode('|', $injection))) {
                die();
            }
        }
    }
}

function encode_html($str)
{
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

function decode_html($str)
{
    return html_entity_decode($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function convert_date($date)
{
    $date = DateTime::createFromFormat('Y-m-d H:i:s', $date);
    return $date->format('d-m-Y H:i:s');
}


function check_email_or_jabber($address)
{
    $xmpp_regex = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
    if (filter_var($address, FILTER_VALIDATE_EMAIL) || preg_match($xmpp_regex, $address)) {
        return true;
    }
    return false;
}

function validate_post($post, $min_length, $max_length)
{

    if (!is_string($post) || !mb_check_encoding($post, 'UTF-8')) {
        return false;
    }

    $post = preg_replace('/\p{C}+/u', '', $post);
    $post = stripslashes($post);
    $post = trim($post);

    $length = mb_strlen($post, 'UTF-8');
    if ($length < $min_length || $length > $max_length) {
        return false;
    }

    return true;
}

function validate_numeric_string($value, $min_length, $max_length)
{
    return ctype_digit($value) && strlen($value) >= $min_length && strlen($value) <= $max_length;
}

function validate_numeric_value($value, $min_value, $max_value)
{
    return is_numeric($value) && $value >= $min_value && $value <= $max_value;
}


function is_logged_in()
{
    return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
}

function convert_role($role)
{
    $roles_list = array(
        "member" => "Member",
        "moderator" => "<span style='color: green;'>Moderator</span>",
        "admin" => "<span style='color: orange;'>Administrator</span>",
        "super-admin" => "<span style='color: red;'>Super-Administrator</span>"
    );
    if (array_key_exists($role, $roles_list)) {
        return "<small>" . $roles_list[$role] . "</small>";
    }
}

function encode_avatar($avatar)
{
    if (!is_null($avatar)) {
        $avatar_blob = $avatar;
        $avatar_data = base64_encode($avatar_blob);
        $avatar_filtred = 'data:image/gif;base64,' . encode_html($avatar_data);
    } else {
        $avatar_filtred = "design/img/no_avatar.png";
    }
    return $avatar_filtred;
}


function error_or_success_popup($type, $text, $redirect)
{
    if ($type === "error") {
        $_SESSION['error_log'] = $text;
        if ($redirect !== false) {
            header("Location: $redirect#popup_error");
            die();
        } else {
            header("Location: #popup_error");
            die();
        }
    }
    if ($type === "success") {
        $_SESSION['success_log'] = $text;
        if ($redirect !== false) {
            header("Location: $redirect#popup_success");
            die();
        } else {
            header("Location: #popup_success");
            die();
        }

    }
}

function processQuotesRecursively($text)
{
    $quotePattern = "/\[quote=(.*?)\](.*?)\[\/quote\]/is";

    while (preg_match($quotePattern, $text, $matches)) {
        $author = encode_html($matches[1]);
        $quoted_content = processQuotesRecursively($matches[2]);

        $nestedQuoteBlock = "<blockquote class='quote'>Quote from $author : $quoted_content</blockquote>";

        $text = preg_replace($quotePattern, $nestedQuoteBlock, $text, 1);
    }

    return $text;
}
function bbcode($text)
{
    // [quote=][/quote]
    $text = processQuotesRecursively($text);

    // [center][/center]
    $text = preg_replace("/\[center\](.*?)\[\/center\]/is", "<p class='centered-text'>$1</p>", $text);

    // [color=x][/color]
    $text = preg_replace_callback("/\[color=([a-zA-Z]+)\]/", function ($matches) {
        $color = preg_replace("/[^a-zA-Z0-9#]/", "", $matches[1]);
        return "<span style='color:{$color}'>";
    }, $text);
    $text = preg_replace("/\[\/color\]/is", "</span>", $text);

    // [b][/b]
    $text = preg_replace("/\[b\](.*?)\[\/b\]/is", "<strong>$1</strong>", $text);

    // [i][/i]
    $text = preg_replace("/\[i\](.*?)\[\/i\]/is", "<em>$1</em>", $text);

    // [code][/code]
    $text = preg_replace("/\[code\](.*?)\[\/code\]/is", "<code>$1</code>", $text);

    // [url][/url]
    $text = preg_replace_callback("/\[url\](.*?)\[\/url\]/is", function ($matches) {
        $url = encode_html($matches[1]);
        $url = str_replace('&amp;', '&', $url);
        return "<a href='$url' target='_blank'>$url</a>";
    }, $text);

    // [url=]text[/url]
    $text = preg_replace_callback("/\[url=(.*?)\](.*?)\[\/url\]/is", function ($matches) {
        $url = encode_html($matches[1]);
        $text = encode_html($matches[2]);
        return "<a href='$url' target='_blank'>$text</a>";
    }, $text);

    // [img][/img]
    $text = preg_replace_callback("/\[img\](.*?)\[\/img\]/is", function ($matches) {
        $img = encode_html($matches[1]);
        return "<img src='$img' style='max-width: 30%; height: auto;' />";
    }, $text);

    // @usertag
    $text = preg_replace_callback("/(?<!\w)@(\w{1,20})(?!\w|[:>])/i", function ($matches) {
        $username = encode_html($matches[1]);
        $link = "?location=profile&user=" . urlencode($username);
        return "<a href='$link' target='_blank'><span style='pointer-events: none;'>@$username</span></a>";
    }, $text);

    $text = nl2br($text);

    return $text;
}


function count_message_replies($post_id)
{
    global $posts;
    $count = 1;
    $post = $posts[$post_id];
    foreach ($post['replies'] as $reply_id) {
        $count += count_message_replies($reply_id);
    }
    return $count;
}

function display_message($post_display_id, $indent_level = 0)
{
    global $page;
    global $posts;
    global $get_topic_id_and_title_and_pinned_and_locked_statement;
    $post = $posts[$post_display_id];

    echo '<div class="message" style="margin-left: ' . ($indent_level * 2) . 'px;">'
        . '<div class="message-header">'
        . '<div class="username-container">'
        . '<a href="?location=profile&user=' . encode_html($post['username']) . '"><span class="username">' . encode_html($post['username']) . '</span></a>'
        . '<span class="username-text">' . convert_role($post['role']) . '</span>'
        . '</div>'
        . '<div class="moderation-container">'
        . '<a href="?location=user_answers&answer_id=' . encode_html($post['post_id']) . '"><span class="timestamp">' . encode_html(convert_date($post['post_timestamp_creation'])) . '</span></a>';

    if (check_permissions("delete_post_by_id")) {
        echo '<span class="moderation-buttons">'
            . '<form action="" method="post">'
            . '<input type="hidden" value="' . encode_html($post['post_id']) . '" name="post_id">'
            . '<input type="hidden" name="token" value="' . encode_html(sha1(session_id())) . '">'
            . '<button type="submit" name="delete_post_by_id"><img src="design/img/delete.png"></button>'
            . '</form>'
            . '</span>';
    }

    echo '</div>'
        . '</div>'
        . '<div class="message-body">'
        . '<img src="' . encode_avatar($post['avatar']) . '" class="message-body-avatar" alt="avatar">'
        . '<p>' . bbcode(encode_html($post['post_data'])) . '</p>';

    if (!isset($post['user_answers']) && is_logged_in() && ((!isset($get_topic_id_and_title_and_pinned_and_locked_statement["locked"]) || $get_topic_id_and_title_and_pinned_and_locked_statement["locked"] != "yes") || check_permissions("full_access"))) {
        echo '<a href="?location=answers&id=' . encode_html($post['topic_id']) . '&page=' . encode_html($page) . '&targeted_post=' . encode_html($post['post_id']) . '" class="clickable">Quote</a>';
    }
    if (isset($post['user_answers'])) {
        echo '<a href="?location=answers&id=' . encode_html($post['topic_id']) . '" class="clickable">View topic</a>';
    }
    echo '</div>';

    /*
    $messages_count = count_message_replies($post_display_id);

    if ($messages_count > 1 && $indent_level === 0) {
        echo '<details open=""><summary>See ' . ($messages_count - 1) . ' response(s)</summary><div class="replies">';
        foreach ($post['replies'] as $reply_id) {
            display_message($reply_id, $indent_level + 1);
        }
        echo '</div></details>';
    } else {
        foreach ($post['replies'] as $reply_id) {
            display_message($reply_id, $indent_level + 1);
        }
    }
    */

    echo '</div>';
}


function check_permissions($action)
{

    $roles = array(
        "member" => array(
        ),
        "moderator" => array(
            "full_access",
            "control_panel",
            "delete_post_by_id",
            "delete_topic_by_id",
            "lock_or_unlock_topic_by_id",
            "mute_or_unmute_user_by_username"
        ),
        "admin" => array(
            "full_access",
            "control_panel",
            "logs",
            "show_users",
            "delete_post_by_id",
            "delete_topic_by_id",
            "pin_or_unpin_topic_by_id",
            "lock_or_unlock_topic_by_id",
            "ban_or_unban_user_by_username",
            "mute_or_unmute_user_by_username",
            "delete_all_user_posts_by_username",
            "enable_or_disable_registration_case",
            "blacklisting"
        ),
        "super-admin" => array(
            "full_access",
            "control_panel",
            "modify_site_data",
            "logs",
            "show_users",
            "delete_post_by_id",
            "delete_topic_by_id",
            "pin_or_unpin_topic_by_id",
            "lock_or_unlock_topic_by_id",
            "ban_or_unban_user_by_username",
            "mute_or_unmute_user_by_username",
            "delete_all_user_posts_by_username",
            "refresh_database",
            "delete_all_private_messages",
            "grant_privileges_by_username",
            "enable_or_disable_registration_case",
            "blacklisting"
        )
    );

    if (!isset($_SESSION['role']) || !array_key_exists($_SESSION['role'], $roles)) {
        return false;
    }

    if (!in_array($action, $roles[$_SESSION['role']])) {
        return false;
    }

    return true;

}


function generate_pagination($total_pages, $page, $location = '', $variables = array())
{
    $range = 1;
    $query_string = '';

    if (!empty($variables)) {
        foreach ($variables as $key => $value) {
            if (!empty($value)) {
                $query_string .= urlencode($key) . '=' . urlencode($value) . '&';
            }
        }
    }

    echo '<div class="data-pagination-container">';
    echo '<nav data-pagination>';
    echo '<a href="' . (encode_html($location) ? '?location=' . encode_html($location) . '&' : '?') . encode_html($query_string) . 'page=' . ($page == 0 ? 0 : $page - 1) . '" ' . ($page <= 0 ? 'disabled' : '') . '><img src="design/img/left.png"></i></a>';
    echo '<ul>';
    echo '<li' . ($page == 0 ? ' class="current"' : '') . '><a href="' . (encode_html($location) ? '?location=' . encode_html($location) . '&' : '?') . encode_html($query_string) . 'page=0">0</a></li>';
    if ($page - $range > 0 && $page != 1) {
        echo '<li><a href="#">...</a></li>';
    }

    for ($i = max(1, $page - $range); $i <= min($total_pages, $page + $range); $i++) {
        if ($i == $page) {
            echo '<li class="current"><a href="#">' . $i . '</a></li>';
        } else {
            echo '<li><a href="' . (encode_html($location) ? '?location=' . encode_html($location) . '&' : '?') . encode_html($query_string) . 'page=' . $i . '">' . $i . '</a></li>';
        }
    }
    if ($page + $range < $total_pages) {
        echo '<li><a href="#">...</a></li>';
        echo '<li><a href="' . (encode_html($location) ? '?location=' . encode_html($location) . '&' : '?') . encode_html($query_string) . 'page=' . $total_pages . '">' . $total_pages . '</a></li>';
    }
    echo '</ul>';
    echo '<a href="' . (encode_html($location) ? '?location=' . encode_html($location) . '&' : '?') . encode_html($query_string) . 'page=' . ($page == $total_pages ? $page : $page + 1) . '" ' . ($page == $total_pages ? 'disabled' : '') . '><img src="design/img/right.png"></i></a>';
    echo '</nav>';
    echo '</div>';
}



?>