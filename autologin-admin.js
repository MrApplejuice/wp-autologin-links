/* Distributed under the GPL 2 */

function pkg_autologin_append_unsaved_node() {
  var unsavedMarker = document.getElementById('pkg_autologin_unsaved_marker')
  unsavedMarker.style.display = "inline";
}

function pkg_autologin_new_link_click(sender, prefix) {
  var newValue = "TODOTODOTODO";
  
  var updateField = document.getElementById("pkg_autologin_update");
  updateField.value = "update";
  
  var linkTextNode = pkg_autologin_get_link_field_text();
  linkTextNode.nodeValue = prefix + newValue + " ";
  
  pkg_autologin_append_unsaved_node();
}

function pkg_autologin_delete_link_click(sender) {
  var updateField = document.getElementById("pkg_autologin_update");
  updateField.value = "delete";

  var linkTextNode = pkg_autologin_get_link_field_text();
  linkTextNode.nodeValue = "-";

  pkg_autologin_append_unsaved_node();
}

