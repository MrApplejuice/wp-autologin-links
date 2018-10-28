/* Distributed under the GPL 2 */
function pkg_autologin_get_link_field() {
  return document.getElementById('pkg_autologin_link'); 
}

function pkg_autologin_get_link_field_text() {
  return pkg_autologin_get_link_field().lastChild; // Should be the text node
}

function pkg_autologin_get_code_input() {
  return document.getElementById('pkg_autologin_code');
}
