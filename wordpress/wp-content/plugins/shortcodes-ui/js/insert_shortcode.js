/**
 * ShortCodes UI Insert Shortcode
 * @author Ohad Raz
 * @since 1.9.2
 */
(function() {
	var fieldSelection = {

		getSelection: function() {
			var e = (this.jquery) ? this[0] : this;
			return (
				/* mozilla / dom 3.0 */
				('selectionStart' in e && function() {
					var l = e.selectionEnd - e.selectionStart;
					return { start: e.selectionStart, end: e.selectionEnd, length: l, text: e.value.substr(e.selectionStart, l) };
				}) ||
				/* exploder */
				(document.selection && function() {
					e.focus();
					var r = document.selection.createRange();
					if (r === null) {
						return { start: 0, end: e.value.length, length: 0 }
					}
					var re = e.createTextRange();
					var rc = re.duplicate();
					re.moveToBookmark(r.getBookmark());
					rc.setEndPoint('EndToStart', re);
					return { start: rc.text.length, end: rc.text.length + r.text.length, length: r.text.length, text: r.text };
				}) ||
				/* browser not supported */
				function() { return null; }
			)();
		}
	};

	jQuery.each(fieldSelection, function(i) { jQuery.fn[i] = this; });

})();

var selected_content = "";
var shui_editor = "visual";
//insert shortcode
jQuery(document).ready(function() {
	//insert shortcode
	jQuery(".insert_shortcode").live('click', function() {
		var shortcode = "";
		var attr_val = "";
		shortcode = "[" + walker.tag;
		if (walker.fields){
    		jQuery.each(walker.fields, function(i,item){
    			attr_val = "";
    			attr_val = jQuery("#" + item.name).val();
    			//if ( attr_val != "" && attr_val.lenght > 0){
    				shortcode = shortcode + " " + item.name + "=\"" + attr_val + "\"";
    			//}
			});
		}
		
		if (walker.content){
		    var con = "";
		    con = jQuery(".sc_content").val();
		}
		if (walker.content && jQuery.trim(con).length){
			shortcode = shortcode + "]" + jQuery(".sc_content").val();
			shortcode = shortcode + "[/"+ walker.tag + "]"; 
		}else{
			shortcode = shortcode + "]";
		}
		shui_insert_content(shortcode);
		closeSimpleBox();
	});
	
	//imported author lock
	if (jQuery("#_bascimported").val() == 1){
        jQuery("#_basc_Author_Name").attr("disabled", true); 
	    jQuery("#_basc_Author_url").attr("disabled", true); 
		jQuery("#_basc_Support_url").attr("disabled", true); 
	}
	
	//quicktag
    if ("undefined" !== typeof edButtons) {
		var shuiIdx = edButtons.length;
		edButtons[shuiIdx] = new edButton(
			"shui"  // id
			,"ShortCodes UI"    // display
			,""  // tagStart
			,"" // tagEnd
			,""     // access
		);
    }
    
	jQuery("#qt_content_shui").live("click",function() {
	    shui_editor = "html";
	    selected_content = jQuery("#content").getSelection().text;
	    SimpleBox(null,"admin-ajax.php?action=sh_ui_panel","ShortCodes UI");
	 }); 
	
	//render snippet
	jQuery(".render_shortcode").live("click", function() {
		var shortcode = "";
		var attr_val = "";
		shortcode = "[" + walker.tag;
		if (walker.fields){
    		jQuery.each(walker.fields, function(i,item){
    			attr_val = "";
    			attr_val = jQuery("#" + item.name).val();
    			//if ( attr_val != "" && attr_val.lenght > 0){
    				shortcode = shortcode + " " + item.name + "=\"" + attr_val + "\"";
    			//}
			});
		}
		
		if (walker.content){
		    var con = "";
		    con = jQuery(".sc_content").val();
		}
		if (walker.content && jQuery.trim(con).length){
			shortcode = shortcode + "]" + jQuery(".sc_content").val();
			shortcode = shortcode + "[/"+ walker.tag + "]"; 
		}else{
			shortcode = shortcode + "]";
		}
		jQuery(".sc_status").show("fast");
		jQuery.ajaxSetup({ cache: false });
		
		jQuery.getJSON(ajaxurl,
		{  	sc_to_rander: shortcode,
			rnd: microtime(false), //hack to avoid request cache
		    action: "ba_sb_rander",
		    seq: conf.get_shortcode_rander_nonce
		},
		function(data) {
			jQuery.ajaxSetup({ cache: true });
			if (data){
				if (data.code){
					jQuery(".sc_status").hide("3500");
					shui_insert_content(data.code);
					closeSimpleBox();
				}else{
					alert("Something Went Wrong");
					closeSimpleBox();
				}
			}
		});
	});
});

function microtime(get_as_float) {  
	var now = new Date().getTime() / 1000;  
	var s = parseInt(now);  
    return (get_as_float) ? now : (Math.round((now - s) * 1000) / 1000) + " " + s;  
}

function shui_insert_content(content){
	if (jQuery("#wp-content-wrap").hasClass("tmce-active")){
        tinyMCE.activeEditor.execCommand("mceInsertContent", 0, content);
    }else{
    	if (shortcodes_ui_last_element == null)
        	edInsertContent(edCanvas, content);
        else{
        	jQuery(shortcodes_ui_last_element).next().val(jQuery(shortcodes_ui_last_element).next().val() + content);
        	shortcodes_ui_last_element = null;
        }
    }
}
var shortcodes_ui_last_element = null;
function setCaller(e){
	shortcodes_ui_last_element = jQuery(e);
	SimpleBox(null,'admin-ajax.php?action=sh_ui_panel','ShortCodes UI');
}