!function(t){function e(n){if(a[n])return a[n].exports;var r=a[n]={i:n,l:!1,exports:{}};return t[n].call(r.exports,r,r.exports,e),r.l=!0,r.exports}var a={};e.m=t,e.c=a,e.d=function(t,a,n){e.o(t,a)||Object.defineProperty(t,a,{configurable:!1,enumerable:!0,get:n})},e.n=function(t){var a=t&&t.__esModule?function(){return t.default}:function(){return t};return e.d(a,"a",a),a},e.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},e.p="",e(e.s=12)}({12:function(t,e,a){t.exports=a("EO+/")},"EO+/":function(t,e,a){"use strict";function n(t){return u(t).parent().parent().parent().parent().parent()}function r(t){var e=void 0;return"chapter"===t.post_type?e=new wp.api.models.Chapters({id:t.id}):"front-matter"===t.post_type?e=new wp.api.models.FrontMatter({id:t.id}):"back-matter"===t.post_type?e=new wp.api.models.BackMatter({id:t.id}):"part"===t.post_type&&(e=new wp.api.models.Parts({id:t.id})),e}function i(t,e){t=(t=t.attr("id").split("_"))[t.length-1],e=(e=e.attr("id").split("_"))[e.length-1];var a=new wp.api.models.Chapters({id:t});a.fetch({success:function(t,n,r){a.save({part:e},{patch:!0})}})}function o(t,e){return"prev"===e?u(t).prev("[id^=part]"):"next"===e?u(t).next("[id^=part]"):void 0}function s(t){t.children("tbody").children("tr").each(function(t,e){var a=u(e).attr("id").split("_"),n=r(a={id:a[a.length-1],post_type:a[0],menu_order:t});n.fetch({success:function(t,e,r){n.save({menu_order:a.menu_order},{patch:!0})}}),t++})}function l(t){t.children("tbody").children("tr").each(function(e,a){var n="",r='<button class="move-up">Move Up</button>',i='<button class="move-down">Move Down</button>';n=u(a).is("tr:first-of-type")?t.prev("[id^=part]").length?" | "+r+" | "+i:" | "+i:u(a).is("tr:last-of-type")?u(t).next("[id^=part]").length?" | "+r+" | "+i:" | "+r:" | "+r+" | "+i,u(a).children(".has-row-actions").children(".row-title").children(".row-actions").children(".reorder").html(n)})}Object.defineProperty(e,"__esModule",{value:!0});var c=a("EbL4"),p=a.n(c),u=window.jQuery,d={oldPart:null,newPart:null,defaultOptions:{revert:!0,helper:"clone",zIndex:2700,distance:3,opacity:.6,placeholder:"ui-state-highlight",connectWith:".chapters",dropOnEmpty:!0,cursor:"crosshair",items:"tbody > tr",start:function(t,e){d.oldPart=e.item.parents("table").attr("id")},stop:function(t,e){d.newPart=e.item.parents("table").attr("id"),d.update(e.item)}},frontMatterOptions:{revert:!0,helper:"clone",zIndex:2700,distance:3,opacity:.6,placeholder:"ui-state-highlight",dropOnEmpty:!0,cursor:"crosshair",items:"tbody > tr",start:function(t,e){},stop:function(t,e){d.fmupdate(e.item)}},backMatterOptions:{revert:!0,helper:"clone",zIndex:2700,distance:3,opacity:.6,placeholder:"ui-state-highlight",dropOnEmpty:!0,cursor:"crosshair",items:"tbody > tr",start:function(t,e){},stop:function(t,e){d.bmupdate(e.item)}},update:function(t){u.blockUI.defaults.applyPlatformOpacityRules=!1,u.blockUI({message:jQuery("#loader.chapter")}),i(t,u("#"+d.newPart)),s(u("#"+d.oldPart)),s(u("#"+d.newPart)),l(u("#"+d.oldPart)),l(u("#"+d.newPart))},fmupdate:function(t){u.blockUI.defaults.applyPlatformOpacityRules=!1,u.blockUI({message:jQuery("#loader.front-matter")}),s(u(t).parent().parent()),l(u(t).parent().parent())},bmupdate:function(t){u.blockUI.defaults.applyPlatformOpacityRules=!1,u.blockUI({message:jQuery("#loader.back-matter")}),s(u(t).parent().parent()),l(u(t).parent().parent())}};jQuery(document).ready(function(t){t("table.chapters").sortable(d.defaultOptions).disableSelection(),t("table#front-matter").sortable(d.frontMatterOptions).disableSelection(),t("table#back-matter").sortable(d.backMatterOptions).disableSelection(),t("input[name=blog_public]").change(function(){var e=void 0;e=1===parseInt(this.value,10)?1:0,t.ajax({url:ajaxurl,type:"POST",data:{action:"pb_update_global_privacy_options",blog_public:e,_ajax_nonce:PB_OrganizeToken.privacyNonce},beforeSend:function(){0===e?(t("h4.publicize-alert > span").text(PB_OrganizeToken.private),t(".publicize-alert").removeClass("public").addClass("private")):1===e&&(t("h4.publicize-alert > span").text(PB_OrganizeToken.public),t(".publicize-alert").removeClass("private").addClass("public"))},error:function(t,e,a){}})}),t(".web_visibility, .export_visibility").change(function(e){var a=t(e.target).parent().parent().attr("id").split("_");a={id:a[a.length-1],post_type:a[0]};var n=t("#export_visibility_"+a.id),i=t("#web_visibility_"+a.id),o=void 0;o=i.is(":checked")?n.is(":checked")?"publish":"web-only":n.is(":checked")?"private":"draft";var s=r(a);s.fetch({success:function(e,a,n){s.save({status:o},{patch:!0,success:function(){!function(){var e={action:"pb_update_word_count_for_export",_ajax_nonce:PB_OrganizeToken.wordCountNonce};t.post(ajaxurl,e,function(e){var a=parseInt(t("#wc-selected-for-export").text(),10);new p.a("wc-selected-for-export",a,e,0,2.5,{separator:""}).start()})}()}})}})}),t(".show_title").change(function(e){var a=t(e.target).parent().parent().attr("id");a={id:(a=a.split("_"))[a.length-1],post_type:a[0]};var n="";t(e.target).is(":checked")&&(n="on");var i=r(a);i.fetch({success:function(t,e,a){i.save({meta:{pb_show_title:n}},{patch:!0})}})}),t(document).on("click",".move-up",function(e){var a=n(e.target),r=t(a).attr("id").split("_");r=r[0],t.blockUI.defaults.applyPlatformOpacityRules=!1,t.blockUI({message:jQuery("#loader."+r)});var c=t(a).parent().parent();if(t(a).is("tr:first-of-type")&&c.is("[id^=part]")&&c.prev("[id^=part]").length){var p=o(c,"prev");p.append(a),i(a,p),s(c),s(p),l(c),l(p)}else a.prev().before(a),s(c),l(c)}),t(document).on("click",".move-down",function(e){var a=n(e.target),r=t(a).attr("id").split("_");r=r[0],t.blockUI.defaults.applyPlatformOpacityRules=!1,t.blockUI({message:jQuery("#loader."+r)});var c=t(a).parent().parent();if(t(a).is("tr:last-of-type")&&c.is("[id^=part]")&&c.next("[id^=part]").length){var p=o(c,"next");p.prepend(a),i(a,p),s(c),s(p),l(c),l(p)}else a.next().after(a),s(c),l(c)});var e=[];t("table thead th").click(function(){var a=t(this).index()+1,n=t(this).parents("table").index()+"_"+a;e[n]?(t(this).parents("table").find("tr td:nth-of-type("+a+")").find("input[type=checkbox]:checked").click(),e[n]=!1):(t(this).parents("table").find("tr td:nth-of-type("+a+")").find("input[type=checkbox]:not(:checked)").click(),e[n]=!0)}),t(document).ajaxStop(function(){t.unblockUI()}),t(window).on("beforeunload",function(){if(t.active>0)return"Changes you made may not be saved..."})})},EbL4:function(t,e,a){var n,r;!function(i,o){void 0===(r="function"==typeof(n=o)?n.call(e,a,e,t):n)||(t.exports=r)}(0,function(t,e,a){return function(t,e,a,n,r,i){function o(t){return"number"==typeof t&&!isNaN(t)}var s=this;if(s.version=function(){return"1.9.3"},s.options={useEasing:!0,useGrouping:!0,separator:",",decimal:".",easingFn:function(t,e,a,n){return a*(1-Math.pow(2,-10*t/n))*1024/1023+e},formattingFn:function(t){var e,a,n,r,i,o,l=t<0;if(t=Math.abs(t).toFixed(s.decimals),t+="",e=t.split("."),a=e[0],n=e.length>1?s.options.decimal+e[1]:"",s.options.useGrouping){for(r="",i=0,o=a.length;i<o;++i)0!==i&&i%3==0&&(r=s.options.separator+r),r=a[o-i-1]+r;a=r}return s.options.numerals.length&&(a=a.replace(/[0-9]/g,function(t){return s.options.numerals[+t]}),n=n.replace(/[0-9]/g,function(t){return s.options.numerals[+t]})),(l?"-":"")+s.options.prefix+a+n+s.options.suffix},prefix:"",suffix:"",numerals:[]},i&&"object"==typeof i)for(var l in s.options)i.hasOwnProperty(l)&&null!==i[l]&&(s.options[l]=i[l]);""===s.options.separator?s.options.useGrouping=!1:s.options.separator=""+s.options.separator;for(var c=0,p=["webkit","moz","ms","o"],u=0;u<p.length&&!window.requestAnimationFrame;++u)window.requestAnimationFrame=window[p[u]+"RequestAnimationFrame"],window.cancelAnimationFrame=window[p[u]+"CancelAnimationFrame"]||window[p[u]+"CancelRequestAnimationFrame"];window.requestAnimationFrame||(window.requestAnimationFrame=function(t,e){var a=(new Date).getTime(),n=Math.max(0,16-(a-c)),r=window.setTimeout(function(){t(a+n)},n);return c=a+n,r}),window.cancelAnimationFrame||(window.cancelAnimationFrame=function(t){clearTimeout(t)}),s.initialize=function(){return!(!s.initialized&&(s.error="",s.d="string"==typeof t?document.getElementById(t):t,s.d?(s.startVal=Number(e),s.endVal=Number(a),o(s.startVal)&&o(s.endVal)?(s.decimals=Math.max(0,n||0),s.dec=Math.pow(10,s.decimals),s.duration=1e3*Number(r)||2e3,s.countDown=s.startVal>s.endVal,s.frameVal=s.startVal,s.initialized=!0,0):(s.error="[CountUp] startVal ("+e+") or endVal ("+a+") is not a number",1)):(s.error="[CountUp] target is null or undefined",1)))},s.printValue=function(t){var e=s.options.formattingFn(t);"INPUT"===s.d.tagName?this.d.value=e:"text"===s.d.tagName||"tspan"===s.d.tagName?this.d.textContent=e:this.d.innerHTML=e},s.count=function(t){s.startTime||(s.startTime=t),s.timestamp=t;var e=t-s.startTime;s.remaining=s.duration-e,s.options.useEasing?s.countDown?s.frameVal=s.startVal-s.options.easingFn(e,0,s.startVal-s.endVal,s.duration):s.frameVal=s.options.easingFn(e,s.startVal,s.endVal-s.startVal,s.duration):s.countDown?s.frameVal=s.startVal-(s.startVal-s.endVal)*(e/s.duration):s.frameVal=s.startVal+(s.endVal-s.startVal)*(e/s.duration),s.countDown?s.frameVal=s.frameVal<s.endVal?s.endVal:s.frameVal:s.frameVal=s.frameVal>s.endVal?s.endVal:s.frameVal,s.frameVal=Math.round(s.frameVal*s.dec)/s.dec,s.printValue(s.frameVal),e<s.duration?s.rAF=requestAnimationFrame(s.count):s.callback&&s.callback()},s.start=function(t){s.initialize()&&(s.callback=t,s.rAF=requestAnimationFrame(s.count))},s.pauseResume=function(){s.paused?(s.paused=!1,delete s.startTime,s.duration=s.remaining,s.startVal=s.frameVal,requestAnimationFrame(s.count)):(s.paused=!0,cancelAnimationFrame(s.rAF))},s.reset=function(){s.paused=!1,delete s.startTime,s.initialized=!1,s.initialize()&&(cancelAnimationFrame(s.rAF),s.printValue(s.startVal))},s.update=function(t){if(s.initialize()){if(t=Number(t),!o(t))return void(s.error="[CountUp] update() - new endVal is not a number: "+t);s.error="",t!==s.frameVal&&(cancelAnimationFrame(s.rAF),s.paused=!1,delete s.startTime,s.startVal=s.frameVal,s.endVal=t,s.countDown=s.startVal>s.endVal,s.rAF=requestAnimationFrame(s.count))}},s.initialize()&&s.printValue(s.startVal)}})}});