<?php
/* Version 1.4.
   Last updated 16/2/6 22:12 GMT by DJPaul.

   del.icio.us integration
   From a DMBot perspective, this does nothing on itself.  To use it in your components, do:

   0) Make sure your class doesn't get loaded before this one (see bug #0000125)

   1) Put this code in your class' constructor:
	 function __construct() {
		 global $Modules;
     $this->_delicious = &$Modules->ar_Modules['DELICIOUS']['obj']; 
   }

   2) Add links to del.icio.us with the add() method:
   $this->_delicious->add($title, $url);
*/	
class DELICIOUS {
	/* Gigantic array of stop words */
  private $_stopWords = array("a", "a's", "able", "about", "above", "according", "accordingly", "across", "actually", "after", "afterwards", "again", "against", "ain't", "all", "allow", "allows", "almost", "alone", "along", "already", "also", "although", "always", "am", "among", "amongst", "an", "and", "another", "any", "anybody", "anyhow", "anyone", "anything", "anyway", "anyways", "anywhere", "apart", "appear", "appreciate", "appropriate", "are", "aren't", "around", "as", "aside", "ask", "asking", "associated", "at", "available", "away", "awfully", "b", "be", "became", "because", "become", "becomes", "becoming", "been", "before", "beforehand", "behind", "being", "believe", "below", "beside", "besides", "best", "better", "between", "beyond", "both", "brief", "but", "by", "c", "c'mon", "c's", "came", "can", "can't", "cannot", "cant", "cause", "causes", "certain", "certainly", "changes", "clearly", "co", "com", "come", "comes", "concerning", "consequently", "consider", "considering", "contain", "containing", "contains", "corresponding", "could", "couldn't", "course", "currently", "d", "definitely", "described", "despite", "did", "didn't", "different", "do", "does", "doesn't", "doing", "don't", "done", "down", "downwards", "during", "e", "each", "edu", "eg", "eight", "either", "else", "elsewhere", "enough", "entirely", "especially", "et", "etc", "even", "ever", "every", "everybody", "everyone", "everything", "everywhere", "ex", "exactly", "example", "except", "f", "far", "few", "fifth", "first", "five", "followed", "following", "follows", "for", "former", "formerly", "forth", "four", "from", "further", "furthermore", "g", "get", "gets", "getting", "given", "gives", "go", "goes", "going", "gone", "got", "gotten", "greetings", "h", "had", "hadn't", "happens", "hardly", "has", "hasn't", "have", "haven't", "having", "he", "he's", "hello", "help", "hence", "her", "here", "here's", "hereafter", "hereby", "herein",
  "hereupon", "hers", "herself", "hi", "him", "himself", "his", "hither", "hopefully", "how", "howbeit", "however", "i", "i'd", "i'll", "i'm", "i've", "ie", "if", "ignored", "immediate", "in", "inasmuch", "inc", "indeed", "indicate", "indicated", "indicates", "inner", "insofar", "instead", "into", "inward", "is", "isn't", "it", "it'd", "it'll", "it's", "its", "itself", "j", "just", "k", "keep", "keeps", "kept", "know", "knows", "known", "l", "last", "lately", "later", "latter", "latterly", "least", "less", "lest", "let", "let's", "like", "liked", "likely", "little", "look", "looking", "looks", "ltd", "m", "mainly", "many", "may", "maybe", "me", "mean", "meanwhile", "merely", "might", "more", "moreover", "most", "mostly", "much", "must", "my", "myself", "n", "name", "namely", "nd", "near", "nearly", "necessary", "need", "needs", "neither", "never", "nevertheless", "new", "next", "nine", "no", "nobody", "non", "none", "noone", "nor", "normally", "not", "nothing", "novel", "now", "nowhere", "o", "obviously", "of", "off", "often", "oh", "ok", "okay", "old", "on", "once", "one", "ones", "only", "onto", "or", "other", "others", "otherwise", "ought", "our", "ours", "ourselves", "out", "outside", "over", "overall", "own", "p", "particular", "particularly", "per", "perhaps", "placed", "please", "plus", "possible", "presumably", "probably", "provides", "q", "que", "quite", "qv", "r", "rather", "rd", "re", "really", "reasonably", "regarding", "regardless", "regards", "relatively", "respectively", "right", "s", "said", "same", "saw", "say", "saying", "says", "second", "secondly", "see", "seeing", "seem", "seemed", "seeming", "seems", "seen", "self", "selves", "sensible", "sent", "serious", "seriously", "seven", "several", "shall", "she", "should", "shouldn't", "since", "six", "so", "some", "somebody", "somehow", "someone", "something", "sometime", "sometimes", "somewhat", "somewhere", "soon", "sorry", "specified",
  "specify", "specifying", "still", "sub", "such", "sup", "sure", "t", "t's", "take", "taken", "tell", "tends", "th", "than", "thank", "thanks", "thanx", "that", "that's", "thats", "the", "their", "theirs", "them", "themselves", "then", "thence", "there", "there's", "thereafter", "thereby", "therefore", "therein", "theres", "thereupon", "these", "they", "they'd", "they'll", "they're", "they've", "think", "third", "this", "thorough", "thoroughly", "those", "though", "three", "through", "throughout", "thru", "thus", "to", "together", "too", "took", "toward", "towards", "tried", "tries", "truly", "try", "trying", "twice", "two", "u", "un", "under", "unfortunately", "unless", "unlikely", "until", "unto", "up", "upon", "us", "use", "used", "useful", "uses", "using", "usually", "v", "value", "various", "very", "via", "viz", "vs", "w", "want", "wants", "was", "wasn't", "way", "we", "we'd", "we'll", "we're", "we've", "welcome", "well", "went", "were", "weren't", "what", "what's", "whatever", "when", "whence", "whenever", "where", "where's", "whereafter", "whereas", "whereby", "wherein", "whereupon", "wherever", "whether", "which", "while", "whither", "who", "who's", "whoever", "whole", "whom", "whose", "why", "will", "willing", "wish", "with", "within", "without", "won't", "wonder", "would", "would", "wouldn't", "x", "y", "yes", "yet", "you", "you'd", "you'll", "you're", "you've", "your", "yours", "yourself", "yourselves", "z", "zero");

  /* Timer variable */
  private $_last_time;

  /* Cached delicious instructions */
  private $_cachedDelicious = array();


	function __construct() {
		global $Modules;
		$Modules->add_module('DELICIOUS', 'DELICIOUS component', 'DELICIOUS component', '1.5', '');
	}

	/* returns: del.icio.us API path with user login attached */
	protected function _getURL() {
		$user = Config::GetValue('user', 0, 'delicious');
		$pass = Config::GetValue('pass', 0, 'delicious');
		return "http://" . $user . ":" . $pass . "@del.icio.us/api/";
	}
	
	/* adds link to del.icio.us account */
	public function add($title, $url, $optional_tags=array()) {
		$bEnabled = Config::GetValue('enabled', 0, 'delicious');

		if (!$bEnabled) {
			return;
		}

    /* Prepare data */
		$path = $this->_getURL();
		$pattern = '/[^0-9a-z\s]+/i';
		$title = preg_replace($pattern, '', $title);
		$title = urlencode($title);
		$url = urlencode($url);
		$tags = '';

    if ($optional_tags != array()) {
	    /* Optinal argument tags specified - use them */
		  for ($i=0; $i<count($optional_tags); ++$i) {
	  		$tags .= "%20" . htmlentities($optional_tags[$i]);
  		}	
    } else {
      /* Generate tags from the title */
		  $exploded_title = explode(' ', $title);
		  $exploded_tags = array_diff($exploded_title, $this->_stopWords);
		  for ($i=0; $i<count($exploded_tags); ++$i) {
	  		$tags .= "%20" . htmlentities($exploded_tags[$i]);
  		}
	  }

    /* Send instructions to delicious */
		$this->_cachedDelicious[] = $path . "posts/add?description=" . $title . "&url=" . $url . "&tags=" . $tags;
	}
	
	/* recieves TIMER events (non-IRC event) */
	public function TIMER($event) {
		$time = microtime(true);

		if ($time - $this->_last_time > 1.5) {
			if (count($this->_cachedDelicious) > 0) { 
			  /* send the command */
		  	$handle = fopen(array_shift($this->_cachedDelicious), 'r');
	  	  $this->_last_time = $time;
  	  }
		}
	}
}
?>