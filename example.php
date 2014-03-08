<?php

/*
 * For full documentation, please visit
 * http://adriancrepaz.com/twitter_conversions_api
 */


// Require the acTwitterConversation class.
require_once './acTwitterConversation.php';


// Fetch a Tweet. (A tweet by Facebook, for example).
$tweetId = 324214451756728320;


// Optional.
// Fetch the 'data' fields, this includes essential tweet data.
$method = 'data';


// Optional.
// Only fetch replies for a conversation since $tweetId was made.
// Ignoring prior tweets in the thread.
$conversate = CONVERSATE_AFTER;


// Initiate the class, and fetch the conversation.
$twitter = new acTwitterConversation;
$conversation = $twitter->fetchConversion($tweetId, $method, $conversate);


header('Content-Type: text/html; charset=utf-8');
echo '<pre>' . print_r($conversation, true) . '</pre>';

?>