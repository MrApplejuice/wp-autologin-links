/* Distributed under the GPL 2 */
"use strict";

function pkg_autologin_get_link_field() {
  return jQuery('#pkg_autologin_link'); 
}

function pkg_autologin_append_unsaved_node() {
  var unsavedMarker = jQuery('#pkg_autologin_unsaved_marker')
  unsavedMarker.css("display", "inline");
}

function pkg_autologin_show_wait_spinner(do_show) {
  jQuery('#pkg_autologin_link_wait_spinner').css("display", do_show ? "inline" : "none");
}

function pkg_autologin_new_link_click(sender, prefix) {
  pkg_autologin_show_wait_spinner(true);
  
  jQuery.ajax({
    url: ajaxurl, 
    method: "POST",
    data: {
      action: "pkg_autologin_plugin_ajax_new_code",
      user_id: parseInt(jQuery("#pkg_autologin_user_id").text()),
      _ajax_nonce: jQuery("#pkg_autologin_nonce").val(),
    }
  }).done(function(data) {
    pkg_autologin_append_unsaved_node();
    jQuery("#pkg_autologin_update").val("update");
    pkg_autologin_get_link_field().text(prefix + data.new_code);
  }).fail(function() {
    pkg_autologin_get_link_field().text("FAILED");
  }).always(function() {
    pkg_autologin_show_wait_spinner(false);
  });
}

function pkg_autologin_delete_link_click(sender) {
  pkg_autologin_show_wait_spinner(true);
  jQuery.ajax({
    url: ajaxurl, 
    method: "POST",
    data: {
      action: "pkg_autologin_plugin_ajax_delete_code",
      user_id: parseInt(jQuery("#pkg_autologin_user_id").text()),
      _ajax_nonce: jQuery("#pkg_autologin_nonce").val(),
    }
  }).done(function(data) {
    pkg_autologin_append_unsaved_node();
    jQuery("#pkg_autologin_update").val("delete");
    pkg_autologin_get_link_field().text("-");
  }).fail(function() {
    pkg_autologin_get_link_field().text("FAILED");
  }).always(function() {
    pkg_autologin_show_wait_spinner(false);
  });
}
