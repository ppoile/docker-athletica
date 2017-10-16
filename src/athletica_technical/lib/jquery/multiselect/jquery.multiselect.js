/*
 * jQuery MultiSelect Plugin 0.5
 * Copyright (c) 2010 Eric Hynds
 *
 * http://www.erichynds.com/jquery/jquery-multiselect-plugin-with-themeroller-support/
 * Inspired by Cory S.N. LaViska's implementation, A Beautiful Site (http://abeautifulsite.net/) 2009
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
*/
(function(f){f.fn.multiSelect=function(i){i=f.extend({},f.fn.multiSelect.defaults,i);return this.each(function(){return new v(this,i)})};var v=function(i,c){var g=$original=f(i),e,l,j,h=[],q=[];l=g.is(":disabled");h.push('<a id="'+i.id+'" class="ui-multiselect ui-widget ui-state-default ui-corner-all'+(l||c.disabled?" ui-state-disabled":"")+'">');h.push('<input readonly="readonly" type="text" class="ui-state-default" value="'+c.noneSelectedText+'" /><span class="ui-icon ui-icon-triangle-1-s"></span></a>'); h.push('<div class="ui-multiselect-options'+(c.shadow?" ui-multiselect-shadow":"")+' ui-widget ui-widget-content ui-corner-all">');if(c.showHeader){h.push('<div class="ui-widget-header ui-helper-clearfix ui-corner-all ui-multiselect-header">');h.push('<ul class="ui-helper-reset">');h.push('<li><a class="ui-multiselect-all" href=""><span class="ui-icon ui-icon-check"></span>'+c.checkAllText+"</a></li>");h.push('<li><a class="ui-multiselect-none" href=""><span class="ui-icon ui-icon-closethick"></span>'+ c.unCheckAllText+"</a></li>");h.push('<li class="ui-multiselect-close"><a href="" class="ui-multiselect-close ui-icon ui-icon-circle-close"></a></li>');h.push("</ul>");h.push("</div>")}h.push('<ul class="ui-multiselect-checkboxes ui-helper-reset">');l&&g.removeAttr("disabled");g.find("option").each(function(){var a=f(this),b=a.html(),d=this.value,m=d.length,k=a.parent(),w=k.is("optgroup"),r=a.is(":disabled"),s=["ui-corner-all"],t=[];if(w){k=k.attr("label");if(f.inArray(k,q)===-1){h.push('<li class="ui-multiselect-optgroup-label"><a href="#">'+ k+"</a></li>");q.push(k)}}if(m>0){if(r){s.push("ui-state-disabled");t.push("ui-multiselect-disabled")}h.push('<li class="'+t.join(" ")+'">');h.push('<label class="'+s.join(" ")+'"><input type="checkbox" name="'+i.name+'" value="'+d+'" title="'+b+'"');a.is(":selected")&&h.push(' checked="checked"');r&&h.push(' disabled="disabled"');h.push(" />"+b+"</label></li>")}});h.push("</ul></div>");g=g.after(h.join("")).next("a.ui-multiselect");e=g.next("div.ui-multiselect-options");l=e.find("div.ui-multiselect-header"); j=e.find("label").not(".ui-state-disabled");var u=g.find("span.ui-icon").outerWidth(),n=$original.outerWidth(),o=n+u;if(/\d/.test(c.minWidth)&&o<c.minWidth){n=c.minWidth-u;o=c.minWidth}g.width(o).find("input").width(n);c.showHeader&&l.find("a").click(function(a){var b=f(this);if(b.hasClass("ui-multiselect-close"))e.trigger("close");else{b=b.hasClass("ui-multiselect-all");e.trigger("toggleChecked",[b?true:false]);c[b?"onCheckAll":"onUncheckAll"].call(this)}a.preventDefault()});var p=function(){var a= j.find("input"),b=a.filter(":checked"),d="";d=b.length;d=d===0?c.noneSelectedText:f.isFunction(c.selectedText)?c.selectedText.call(this,d,a.length,b.get()):/\d/.test(c.selectedList)&&c.selectedList>0&&d<=c.selectedList?b.map(function(){return this.title}).get().join(", "):c.selectedText.replace("#",d).replace("#",a.length);g.find("input").val(d).attr("title",d);return d};g.bind({click:function(){e.trigger("toggle")},keypress:function(a){switch(a.keyCode){case 27:case 38:e.trigger("close");break;case 40:case 0:e.trigger("toggle"); break}},mouseenter:function(){g.hasClass("ui-state-disabled")||f(this).addClass("ui-state-hover")},mouseleave:function(){f(this).removeClass("ui-state-hover")},focus:function(){g.hasClass("ui-state-disabled")||f(this).addClass("ui-state-focus")},blur:function(){f(this).removeClass("ui-state-focus")}});e.bind({close:function(a,b){b=b||false;if(b===true)f("div.ui-multiselect-options").filter(":visible").fadeOut(c.fadeSpeed).prev("a.ui-multiselect").removeClass("ui-state-active").trigger("mouseout"); else{g.removeClass("ui-state-active").trigger("mouseout");e.fadeOut(c.fadeSpeed)}},open:function(a,b){if(!g.hasClass("ui-state-disabled")){a=g[g.closest("div.ui-widget-content").length?"position":"offset"]();var d=e.find("ul:last"),m;g.addClass("ui-state-active");if(b||typeof b==="undefined")e.trigger("close",[true]);b=c.position==="middle"?a.top+g.height()/2-e.outerHeight()/2:c.position==="top"?a.top-e.outerHeight():a.top+g.outerHeight();m=g.width()-parseInt(e.css("padding-left"),10)-parseInt(e.css("padding-right"), 10);j.filter("label:first").trigger("mouseenter").trigger("focus");e.css({position:"absolute",top:b+"px",left:a.left+"px",width:m+"px"}).show();d.scrollTop(0);c.maxHeight&&d.css("height",c.maxHeight);c.onOpen.call(e[0])}},toggle:function(){e.trigger(f(this).is(":hidden")?"open":"close")},traverse:function(a,b,d){a=f(b);d=d===38||d===37?true:false;a=a.parent()[d?"prevAll":"nextAll"]("li:not(.ui-multiselect-disabled, .ui-multiselect-optgroup-label)")[d?"last":"first"]();if(a.length)a.find("label").trigger("mouseenter"); else{a=e.find("ul:last");e.find("label")[d?"last":"first"]().trigger("mouseover");a.scrollTop(d?a.height():0)}},toggleChecked:function(a,b,d){(d&&d.length?d:j.find("input")).not(":disabled").attr("checked",b?"checked":"");p()}}).find("li.ui-multiselect-optgroup-label a").click(function(a){var b=f(this).parent().nextUntil("li.ui-multiselect-optgroup-label").find("input");e.trigger("toggleChecked",[b.filter(":checked").length===b.length?false:true,b]);c.onOptgroupToggle.call(this,b.get());a.preventDefault()}); j.bind({mouseenter:function(){j.removeClass("ui-state-hover");f(this).addClass("ui-state-hover").find("input").focus()},click:function(a){a.preventDefault();f(this).find("input").trigger("click",[true])},keyup:function(a){switch(a.keyCode){case 27:e.trigger("close");break;case 38:case 40:case 37:case 39:e.trigger("traverse",[this,a.keyCode]);break;case 13:a.preventDefault();f(this).click();break}}}).find("input").bind("click",function(a,b){b=b||false;a.stopPropagation();if(b){a.preventDefault();this.checked= this.checked?false:true}c.onCheck.call(this);p()});$original.remove();f.fn.bgiframe&&e.bgiframe();c.state==="open"&&e.trigger("open",[false]);g.find("input")[0].defaultValue=p()};f(document).bind("click",function(i){i=f(i.target);!i.closest("div.ui-multiselect-options").length&&!i.parent().hasClass("ui-multiselect")&&f("div.ui-multiselect-options").trigger("close",[true])});f.fn.multiSelect.defaults={showHeader:true,maxHeight:175,minWidth:200,checkAllText:"Check all",unCheckAllText:"Uncheck all", noneSelectedText:"Select options",selectedText:"# selected",selectedList:0,position:"bottom",shadow:false,fadeSpeed:200,disabled:false,state:"closed",onCheck:function(){},onOpen:function(){},onCheckAll:function(){},onUncheckAll:function(){},onOptgroupToggle:function(){}}})(jQuery);