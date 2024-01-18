<?php

date_default_timezone_set('Europe/Paris');
session_set_cookie_params(10800);
session_start();

$db = new SQLite3("config/db.sqlite");

if (!isset($site_gears)) {

  $site_data = $db->prepare('SELECT name, description, keywords, footer, nb_topics, nb_posts, nb_user_posts, nb_conversations, nb_conversations_messages, registration_case FROM site');
  $site_data = $site_data->execute()->fetchArray(SQLITE3_ASSOC);

  $site_name = $site_data["name"];

  $site_description = $site_data["description"];

  $site_keywords = $site_data["keywords"];

  $site_footer = $site_data["footer"];

  $nb_topics = $site_data["nb_topics"];

  $nb_posts = $site_data["nb_posts"];

  $nb_user_posts = $site_data["nb_user_posts"];

  $nb_conversations = $site_data["nb_conversations"];

  $nb_conversations_messages = $site_data["nb_conversations_messages"];

  $registration_case = $site_data["registration_case"];

  $site_gears = true;

}



?>