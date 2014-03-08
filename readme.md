acTwitterConversation
=========
A PHP Class to fetch Twitter Conversations

---------

With the release of Twitters REST v1.1 API's, they depreciated the functionality of retreving a public conversation of Tweets.
This class helps to solve that.


View full documentation at: http://adriancrepaz.com/twitter_conversions_api

-----


Example Request
---

```php
<?php

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

print_r($conversation);

?>
```

Example Response
---

```
Array
(
    [error] => false
    [tweets] => Array
        (
            [0] => Array
                (
                    [id] => 324214861728989184
                    [state] => after
                    [username] => michaelschultz
                    [name] => Michael Schultz
                    [content] => @facebook good April Fools joke Facebook….chat hasn’t changed. No new features.
                    [date] => 16 Apr
                    [images] => Array
                        (
                            [thumbnail] => https://pbs.twimg.com/profile_images/414193649073668096/dbIUerA8_normal.jpeg
                            [large] => https://pbs.twimg.com/profile_images/414193649073668096/dbIUerA8.jpeg
                        )

                )
            [1] = Array(
                ...
        )
)
```

----

## Options ##

Please see the [full documentation](http://adriancrepaz.com/twitter_conversions_api) at for a list of options, response types and notes.


--

Created by Adrian Crepaz - http://adriancrepaz.com - @adriancrepaz