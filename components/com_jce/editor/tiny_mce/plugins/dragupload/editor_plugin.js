/* JCE Editor - 2.3.2.3 | 11 March 2013 | http://www.joomlacontenteditor.net | Copyright (C) 2006 - 2013 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
(function(){var each=tinymce.each,extend=tinymce.extend,JSON=tinymce.util.JSON;var isWin=navigator.platform.indexOf('Win')!==-1,isSafari=tinymce.isWebKit&&navigator.vendor.indexOf('Apple')!==-1;var mimes={};function toArray(list){return Array.prototype.slice.call(list||[],0);}
(function(mime_data){var items=mime_data.split(/,/),i,y,ext;for(i=0;i<items.length;i+=2){ext=items[i+1].split(/ /);for(y=0;y<ext.length;y++){mimes[ext[y]]=items[i];}}})("application/msword,doc dot,"+"application/pdf,pdf,"+"application/pgp-signature,pgp,"+"application/postscript,ps ai eps,"+"application/rtf,rtf,"+"application/vnd.ms-excel,xls xlb,"+"application/vnd.ms-powerpoint,ppt pps pot,"+"application/zip,zip,"+"application/x-shockwave-flash,swf swfl,"+"application/vnd.openxmlformats-officedocument.wordprocessingml.document,docx,"+"application/vnd.openxmlformats-officedocument.wordprocessingml.template,dotx,"+"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,xlsx,"+"application/vnd.openxmlformats-officedocument.presentationml.presentation,pptx,"+"application/vnd.openxmlformats-officedocument.presentationml.template,potx,"+"application/vnd.openxmlformats-officedocument.presentationml.slideshow,ppsx,"+"application/x-javascript,js,"+"application/json,json,"+"audio/mpeg,mpga mpega mp2 mp3,"+"audio/x-wav,wav,"+"audio/mp4,m4a,"+"image/bmp,bmp,"+"image/gif,gif,"+"image/jpeg,jpeg jpg jpe,"+"image/photoshop,psd,"+"image/png,png,"+"image/svg+xml,svg svgz,"+"image/tiff,tiff tif,"+"text/plain,asc txt text diff log,"+"text/html,htm html xhtml,"+"text/css,css,"+"text/csv,csv,"+"text/rtf,rtf,"+"video/mpeg,mpeg mpg mpe,"+"video/quicktime,qt mov,"+"video/mp4,mp4,"+"video/x-m4v,m4v,"+"video/x-flv,flv,"+"video/x-ms-wmv,wmv,"+"video/avi,avi,"+"video/webm,webm,"+"video/vnd.rn-realvideo,rv,"+"application/vnd.oasis.opendocument.formula-template,otf,"+"application/octet-stream,exe");var state={STOPPED:1,STARTED:2,QUEUED:1,UPLOADING:2,FAILED:4,DONE:5,GENERIC_ERROR:-100,HTTP_ERROR:-200,IO_ERROR:-300,SECURITY_ERROR:-400}
tinymce.create('tinymce.plugins.DragUpload',{files:[],plugins:[],init:function(ed,url){function cancel(){ed.dom.bind(ed.getBody(),'dragover',function(e){var dataTransfer=e.dataTransfer;if(dataTransfer&&dataTransfer.files&&dataTransfer.files.length){e.preventDefault();}});ed.dom.bind(ed.getBody(),'drop',function(e){var dataTransfer=e.dataTransfer;if(dataTransfer&&dataTransfer.files&&dataTransfer.files.length){e.preventDefault();}});}
if(!window.FormData||tinymce.isOpera){ed.onInit.add(function(){cancel();});return;}
var self=this;self.editor=ed;self.plugin_url=url;ed.onPreInit.add(function(){if(!ed.settings.compress.css)
ed.dom.loadCSS(url+"/css/content.css");});ed.onInit.add(function(){each(ed.plugins,function(o,k){if(ed.getParam(k+'_dragdrop_upload')&&tinymce.is(o.getUploadURL,'function')&&tinymce.is(o.insertUploadedFile,'function')){self.plugins.push(o);}});if(self.plugins.length==0){cancel();return;}
if(isSafari&&isWin){ed.dom.bind(ed.getBody(),'dragenter',function(e){var dataTransfer=e.dataTransfer;if(dataTransfer&&dataTransfer.files&&dataTransfer.files.length){var dropInputElm;dropInputElm=ed.dom.get(ed.getBody().id+"_dragdropupload");if(!dropInputElm){dropInputElm=ed.dom.add(ed.getBody(),"input",{'type':'file','id':ed.getBody().id+"_dragdropupload",'multiple':'multiple','style':{position:'absolute',display:'block',top:0,left:0,width:'100%',height:'100%',opacity:'0'}});}
ed.dom.bind(dropInputElm,'change',function(e){each(dropInputElm.files,function(file){if(tinymce.inArray(self.files,file)==-1){self.addFile(file);}});ed.dom.unbind(dropInputElm,'change');ed.dom.remove(dropInputElm);each(self.files,function(file){self.upload(file);});});}});ed.dom.bind(ed.getBody(),'drop',function(e){var dataTransfer=e.dataTransfer;if(dataTransfer&&dataTransfer.files&&dataTransfer.files.length){e.preventDefault();}});return;}
ed.dom.bind(ed.getBody(),'dragover',function(e){e.preventDefault();});ed.dom.bind(ed.getBody(),'drop',function(e){var dataTransfer=e.dataTransfer;if(dataTransfer&&dataTransfer.files&&dataTransfer.files.length){each(dataTransfer.files,function(file){self.addFile(file);});e.preventDefault();}
each(self.files,function(file){self.upload(file);});});});self.FilesAdded=new tinymce.util.Dispatcher(this);self.UploadProgress=new tinymce.util.Dispatcher(this);self.FileUploaded=new tinymce.util.Dispatcher(this);self.UploadError=new tinymce.util.Dispatcher(this);this.settings={multipart:true,multi_selection:true,file_data_name:'file',filters:[]};self.FileUploaded.add(function(file,o){var n=file.marker,s;function showError(error){ed.windowManager.alert(error||ed.getLang('upload.response_error','Invalid Upload Response'));ed.dom.remove(n);return false;}
if(n){if(o&&o.response){var r=JSON.parse(o.response);if(!r){return showError();}
if(r.error){var txt=r.text||r.error;ed.windowManager.alert(txt);ed.dom.remove(n);return false;}
if(file.status==state.DONE){r.type=file.type;if(file.uploader){o=file.uploader;if(s=o.insertUploadedFile(r)){if(ed.dom.replace(s,n)){ed.nodeChanged();return true;}}}
self.files.splice(tinymce.inArray(self.files,file),1);}}else{return showError();}
ed.dom.remove(n);}});self.UploadError.add(function(o){ed.windowManager.alert(o.code+' : '+o.message);if(o.file&&o.file.marker){ed.dom.remove(o.file.marker);}});self.UploadProgress.add(function(file){file.marker.innerHTML='<span class="upload-progress">'+Math.round(file.loaded/file.size*100)+'</span>';});},buildUrl:function(url,items){var query='';each(items,function(value,name){query+=(query?'&':'')+encodeURIComponent(name)+'='+encodeURIComponent(value);});if(query){url+=(url.indexOf('?')>0?'&':'?')+query;}
return url;},addFile:function(file){var ed=this.editor,self=this,fileNames={},url;each(self.plugins,function(o,k){if(!file.upload_url){if(url=o.getUploadURL(file)){file.upload_url=url;file.uploader=o;}}});if(file.upload_url){self.FilesAdded.dispatch(file);var w=300,h=300;ed.execCommand('mceInsertContent',false,'<span id="__mce_tmp" data-mce-type="marker" class="mceItemUpload"></span>',{skip_undo:1});if(/image\/(gif|png|jpeg|jpeg)/.test(file.type)){w=h=Math.round(Math.sqrt(file.size));}
var n=ed.dom.get('__mce_tmp');ed.dom.setAttrib(n,'id','');ed.dom.setStyles(n,{'width':w,'height':h});ed.undoManager.add();file.marker=n;self.files.push(file);}},upload:function(file){var self=this,ed=this.editor;var args={'action':'upload','format':'raw','method':'dragdrop','component_id':ed.settings.component_id};args[ed.settings.token]='1';var url=file.upload_url;function sendFile(bin){var xhr=new XMLHttpRequest,formData=new FormData();if(xhr.upload){xhr.upload.onprogress=function(e){if(e.lengthComputable){file.loaded=Math.min(file.size,e.loaded);self.UploadProgress.dispatch(file);}};}
xhr.onreadystatechange=function(){var httpStatus;if(xhr.readyState==4&&self.state!==state.STOPPED){try{httpStatus=xhr.status;}catch(ex){httpStatus=0;}
if(httpStatus>=400){self.UploadError.dispatch({code:state.HTTP_ERROR,message:ed.getLang('upload.http_error','HTTP Error'),file:file,status:httpStatus});}else{file.loaded=file.size;self.UploadProgress.dispatch(file);bin=formData=null;file.status=state.DONE;self.FileUploaded.dispatch(file,{response:xhr.responseText,status:httpStatus});}}};extend(args,{'name':file.target_name||file.name});xhr.open("post",url,true);each(self.settings.headers,function(value,name){xhr.setRequestHeader(name,value);});each(extend(args,self.settings.multipart_params),function(value,name){formData.append(name,value);});formData.append(self.settings.file_data_name,bin);xhr.send(formData);return;}
if(file.status==state.DONE||file.status==state.FAILED||self.state==state.STOPPED){return;}
extend(args,{name:file.target_name||file.name});sendFile(file);},getInfo:function(){return{longname:'Drag & Drop Upload',author:'Ryan Demmer',authorurl:'http://www.joomlacontenteditor.net',infourl:'http://www.joomlacontenteditor.net',version:'2.3.2.3'};}});tinymce.PluginManager.add('dragupload',tinymce.plugins.DragUpload);})();