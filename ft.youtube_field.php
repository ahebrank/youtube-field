<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once('lib/youtube.class.php');

class Youtube_field_ft extends EE_Fieldtype {

  var $info = array(
    'name'      => 'Youtube Field',
    'version'   => '0.1'
  );

  function __construct()
  {
    ee()->lang->loadfile('youtube_field');
    parent::__construct();
  }

  function install()
  {
    return array(
      'youtube_id'                => '',
      'cache_expire_hours'        => 24*7,
      'api_key'                   => '',
    );
  }

  /**
   * global settings for the field instance set by installer
   */
  function display_global_settings()
  {
    $val = array_merge($this->settings, $_POST);

    $form =  '<p>' . form_label('API Key', 'api_key').form_input('api_key', $val['api_key']) . '</p>';
    $form .= '<p>' . form_label('Stats cache expire time (hours)', 'cache_expire_hours').form_input('cache_expire_hours', $val['cache_expire_hours']) . '</p>';


    return $form;
  }

  function save_global_settings()
  {
    return array_merge($this->settings, $_POST);
  }

  /**
   * individual field settings on the publish page
   * @param  [type] $data [description]
   * @return [type]       [description]
   */
  function display_field($data)
  {
    $e = explode('|', $data);
    if (count($e) != 5) {
      $e = array('', '', '', '', '');
    }
    list($youtube_id, $cache_date, $title, $views, $since) = $e;

    $ret = form_input(array(
      'name'  => $this->field_name,
      'id'    => $this->field_id,
      'value' => $youtube_id,
      'class' => 'youtube-field-id',
    ));
    
    $ret .= '<p>'.lang('youtube_field_help').'</p>';

    $ret .= '<div class="youtube-thumb" style="margin-top: 20px;"></div>';

    // inject the JS to do the preview render
    $js_inject =
<<<EOD
<script>
YoutubeInit();
function YoutubeInit() {
  \$('.youtube-field-id').on('change', function(e) {
    YoutubeUpdatePreview(\$(this));
  });
  \$('.youtube-field-id').each(function() {
    YoutubeUpdatePreview(\$(this));
  });
}
function YoutubeUpdatePreview(\$el) {
  var val = \$el.val();
  if (val) {
    var last = val.split('/').slice(-1).pop();
    var id = last.replace('watch?v=', '');
    var src = '//img.youtube.com/vi/' + id + '/0.jpg';
    \$el.parent().find('.youtube-thumb').html('<img width="100px" src="' + src + '" alt="Preview">');
  }
}
</script>
EOD;
    ee()->cp->add_to_foot($js_inject);

    return $ret;
  }

  function save($data)
  {
    $y = new Youtube($this->settings['api_key'], $data);

    // grab some stats
    list($views) = $y->stats();
    list($title, $since) = $y->snippet();
    
    $data = implode('|', array($y->id, date('U'), $title, $views, $since));

    return $data;
  }

  /**
   * tag rendering
   * @param  [type]  $data    [description]
   * @param  array   $params  [description]
   * @param  boolean $tagdata [description]
   * @return [type]           [description]
   */
  function replace_tag($data, $params = array(), $tagdata = FALSE)
  {
    $this->_cache_refresh();

    $e = explode('|', $data);
    if (count($e) != 5) {
      $e = array('', '', '', '', '');
    }
    list($youtube_id, $cache_date, $title, $views, $since) = $e;
    $y = new Youtube($this->settings['api_key'], $youtube_id);

    $thumbnail_index = (isset($params['thumbnail']))? $params['thumbnail']:'0';
    $link = $this->_make_thumbnail(
      $y->thumbnail_src($thumbnail_index),
      $y->embed(),
      $y->watch_url(),
      $title,
      $views,
      $since,
      $params
    );

    return $link;
  }

  /**
   * batch processing -- find any update any fields with expired cache counts
   * pseudo-cron triggered (since I'm a field) by a render event
   * @return [type] [description]
   */
  private function _cache_refresh() {
    // just update for this field_id
    $field_id = 'field_id_' . $this->field_name;
    $instances = ee()->db->where($field_id . " != ''")
      ->get('channel_data');
    foreach ($instances->result() as $f) {
      list($youtube_id, $cache_date, $views, $since) = explode('|', $f->$field_id);
      if ( (date('U') - $cache_date) > ($this->settings['cache_expire_hours'] * 3600) ) {
        $this->_save_field_data($field_id, $f->entry_id, $youtube_id);
      }
    }
  }

  /**
   * Manually updates field with new video stats
   * @param  [type] $column     [description]
   * @param  [type] $entry_id   [description]
   * @param  [type] $youtube_id [description]
   * @return [type]             [description]
   */
  private function _save_field_data($column, $entry_id, $youtube_id) {
    // stats cache has expired, update the values for the field
    $y = new Youtube($this->settings['api_key'], $youtube_id);
    list($views) = $y->stats();
    list($title, $since) = $y->snippet();

    // save the field
    $data = implode('|', array($youtube_id, date('U'), $title, $views, $since));
    $val = array($column => $data);

    return ee()->db->where('entry_id', $entry_id)
      ->update('channel_data', $val);
  }

  /**
   * make a thumbnail based on render params
   * @param  [type] $params [description]
   * @return [type]         [description]
   */
  private function _make_thumbnail($img_src, $embed_iframe, $watch_url, $title, $views, $since_upload, $params) {
    
    $reveal_id = null;
    $popup = '';

    if (isset($params['reveal']) && $params['reveal'] == 'yes') {
      // use a foundation reveal for the link
      $reveal_id = 'youtube-field-popup-' . uniqid();
      $link = '<a href="#" data-reveal-id="' . $reveal_id .'">';
    }
    else {
      $link = '<a href="' . $watch_url . '">';
    }

    $thumbnail = '<img src="' . $img_src . '" alt="Thumbnail for youtube video">';

    $stats = '';
    if (isset($params['stats']) && $params['stats'] == 'yes') {
      if ($views || $since_upload) {
        $stats = '<div class="youtube-field-stats"><ul>';
        if ($views) {
          $stats .= '<li><span class="youtube-field-views">' . $views . ' views</span></li>';
        }
        if ($since_upload) {
          $stats .= '<li><span class="youtube-field-time-since-upload">' . $since_upload . '</span></li>';
        }
        $stats .= '</ul></div>';
      }
    }

    $caption = '';
    // show a caption by default, but it can be turned off
    if (!isset($params['caption']) || $params['caption'] == 'yes') {
      $caption = '<div class="youtube-field-title">' . $link . '<span class="youtube-field-title">' . $title . '</span></a>' . $stats . '</div>';
    }

    // popup markup, if applicable
    if (!is_null($reveal_id)) {
      if ($caption) {
        $popup_link = '<a href="' . $watch_url . '">';
        $popup_caption = '<div class="youtube-field-title">' . $popup_link . '<span class="youtube-field-title">' . $title . '</span></a>' . $stats . '</div>';
      }
      $popup_close = '<a class="close-reveal-modal" href="#" data-reveal-close>✕</a>';
      $popup = '<div id="' . $reveal_id . '" class="youtube-field-popup reveal-modal" data-reveal area-hidden="true" role="dialog">'
          . $popup_close . $embed_iframe . $popup_caption . '</div>';
    }

    if (isset($params['embed']) && $params['embed'] == 'yes') {
      return '<div class="youtube-field-wrapper">' . $embed_iframe . $caption . '</div>';
    }
    else {
      return '<div class="youtube-field-wrapper">' . $link . $thumbnail . '</a>' . $caption . '</div>' . $popup;
    }
  }
}
