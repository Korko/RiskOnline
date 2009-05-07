var Util = {
};

/**
 * config :
 * 	{
 *		content: content of the popup
 * 		background: config
 * 	}
 */
Util.Popup = function(config) {
	// Create a global div with the size of the window, grey with opacity 50%
	// Inside, create a div centered with the waitMsg

	var all = new Frame({position: 'absolute', width: window.innerWidth, height: window.innerHeight, left: window.scrollX, top: window.scrollY, backgroundColor: '#000', opacity: 0.5});
	all.setParent(document.body);

	var message = new Frame({element: 'div', position: 'absolute', width: 200, height: 50, textAlign: 'center', allAlign: 'center', backgroundColor: '#FFF', border: '4px #CC0 ridge'});
	message.setContent(config.content);
	message.setParent(document.body);

	this.close = function(){
		all.remove();
		message.remove();
	}
}

/**
 * config :
 * 	{
 *		url: url to request
 * 		args: arguments for the request
 * 		method: method of the request : GET or POST (caps only). Default : GET
 *		callback: function called after the request is done. Params : XMLHttpRequest Object, Boolean Success, String Parameters
 *		timeout: max time in seconds for the request // TODO
 * 		form: id of a formular to submit
 * 	}
 */
Util.Ajax = function(config) {
	var handle;

	if(window.XMLHttpRequest) // Firefox
		handle = new XMLHttpRequest();

	else if(window.ActiveXObject) // Internet Explorer
		handle = new ActiveXObject("Microsoft.XMLHTTP");

	else { // XMLHttpRequest non supporté par le navigateur
		throw new Error("Votre navigateur ne supporte pas les objets XMLHTTPRequest...");
		return;
	}

	var method;
	if( config.method == 'GET' || config.method == 'POST' )
	{
		method = config.method;
	}
	else
	{
		method = 'GET';
	}

	if(!config.url) throw new Error("URL needed");

	handle.open(method, config.url, true);

	if(config.timeout) {
		//handle.setTimeout(config.timeout);
	}

	if( !config.args ) config.args = '';

	if( config.method == 'POST' || config.form )
	{
		handle.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	}
	
	//var popup;
	if(config.form) {
		form = $(config.form);

		if(form == null) throw new Error('Bad Form Id');

		if(config.waitMsg){
			//popup = new Util.Popup();
		}

		// Input
		elts = form.getElementsByTagName('input');
		for(var i=0; i<elts.length; i++)
		{
			if( config.args.length > 0 ) config.args += '&';
			config.args += elts[i].name+'='+elts[i].value;
		}

		// Select
		elts = form.getElementsByTagName('select');
		for(var i=0; i<elts.length; i++)
		{
			if( elts[i].multiple ) {
				for (m = 0; m < elts[i].options.length; m++){
					if (elts[i].options[m].selected) {
						if( config.args.length > 0 ) config.args += '&';
						config.args += elts[i].name+'='+elts[i].options[m].text;
					}
				}
			}
			else
			{
				if( config.args.length > 0 ) config.args += '&';
				config.args += elts[i].name+'='+elts[i].options[elts[i].selectedIndex].text;
			}
		}

		// Textarea
		elts = form.getElementsByTagName('textarea');
		for(var i=0; i<elts.length; i++)
		{
			if( config.args.length > 0 ) config.args += '&';
			config.args += elts[i].name+'='+elts[i].value;
		}
	}

	handle.setRequestHeader("X-Requested-With", "XMLHttpRequest");
	handle.send(config.args);

	handle.onreadystatechange = function(){
		if( this.readyState == 4 )
		{
			if(config.callback)
				config.callback(
					this,
					(this.status == 200),
					config.args
				);

			//if(popup){
				//popup.close();
			//}
		}
	}
}

Util.addEventListener = function(elt, event, callback) {
	if( elt.addEventListener )
	{
		elt.addEventListener(event, callback, false);
	}
	else if( elt.attachEvent )
	{
		elt.attachEvent(event, callback);
	}
	else
	{
		elt.eval('on'+event).call(event, callback);
	}
}

Util.getFormValues = function(form) {
	var args='';

	var inputs = $(form).getElementsByTagName('input');
	for(var i=0; i<inputs.length; i++)
	{
		if( args.length > 0 ) args += '&';
		args += inputs[i].name+'='+inputs[i].value;
	}

	var textarea = $(form).getElementsByTagName('textarea');
	for(var i=0; i<textarea.length; i++)
	{
		if( args.length > 0 ) args += '&';
		args += textarea[i].name+'='+textarea[i].value;
	}

	var select = $(form).getElementsByTagName('select');
	for(var i=0; i<select.length; i++)
	{
		if( select[i].multiple ) {
			for (m = 0; m < select[i].options.length; m++){
				if (select[i].options[m].selected) {
					if( args.length > 0 ) args += '&';
					args += select[i].name+'='+select[i].options[m].text;
				}
			}
		}
		else
		{
			if( args.length > 0 ) args += '&';
			args += select[i].name+'='+select[i].options[select[i].selectedIndex].text;
		}
	}

	return args;
}

Util.getURLParam = function(strParamName) {
	var strReturn = "";
	var strHref = window.location.href;

	if ( strHref.indexOf("?") > -1 ){
		var strQueryString = strHref.substr(strHref.indexOf("?")).toLowerCase();
		var aQueryString = strQueryString.split("&");

		for ( var iParam = 0; iParam < aQueryString.length; iParam++ ){
			if (aQueryString[iParam].indexOf(strParamName + "=") > -1 ){
				var aParam = aQueryString[iParam].split("=");
				strReturn = aParam[1];
				break;
			}
		}
	}

	return strReturn;
}

Util.generateId = function() {
	do
		var id = new Date().getTime() + Math.floor(Math.random()*1000);
	while( document.getElementById(id) );

	return id;
}

Util.benchmark = {
	begin: 0,
	start: function() {
		Util.benchmark.begin = new Date().getTime();
	},
	stop: function() {
		return new Date().getTime() - Util.benchmark.begin;
	}
}
