jQuery(document).ready(function($) {
      jQuery.get(ajaxurl, {action: 'mygmail_widget'}, function(response) {
          var data = JSON.parse(response);
          jQuery("#mygmail_widget").children('.inside').html(data.content);
          var title = jQuery("#mygmail_widget").children('h3').html();
          jQuery("#mygmail_widget").children('h3').html(title + ' (' + data.unread + ')');
      });
});