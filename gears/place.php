<?php

if (isset($page)) {

  if(is_logged_in()){
    $user->new_topic($token);
  }

  if (!is_null($search)) {
    $count_topics_statement = 'SELECT COUNT(*) FROM topics WHERE topic_name LIKE :search';
    $params[':search'] = '%' . $search . '%';
    $types[':search'] = SQLITE3_TEXT;
  } else {
    $count_topics_statement = 'SELECT COUNT(*) FROM topics';
    $params = $types = [];
  }

  $count_topics_statement = $everyone->execute_query($count_topics_statement, $params, $types, false)->fetchArray(SQLITE3_NUM);

  if ($count_topics_statement && $count_topics_statement[0] > 0) {

    $pages_sum = ceil($count_topics_statement[0] / $nb_topics);
    $total_pages = $pages_sum - 1;

    if ($page > $total_pages) {
      $page = $total_pages;
    }

    $offset = $page * $nb_topics;

    if (!is_null($search)) {

      $get_topics_by_date_statement = 'SELECT topics.*, members.member_id, members.username 
                                         FROM topics 
                                         INNER JOIN members 
                                         ON topics.member_creator_id = members.member_id 
                                         WHERE topics.topic_name 
                                         LIKE :search
                                         ORDER BY topics.topic_last_post_timestamp DESC 
                                         LIMIT :nb_topics OFFSET :offset';

      $params = [':search' => '%' . $search . '%']; $types = [':search' => SQLITE3_TEXT];

    } else {
      $get_topics_by_date_statement = 'SELECT topics.*, members.member_id, members.username 
                                         FROM topics 
                                         INNER JOIN members 
                                         ON topics.member_creator_id = members.member_id 
                                         ORDER BY topics.pinned DESC, topics.topic_last_post_timestamp DESC 
                                         LIMIT :nb_topics OFFSET :offset';
    }


    $params[':nb_topics'] = $nb_topics; $types[':nb_topics'] = SQLITE3_INTEGER;
    $params[':offset'] = $offset; $types[':offset'] = SQLITE3_INTEGER;

    $get_topics_by_date_statement = $everyone->execute_query($get_topics_by_date_statement, $params, $types, false);

    if (isset($get_topics_by_date_statement)) {

      if (!is_null($search)) {
        echo '<div class="center"><p>Search for : ' . encode_html($search) . '</p></div>';
      }

      generate_pagination($total_pages, $page, $location, array('search' => $search));

      echo '<div class="forum_card">';
      while ($row = $get_topics_by_date_statement->fetchArray(SQLITE3_ASSOC)) {
        echo '<div class="topic">'
          . '<div class="title">'
          . '<h2><a href="?location=answers&id=' . encode_html($row['topic_id']) . '">' . (isset($row['pinned']) && $row['pinned'] == "yes" ? '<img src="design/img/pinned.png"/> ' : '') . (isset($row['locked']) && $row['locked'] == "yes" ? '<img src="design/img/locked.png"/> ' : '') . encode_html($row['topic_name']) . '</a></h2>'
          . '</div>'
          . '<small>Created by <a href="?location=profile&user=' . encode_html($row['username']) . '">' . encode_html($row['username']) . '</a> and last reply at <a href="?location=user_answers&answer_id=' . encode_html($row['topic_last_post_id']) . '">' . encode_html(convert_date($row['topic_last_post_timestamp'])) . '</a> by <a href="?location=profile&user=' . encode_html($row['topic_last_post_username']) . '">' . encode_html($row['topic_last_post_username']) . '</a></small>'
          . '<div class="stats">'
          . '<span>' . encode_html($row['posts_number']) . ' replie(s) </span>'
          . '</div>'
          . '</div>';
      }
      echo '</div>';

      generate_pagination($total_pages, $page, $location, array('search' => $search));

    }

  } else {

    if (!is_null($search)) {
      echo '<div class="center"><p>Search for : ' . encode_html($search) . '</p></div>';
    }

    echo '<div class="center"><p>No results found.</p></div>';
  }

  if (is_logged_in() && is_null($search)) {
    
    echo '<div class="forum_card">'
    . '<div class="new-form">'
      . '<h2>Enter the content of your topic</h2>'
      . '<form action="" method="POST">'
        . '<input maxlength="130" type="text" name="new_topic_name" placeholder="Enter topic title here.">'
        . '<textarea maxlength="30000" name="new_topic_post_data" type="text" placeholder="Please respect the rules in force on the site before posting, you can also use BBCode here like: [center][/center], [color=][/color], [b][/b], [i][/i], [code][/code], [url][/url], [url=][/url], [img][/img] or put an @ before the username."></textarea>'
        . '<input type="hidden" name="token" value="'
        . encode_html(sha1(session_id()))
        . '">'
        . '<input type="submit" name="new_topic" value="Send">'
      . '</form>'
    . '</div>'
  . '</div>';

  }

}


?>
