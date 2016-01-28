<?php

class Youtube {

  var $api_key = null;
  var $base_url = '';
  public $id = null;

  function __construct($api_key, $id) {
    $this->id = $this->get_id($id);
    $this->api_key = $api_key;

    $this->base_url = 'https://www.googleapis.com/youtube/v3/videos?id=' . $this->id . '&key=' . $this->api_key;
  }

  /**
   * get an ID from an ID or URL
   * @param  [type] $url [description]
   * @return [type]      [description]
   */
  function get_id($url) {
    $e = explode('/', $url);
    $last = end($e);
    $id = str_replace('watch?v=', '', $last);

    return $id;
  }

  /**
   * return a URL to a thumbnail
   * @param  [type] $id [description]
   * @return [type]     [description]
   */
  function thumbnail_src($index = 0) {
    return '//img.youtube.com/vi/' . $this->id . '/' . $index .'.jpg';
  }

  /**
   * just the regular watch page
   * @return [type] [description]
   */
  function watch_url() {
    return 'https://www.youtube.com/watch?v=' . $this->id;
  }

  /**
   * youtube embed iframe wrapped in a div
   * @return [type] [description]
   */
  function embed() {
    return '<div class="youtube-field-embed-wrapper"><iframe width="640" height="360" src="https://www.youtube-nocookie.com/embed/' 
      . $this->id 
      . '?rel=0&controls=0&showinfo=0&enablejsapi=1" frameborder="0" allowfullscreen></iframe></div>';
  }


  /**
   * return views stats
   * @return [type] number of views
   */
  function stats() {
    $url = $this->base_url . '&part=statistics';
    $stats = $this->fetch_json($url);
    if (!isset($stats->items)) {
      return false;
    }

    return array($stats->items[0]->statistics->viewCount);
  }

  /**
   * return some sort of reasonable 'since' date
   * from http://stackoverflow.com/questions/2915864/php-how-to-find-the-time-elapsed-since-a-date-time
   * @param  [type] $time [description]
   * @return [type]       [description]
   */
  function since_human($time) {
    $time = time() - $time;
    $time = ($time<1)? 1 : $time;
    $tokens = array (
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute',
        1 => 'second'
    );

    foreach ($tokens as $unit => $text) {
      if ($time < $unit) continue;
      $n = floor($time / $unit);
      return $n . ' ' . $text . (($n>1)? 's':'') . ' ago';
    }
  }

  /**
   * return the time since upload as a pretty string date
   * @return [type] [description]
   */
  function snippet() {
    $url = $this->base_url . '&part=snippet';
    $stats = $this->fetch_json($url);

    if (!isset($stats->items)) {
      return false;
    }
    $time = strtotime($stats->items[0]->snippet->publishedAt);
    $title = $stats->items[0]->snippet->title;
    return array($title, $this->since_human($time));
  }

  /**
   * simple curl fetch
   * @param  [type] $url [description]
   * @return [type]      [description]
   */
  function fetch_json($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);

    return json_decode($result);
  }


}