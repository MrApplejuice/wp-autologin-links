/* Distributed under the GPL 2 */
function pkg_autologin_generate_new_code() {
  var characterString = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
  
  var result = "";
  while (result.length < 30) {
    result += characterString.charAt(Math.floor(Math.random() * characterString.length));
  }

  return result;  
}

function pkg_autologin_append_unsaved_node() {
  var unsavedMarker = document.getElementById('pkg_autologin_unsaved_marker')
  unsavedMarker.style.display = "inline";
}

function pkg_autologin_new_link_click(sender, prefix) {
  var newValue = pkg_autologin_generate_new_code();
  
  var codeInput = pkg_autologin_get_code_input();
  codeInput.value = newValue;
  
  var linkTextNode = pkg_autologin_get_link_field_text();
  linkTextNode.nodeValue = prefix + newValue + " ";
  
  pkg_autologin_append_unsaved_node();
}

function pkg_autologin_delete_link_click(sender) {
  var codeInput = pkg_autologin_get_code_input();
  if (codeInput.value.trim() != "") {
    codeInput.value = "";

    var linkTextNode = pkg_autologin_get_link_field_text();
    linkTextNode.nodeValue = "-";
    
    pkg_autologin_append_unsaved_node();
  }
}

