var since = -1;
var timeout = 5000;

function display_chatbox_messages (request, success, args) {
	var messages = eval(request.responseText);
	
	var html = $('chatbox').innerHTML;
	for(var i=0; i<messages.length; i++)
	{
		var style = (messages[i].author_mid == 1) ? 'font-style: italic;' : 'font-weight: bold;';
		html += '<p style="margin: 0; padding: 0;"><span style="'+style+'">'+messages[i].author_name+'</span>: '+messages[i].content+'</p>';
		since = messages[i].date;
	}
	$('chatbox').innerHTML = html;
	$('chatbox').scrollTop = $('chatbox').scrollHeight; // Down !
	
	// If no message, then wait 2 seconds more
	if( messages.length == 0 )
		timeout += 5000;
	// Ah ! A message !
	else
		timeout = 5000;
}

function callfront_chatbox(config) {

	if( since != -1 )
	{
		var begin = config.config.url.indexOf('since=');
		var end = config.config.url.indexOf('&', begin);
		
		if( end == -1 )
		{
			end = config.config.url.length;
		}

		if( begin == -1 )
		{
			config.config.url += (config.config.url.indexOf('?') == -1) ? '?' : '&';
			config.config.url += 'since='+since;
		}
		else
		{
			config.config.url = config.config.url.substr(0, begin) + 'since='+since + config.config.url.substr(end, config.config.url.length);
		}
	}
	
	config.timeout = timeout;
}
