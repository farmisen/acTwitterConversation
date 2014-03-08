<?php

/*
 * Returns an array of Tweets for a Twitter conversation.
 *
 * This was written due to the official Twitter API endpoint "related_results"
 * being depreciated in REST v1.1
 *
 * Written by Adrian Crepaz
 * http://adriancrepaz.com/twitter_conversions_api
 * https://github.com/adriancrepaz/acTwitterConversation
 *
 * Version: 0.1.1
 */


/*
 * Define the types of replies to return.
 */
 
define('CONVERSATE_BEFORE', 1);
define('CONVERSATE_AFTER', 2);
define('CONVERSATE_BOTH', 3); 


class acTwitterConversation {

	
	/*
	 * The tweetId of the conversion to fetch.
	 */
	 
	private $tweetId;

		
	/*
	 * The array of tweets pulled from the conversation.
	 */
	 
	private $tweets = array();
	
	
	/*
	 * DOMNodeList list of HTML replies.
	 */
	 
	private $replies;
	
	
	/*
	 * Store an instance of the DomDocument extension.
	 */
	 
	private $dom;
	
	
	/*
	 * Stores the raw HTML contents from the Twitter Conversation page.
	 */
	 
	private $html;
	
	
	/*
	 * Stores an error message, occured when parsing/retriveing data.
	 */
	
	private $error;
	
	
	/*
	 * Stores the type of data to be returned.
	 */
	 
	private $methodType;
	
	
	/*
	 * Current reply HTML, updated on each iteration.
	 */
	 
	private $currentReply;
	
	
	
	/*
	 * Sets up the DomDocument/libXML class.
	 */
	 
	public function __construct(){
		
		// Omit libxml errors, we don't have much control over HTML/formatting from Twitter.
		libxml_use_internal_errors(true);
		$this->dom = new DomDocument;
	}
	
	
	
	/*
	 * Attempt to fetch an array of a Tweets conversation.
	 */
	 
	public function fetchConversion($tweetId, $method = 'data', $conversate = CONVERSATE_BOTH){
		
		$this->html = null;
		$this->tweetId = $tweetId;
		$this->methodType = (is_string($method)) ? $method : get_class($method);

		
		/*
		 * We cannot continue if there was a problem retrieving or parsing Twitter data.
		 */
		 
		if($this->fetchDocument() === false){
		
			return array('error' => true, 'message' => $this->error, 'tweets' => array());
		}
		
		
		/*
		 * We have a valid response (HTML), continue to parse what we need.
		 */
		 
		if($this->replies->length > 0){		

			foreach($this->replies AS $node){
				
				
				// First off, fetch the basic details. TweetID and "State".
				$details = $this->fetchDetails($node);

				if($details === false) continue;
				
				
				/*
				 * Before parsing data, check if we need the tweet.
				 * $conversate can filter out certain types.
				 */
				 
				if($conversate == CONVERSATE_BEFORE AND $details['state'] == 'after'){
					
					// It's safe to break, tweets are in date/time order.
					// No-more tweets are required.
					break;
				}
				
				
				if($conversate == CONVERSATE_AFTER AND $details['state'] == 'before'){
					
					// Not required, skip to the next reply.
					continue;
				}
				
				
				switch($this->methodType){
					
					case 'data':

						// Tidy up the HTML node for parseData.
						$this->currentReply = $this->fetchInnerHtml($node);
						$details = array_merge($details, $this->parseData());
						
					break;


					/*
					 * Returns a list of tweets including data returned from the 
					 * Twitter GET: statuses/show endpoint.
					 *
					 * Data retrieved via the TwitterOAuth Class, by abraham.
					 * https://github.com/abraham/twitteroauth
					 */
	
					case 'TwitterOAuth':
						$details['tweet'] = $method->get('statuses/show', array('id' => $details['id']));
					break;
				}
				
				$this->tweets[] = $details;
			}
		}


		return array('error' => false, 'tweets' => $this->tweets);	
	}
	
	
	
	/*
	 * Attempt to fetch the HTML and load a DomDocument.
	 */
	 
	private function fetchDocument(){
		
		// The tweetId of the post must be must numeric.
		if(empty($this->tweetId) OR !is_numeric($this->tweetId)){			
			$this->error = 'Invalid tweetId specified.';
			return false;
		}
		
				
		/*
		 * Attempt to fetch the HTML from Twitter, using the IE6 user agent.
		 *
		 * I've tested this with blank, and really rare/obscure UA's and
		 * still receive the same markup. I image this is Twitters "fallback"
		 * markup for non-modern browsers.
		 */
		 
		$stream = stream_context_create(array(
			'http' => array(
    			'header'	=>	"Accept-language: en\r\nUser-Agent: MSIE 6.0\r\n"
    	)));
		
		$this->html = @file_get_contents('https://mobile.twitter.com/string/status/' . $this->tweetId, false, $stream);
		
		if($this->html === false){
			
			// If we pass an invalid tweetId, Twitter gives us a 404 header.
			if(strpos($http_response_header[0], '404 Not Found') !== false){
				$this->error = 'Tweet not found. It may have been deleted.';
			} else {
				$this->error = 'Unable to fetch conversation from Twitter.';
			}
			
			return false;
		}
		
		
		// Load the HTML into the DomDocument, and verify it's somewhat valid.
		if($this->dom->loadHTML($this->html) === false){
			$this->error = 'Unable to parse HTML response.';
			return false;
		}
		

		// Store the replies, if any, into our $replies variable.		
		$xpath = new DomXPath($this->dom);
		$this->replies = $xpath->query("//table[@class='tweet  ']");

		$this->tweetId = intval($this->tweetId);

		return true;
	}
	
	

	/*
	 * Fetches the tweetId and state of a tweet.
	 * A valid DOMElement must be specified.
	 */
	 
	private function fetchDetails(DOMElement $node){
			
		$href = $node->getAttribute('href');
		if(!empty($href)){
			
			$tweetId = null;
			$href = explode('/', trim($href, '/'));
			
			// Now using explode() as PHP < 5.3.0 doesn't support the $before_needle on strstr.
			if(!empty($href[2])){
				list($tweetId) = explode('?', $href[2]);
			}

			if(empty($tweetId) OR (!empty($tweetId) AND !is_numeric($tweetId))){
				return false;
			}
			
			return array(
				'id'		=> $tweetId,
				'state'		=> ((intval($tweetId) < $this->tweetId) ? 'before' : 'after')
			);		
		}
		
		return false;
	}
		
	

	/*
	 * Build an array of retreived elements.
	 */
	 
	public function parseData(){
		
		$thumbnail = $this->retrieve('image');
		
		return array(
	    	'username'		=> $this->retrieve('username'),
	    	'name'			=> $this->retrieve('name'),
	    	'content'		=> $this->retrieve('content'),
	    	'date'			=> $this->retrieve('date'),
	    	'images'		=> array( 
	    		'thumbnail'			=> $thumbnail,
	    		'large' 			=> str_replace('_normal.', '.', $thumbnail)
	    	)
	    );
	}	


	
	/*
	 * Retrieves and tidies up a node's HTML content.
	 */
	 
	private function fetchInnerHtml($node){

	    $innerHTML = ''; 
	    $children = $node->childNodes;
	    
	    foreach($children AS $child){ 
	        $innerHTML .= $child->ownerDocument->saveXML($child); 
	    } 
	
	    $innerHTML = preg_replace('/[ \t]+/', ' ', preg_replace('/[\r\n]+/', "\n", $innerHTML));
	    return str_replace("\n", '', $innerHTML);
	}

	
	
	/*
	 * Fetches an element from the reply HTML using regular expressions.
	 */
	
	private function retrieve($field){
		
		$expressions = array(
			'name'		 	=> '/(\<strong class\="fullname"\>)(.*?)(\<\/strong\>)/i',
			'username' 		=> '/(\<span class\="username"\> \<span\>@\<\/span\>)(.*?)(\<\/span\>)/i',
			'content'		=> '/(\<div class\="dir-ltr" dir\="ltr"\>)(.*?)(\<\/div\>)/i',
			'date'			=> '/(\<td class\="timestamp"\>)(.*?)(\<\/td\>)/i',
			'image'			=> '/(src\=")(.*?)("\/\>)/i'
		);
		
		if(!isset($expressions[$field])){
			return false;
		}
		
		
		// Attempt to match our required element.
		preg_match($expressions[$field], $this->currentReply, $matches, 0);
		return (!empty($matches[2])) ? trim(strip_tags($matches[2])) : false;
	}
	
	
	/*
	 * Returns a string of the HTML to be parsed.
	 * This is untouched by the class, and is direct from Twitter.
	 */
	 
	public function returnHTML(){
		return $this->html;
	}
}

?>