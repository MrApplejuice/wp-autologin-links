/* Distributed under the GPL 2 */

jQuery(function($) {
  $(document).ready(function() {
    // Add popup html to current page
    var newNode = jQuery.parseHTML("<div id='pkg_autologin_display_popup'>"
                                   + "<p>" + pkg_autologin_show_link_translation_strings.link_text + "</p>"
                                   + "<input type='text' class='link-field' readonly='readonly'></input>"
                                   + "<p>" + pkg_autologin_show_link_translation_strings.press_ctrl_c + "</p>"
                                 + "</div>");
    $("body").append(newNode);
    
    // add handler in case the text-field was activated
    var textElement = $("body #pkg_autologin_display_popup .link-field");
    function selectAllHandler() {
      textElement.select();
    }
    textElement.focusin(selectAllHandler);
    textElement.click(selectAllHandler);
  });
  
  function show_copy_link_dialog(user, link) {
    var dialog = $("#pkg_autologin_display_popup");
    
    dialog.find(".link-field").val(link);
    
    var buttons = {};
    buttons[pkg_autologin_show_link_translation_strings.ok_button] = function() { dialog.dialog("close"); };
    
    dialog.dialog({
        height: 250,
        width: 400,
        title: pkg_autologin_show_link_translation_strings.title_prefix + user,
        dialogClass: 'pkg_autologin_display_popup_class',
        buttons: buttons
    });
  }
  
  pkg_autologin_show_copy_link_dialog = show_copy_link_dialog;
});
