<?php

require_once('./database_helper.php');

if(!isset($_GET["list_id"])){
require_once('./default.html');
die();
}

$list_id = $_GET["list_id"];
$quality = $_GET["quality"];

$list_title = '';
$API_CALL_INTERVAL = 86400; //in seconds, 86400 for 1 day

$arr = imdbFeedToArray('http://rss.imdb.com/list/'.$list_id.'/');

$array = array();
foreach($arr as $entry) {
	//First we check if we aready have this movie on db.
	$stored = get_torrent($entry['imdbID']);
	
	if($stored){ //We do! :)
		$stored['pubDate'] = $entry['pubDate'];
		$stored['title'] = $entry['title'];
		if(($stored['720p'] && $stored['1080p']) || ($stored['last_check_unixtime']>time()-$API_CALL_INTERVAL)){
			array_push($array, $stored);
			continue;
		}
	}
	
	//If not:
	$entry_info = getYify($entry['imdbID']);
	if((int)$entry_info['data']['movie_count'] < 1){
		store_torrent($entry['imdbID']);//Store the id only, so we call to check the API again only after a period of time.
		continue; //We could not find this movie on Yify.
	}
	else if((int)$entry_info['data']['movie_count'] > 1)continue; //We found more than one movie with the same id. WHAAAATTT? o_O
	
	//Great, we have only one result from yify api
	$temp_array = array('imdbID'=>$entry['imdbID'], 'pubDate'=>$entry['pubDate'], 'title'=>$entry['title']);
	foreach($entry_info['data']['movies'][0]['torrents'] as $torrent){
		$temp_array[$torrent['quality']] = $torrent['url'];
	}
	store_torrent($temp_array['imdbID'], $temp_array['720p'], $temp_array['1080p']);
	array_push($array, $temp_array);
}


//$array now holds all the info we need. We just need to show it to the user! (this is the part where it gets messy, sorry for that)
header("content-type: application/rss+xml; charset=utf-8");
$rss = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss></rss>');
$rss->addAttribute('version', '2.0');

// Cria o elemento <channel> dentro de <rss>
$canal = $rss->addChild('channel');
// Adiciona sub-elementos ao elemento <channel>
$canal->addChild('title', "IMDb's ".$list_title." to YIFY torrents");
$canal->addChild('link', "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
$canal->addChild('description', 'Automatically generate YIFY download links to the movies on your IMDB list');
foreach($array as $item) 
{
	if(!$item['720p'] && !$item['1080p'])continue;
	$title = $item['title'];
	$pubDate = $item['pubDate'];
	$link = ($quality == '1080' || $quality == '1080p')?$item['1080p']:$item['720p'];
	if(!$link)$link = ($quality == '1080' || $quality == '1080p')?$item['720p']:$item['1080p']; //Desired quality not found. Picking the other one... :(
	// Cria um elemento <item> dentro de <channel>
	$item = $canal->addChild('item');
	// Adiciona sub-elementos ao elemento <item>
	$item->addChild('title', $title);
	$item->addChild('link', $link);
	$item->addChild('guid', $link);
	$item->addChild('pubDate', $pubDate);
}
$dom = dom_import_simplexml($rss)->ownerDocument;
$dom->formatOutput = true;
echo $dom->saveXML();




function imdbFeedToArray($feed_url) {
	global $list_title;
	
	$response_code = get_http_response_code($feed_url);

	if($response_code != "200"){
		if($response_code == "404")die('Playlist not found.');
		die('Error '.$response_code);
	}else{
		$content = file_get_contents($feed_url);
	}
	
	$x = new SimpleXmlElement($content);
	$list_title = (string)$x->channel->title;
	$array = array();
	foreach($x->channel->item as $entry) {
		$arr = array(	'pubDate' => (string)$entry->pubDate,
				'title' => (string)$entry->title,
				'link' => (string)$entry->link,
				'imdbID' => (string)explode('/', $entry->link)[4]);
		array_push($array, $arr);
	}
	return $array;
	

}
function get_http_response_code($url) {
    $headers = get_headers($url);
    return substr($headers[0], 9, 3);
}

function getYify($id){
	error_log('Searching YIFI for new movie with ID '.$id);
	$content = file_get_contents('https://yts.ag/api/v2/list_movies.json?query_term='.$id.'&sort_by=seeds');
	$content = json_decode($content, true);
	return $content;
	if($content[data][movie_count] <1)return('lesser than 1');
	else if($content[data][movie_count] >1)return('higher than 1');
	return $content[data][movies][0];
}
