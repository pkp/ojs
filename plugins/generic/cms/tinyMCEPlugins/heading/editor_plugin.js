/**
 *
 * @author WSL.RU
 * @copyright Copyright c 2006. All rights reserved.
 *
 */

var TinyMCE_HeadingPlugin = {

    
    getInfo : function() {
        return {
            longname :  'Heading plugin',
            author :    'WSL.RU / Andrey G, ggoodd',
            authorurl : 'http://wsl.ru',
            infourl :   'mailto:ggoodd@gmail.com',
            version :   '1.2'
        };
    },


    initInstance : function(inst) {

            inst.addShortcut('alt', '1', 'lang_theme_h1', 'mceHeading', false, 1);
            inst.addShortcut('alt', '2', 'lang_theme_h2', 'mceHeading', false, 2);
            inst.addShortcut('alt', '3', 'lang_theme_h3', 'mceHeading', false, 3);
            inst.addShortcut('alt', '4', 'lang_theme_h4', 'mceHeading', false, 4);
            inst.addShortcut('alt', '5', 'lang_theme_h5', 'mceHeading', false, 5);
            inst.addShortcut('alt', '6', 'lang_theme_h6', 'mceHeading', false, 6);
    },

    getControlHTML : function(cn) {

        switch (cn) { 

            case "h1": return tinyMCE.getButtonHTML(cn, 'lang_theme_h1', '{$pluginurl}/images/h1.gif', 'mceHeading', false, 1);
            case "h2": return tinyMCE.getButtonHTML(cn, 'lang_theme_h2', '{$pluginurl}/images/h2.gif', 'mceHeading', false, 2);
            case "h3": return tinyMCE.getButtonHTML(cn, 'lang_theme_h3', '{$pluginurl}/images/h3.gif', 'mceHeading', false, 3);
            case "h4": return tinyMCE.getButtonHTML(cn, 'lang_theme_h4', '{$pluginurl}/images/h4.gif', 'mceHeading', false, 4);
            case "h5": return tinyMCE.getButtonHTML(cn, 'lang_theme_h5', '{$pluginurl}/images/h5.gif', 'mceHeading', false, 5);
            case "h6": return tinyMCE.getButtonHTML(cn, 'lang_theme_h6', '{$pluginurl}/images/h6.gif', 'mceHeading', false, 6);
        } 

        return ''; 
    },


    execCommand : function(editor_id, element, command, user_interface, value) {

        switch (command) {

            case "mceHeading":

                var ct=tinyMCE.getParam("heading_clear_tag",false)?"<"+tinyMCE.getParam("heading_clear_tag","")+">":"";

                tinyMCE.selectedElement.nodeName.toLowerCase() == 'h'+value
                  ? tinyMCE.execInstanceCommand(editor_id, 'FormatBlock', false, ct) 
                  : tinyMCE.execInstanceCommand(editor_id, 'FormatBlock', false, "<h"+value+">");

                return true;
        }

        return false;
    },


    handleNodeChange : function(editor_id, node, undo_index, undo_levels, visual_aid, any_selection) {

        if (node == null)
            return;

        tinyMCE.switchClass(editor_id + '_h1', tinyMCE.getParentElement(node, "h1") ? 'mceButtonSelected' : 'mceButtonNormal');
        tinyMCE.switchClass(editor_id + '_h2', tinyMCE.getParentElement(node, "h2") ? 'mceButtonSelected' : 'mceButtonNormal');
        tinyMCE.switchClass(editor_id + '_h3', tinyMCE.getParentElement(node, "h3") ? 'mceButtonSelected' : 'mceButtonNormal');
        tinyMCE.switchClass(editor_id + '_h4', tinyMCE.getParentElement(node, "h4") ? 'mceButtonSelected' : 'mceButtonNormal');
        tinyMCE.switchClass(editor_id + '_h5', tinyMCE.getParentElement(node, "h5") ? 'mceButtonSelected' : 'mceButtonNormal');
        tinyMCE.switchClass(editor_id + '_h6', tinyMCE.getParentElement(node, "h6") ? 'mceButtonSelected' : 'mceButtonNormal');

        return true;
    }


};

tinyMCE.addPlugin("heading", TinyMCE_HeadingPlugin);
