# YouTube field for Expression Engine 2

A fieldtype for specifying a YouTube video by URL or video ID and then rendering it in a few different ways.  Currently customized for a client project so there's room for improving its flexibility.  PRs are welcome!

## Setup

```
cd system/expressionengine/third_party
git clone https://github.com/ahebrank/youtube-field.git
```

Go to Add Ons -> Fieldtypes and install Youtube Field.  Then click on the Youtube Field link to set the API Key and the cache interval.

A [v3 API key](https://developers.google.com/youtube/v3/getting-started) is required to show the publish date and viewcount.  These are publicly available data so only the key value is needed, not the full oauth2 authorization.

## Templating examples

The examples below assume a channel entry loop, where `video_field_name` is the name of the field.

Show an embedded video in place, with title and viewcount:

```
{video_field_name embed="yes" caption="yes" stats="yes"}
```


Thumbnail with link out to YouTube watch page:

```
{video_field_name caption="yes"}
```


Show the thumbnail with hidden modal, so that embedded video pops up when clicked:

```
{video_field_name reveal="yes" caption="yes" stats="yes"}
```

## Styling notes

Modal popup markup assumes [Foundation 5](http://foundation.zurb.com/sites/docs/v/5.5.3/components/reveal.html) reveal styling and functionality.  This includes support for opening and closing the modal:

```javascript
var closeModal = function() {
  $('.reveal-modal-bg').hide();

  if ($('.reveal-modal.open iframe').length) {
    // pause the player
    $('.reveal-modal.open iframe')[0].contentWindow.postMessage('{"event":"command","func":"pauseVideo","args":""}', '*');
  }
  $('.reveal-modal.open').removeClass('open');
};
if ($('[data-reveal-id]').length) {
  $('[data-reveal-id]').on('click', function(e) {
    var reveal_id = $(this).data('reveal-id');
    var $popup = $('#'+reveal_id);
    $popup.addClass('open');
    $('.reveal-modal.open iframe')[0].contentWindow.postMessage('{"event":"command","func":"playVideo","args":""}', '*');
    
    if ($('.reveal-modal-bg').length === 0) {
      $bg = $('<div />', {'class': 'reveal-modal-bg'})
        .appendTo('body');
    }
    $bg.show();
  });
  $(document).on('click', '.reveal-modal-bg', function(e) {
    closeModal();
    return false;
  });
  $(document).on('click', '[data-reveal-close]', function(e) {
    closeModal();
    return false;
  });
  $(document).on('keyup', function(e) {
    if (e.keyCode == 27 && $('.reveal-modal.open').length) {
      closeModal();
    }
  });
}
```

Styling to support the modal:

```CSS
.reveal-modal {
  border-radius: 3px;
  display: none;
  position: absolute;
  top: 0;
  visibility: hidden;
  width: 100%;
  z-index: 1005;
  left: 0;
  background-color: #FFFFFF;
  padding: 1.875rem;
  border: solid 1px #666666;
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.4); }
  @media only screen and (max-width: 40em) {
    .reveal-modal {
      min-height: 100vh; } }
  .reveal-modal .column, .reveal-modal .columns {
    min-width: 0; }
  .reveal-modal > :first-child {
    margin-top: 0; }
  .reveal-modal > :last-child {
    margin-bottom: 0; }
  @media only screen and (min-width: 40.0625em) {
    .reveal-modal {
      left: 0;
      margin: 0 auto;
      max-width: 62.5rem;
      right: 0;
      width: 80%; } }
  @media only screen and (min-width: 40.0625em) {
    .reveal-modal {
      top: 6.25rem; } }
  .reveal-modal.radius {
    box-shadow: none;
    border-radius: 3px; }
  .reveal-modal.round {
    box-shadow: none;
    border-radius: 1000px; }
  .reveal-modal.collapse {
    padding: 0;
    box-shadow: none; }
  @media only screen and (min-width: 40.0625em) {
    .reveal-modal.tiny {
      left: 0;
      margin: 0 auto;
      max-width: 62.5rem;
      right: 0;
      width: 30%; } }
  @media only screen and (min-width: 40.0625em) {
    .reveal-modal.small {
      left: 0;
      margin: 0 auto;
      max-width: 62.5rem;
      right: 0;
      width: 40%; } }
  @media only screen and (min-width: 40.0625em) {
    .reveal-modal.medium {
      left: 0;
      margin: 0 auto;
      max-width: 62.5rem;
      right: 0;
      width: 60%; } }
  @media only screen and (min-width: 40.0625em) {
    .reveal-modal.large {
      left: 0;
      margin: 0 auto;
      max-width: 62.5rem;
      right: 0;
      width: 70%; } }
  @media only screen and (min-width: 40.0625em) {
    .reveal-modal.xlarge {
      left: 0;
      margin: 0 auto;
      max-width: 62.5rem;
      right: 0;
      width: 95%; } }
  .reveal-modal.full {
    height: 100vh;
    height: 100%;
    left: 0;
    margin-left: 0 !important;
    max-width: none !important;
    min-height: 100vh;
    top: 0; }
    @media only screen and (min-width: 40.0625em) {
      .reveal-modal.full {
        left: 0;
        margin: 0 auto;
        max-width: 62.5rem;
        right: 0;
        width: 100%; } }
  .reveal-modal.toback {
    z-index: 1003; }
  .reveal-modal .close-reveal-modal {
    color: #AAAAAA;
    cursor: pointer;
    font-size: 1rem;
    font-weight: bold;
    line-height: 1;
    position: absolute;
    top: 0.625rem;
    right: .625rem;
    text-decoration: none; }
.reveal-modal.open {
  visibility: visible;
  display: block;
}
```
