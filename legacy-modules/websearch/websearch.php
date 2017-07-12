<?php
/* Version 1.8 - last edited by DJPaul 05/02/06 09:47 GMT.
   websearch - use it like this: '!search exotic animals'. */

/* Yahoo Search API docs - http://developer.yahoo.net/search/web/V1/webSearch.html */
class WEBSEARCH {
	/* Used for 'shout back'

     ['nick']['url', 'title', 'summary'][1-10]
     ['nick']['original_query', 'original_channel'] - this person's last search results.
	*/
	private $_cachedSearches = array();	
  private $_delicious;	/* Reference to del.icio.us class */
  private $_bUseDelicious=false;  /* Do we use Delicious? */


	function __construct() {
		global $Modules;
		$Modules->add_module('WEBSEARCH', 'Searches Yahoo! for specified query', 'Web Search', '1.8', '');
    $this->_delicious = &$Modules->ar_Modules['DELICIOUS']['obj']; 
    if ($this->_delicious) {
	    $this->_bUseDelicious = true;
    }
	}

  /* recieves PRIVMSG events */	
	public function PRIVMSG($event) {
		global $DMBot;
    $nick = $event['privmsg']['nick'];
    $channel = $event['privmsg']['channel'];
    $msg = $event['privmsg']['msg'];
    $trigger = '!search ';
    $trigger_length = strlen($trigger);
    $BOLD = chr(2);
    $UNDERLINE = chr(31);

    /* Handle 'shout back' */
    if ($channel == 'PM' && substr($msg, 0, $trigger_length) != $trigger) {
	    if (isset($this->_cachedSearches[$nick]['url'][$msg])) {
        $tokens = array('nick' => $nick, 'query' => $this->_cachedSearches[$nick]['original_query']);
        $output = tokeniser::tokenise('websearch', 'SHOUTBACK_1', $tokens);
        $DMBot->PrivMsg($output, $this->_cachedSearches[$nick]['original_channel']);

        $tokens = array('title' => html_entity_decode($this->_cachedSearches[$nick]['title'][$msg]),
                        'summary' => html_entity_decode($this->_cachedSearches[$nick]['summary'][$msg]),
                        'url' => html_entity_decode($this->_cachedSearches[$nick]['url'][$msg]));
        $output = tokeniser::tokenise('websearch', 'SHOUTBACK_2', $tokens);
        $DMBot->PrivMsg($output, $this->_cachedSearches[$nick]['original_channel']);

        /* add to del.icio.us */
        if ($this->_bUseDelicious) {
          $this->_delicious->add($this->_cachedSearches[$nick]['title'][$msg], $this->_cachedSearches[$nick]['url'][$msg]);
        }

		    /* clear person's cache */
		    unset($this->_cachedSearches[$nick]);
	    }
		
    /* Handle !search requests */
    } else if (substr($msg, 0, $trigger_length) == $trigger) {
      $query = substr($msg, $trigger_length, strlen($msg)-$trigger_length);

      /* Queries and recieves the results from Yahoo */
      $xml = simplexml_load_file('http://api.search.yahoo.com/WebSearchService/V1/webSearch' . '?query=' . urlencode($query) . '&appid=dmbot_websearch&results=10');

      /* Parse the results */
      foreach ($xml->attributes() as $name => $attr) $res[$name] = $attr;

      /* Send results to the user */
      if ($res['totalResultsReturned'] > 0) {
        $output = tokeniser::tokenise('websearch', 'RESULTS_1', array('query' => $query));
        $DMBot->PrivMsg($output, $nick);

        /* each result here */
        for ($i=0; $i<$res['totalResultsReturned']; $i++) {
	        $tokens = array('number' => ($i+1),
	                        'title' => html_entity_decode($xml->Result[$i]->Title),
	                        'summary' => html_entity_decode($xml->Result[$i]->Summary),
	                        'url' => html_entity_decode($xml->Result[$i]->Url));
          $output = tokeniser::tokenise('websearch', 'RESULTS_2', $tokens);
          $DMBot->PrivMsg($output, $nick);

          /* Store the cache for 'shout back' */
          if ($channel != 'PM') {
            $this->_cachedSearches[$nick]['url'][($i+1)] = $xml->Result[$i]->Url;
            $this->_cachedSearches[$nick]['title'][($i+1)] = $xml->Result[$i]->Title;
            $this->_cachedSearches[$nick]['summary'][($i+1)] = $xml->Result[$i]->Summary;
          }
        }

        /* Explain 'shout back' */
        if ($channel != 'PM') {
	        $this->_cachedSearches[$nick]['original_channel'] = $channel;
	        $this->_cachedSearches[$nick]['original_query'] = $query;

          $output = tokeniser::tokenise('websearch', 'SHOUTBACK_3', array());
          $DMBot->PrivMsg($output, $nick);
        }
      } else {
        $output = tokeniser::tokenise('websearch', 'NO_RESULTS', array('query' => $query));
        $DMBot->PrivMsg($output, $nick);
      }
    }
  }
}
?>