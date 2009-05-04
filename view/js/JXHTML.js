var JXHTML = {};

JXHTML.checkForm = function(form) {
	form = $(form);

	var check=true;

	var childs = form.childNodes;

	for(var i=0; i<childs.length; i++)
	{
		if( childs[i].childNodes.length > 0 && !JXHTML.checkForm(childs[i]) ) check = false;

		if( childs[i].attributes && childs[i].attributes.getNamedItem('check') )
		{
			var attr_check = childs[i].attributes.getNamedItem('check').value;

			if( attr_check == 'NOTEMPTY' && childs[i].tagName == 'INPUT' && childs[i].value == '' )
			{
				check = false;
				alert('empty field');
			}

			//if( attr_check == 'EMAIL' && childs[i].tagName == 'INPUT' && !preg_match('#^ $#i', childs[i].value) )
		}
	}

	return check;
}

JXHTML.parse = function() {
	// Anchors AJAX or not ?
	var a = document.getElementsByTagName('a');

	for(var i=0; i<a.length; i++)
	{
		if( a[i].attributes.getNamedItem('method') && a[i].attributes.getNamedItem('method').value == 'ajax' )
		{
			// Don't use addEventListener because it will not block the link (return false)
			a[i].onclick = function(evt) {
				Util.Ajax({
					url: evt.target.attributes.getNamedItem('href').value,
					callback: function(response){
						var json = eval('('+response.responseText+')');

						if( evt.target.attributes.getNamedItem('callback') )
						{
							evt.target.attributes.getNamedItem('callback').value.call(new Frame(json, true));
						}
						else
						{
							new Frame(json, false);
						}
					}
				});
				return false;
			};
		}
	}

	// Check forms before submition
	var form = document.getElementsByTagName('form');
		
	for(var i=0; i<form.length; i++)
	{
		// If method AJAX
		if( form[i].attributes.getNamedItem('method') && form[i].attributes.getNamedItem('method').value == 'ajax' )
		{
			form[i].onsubmit = function(evt) {
				if( !JXHTML.checkForm(evt.target) )
					return false;
				
				if( ! evt.target.id ) evt.target.id = Util.generateId();
				
				Util.Ajax({
					url: (evt.target.attributes.getNamedItem('action')) ? evt.target.attributes.getNamedItem('action').value : window.location.href,
					callback: function(response, success, params){
						if( evt.target.attributes.getNamedItem('callback') )
						{
							try
							{
								eval("var func = "+evt.target.attributes.getNamedItem('callback').value);
								
								func(response, success, params);
							}
							catch(e)
							{
								eval(evt.target.attributes.getNamedItem('callback').value+"(response, success, params)");
							}
						}
					},
					form: evt.target.id, 
					method: "POST"
				});
				
				if( evt.target.attributes.getNamedItem('aftersubmit') )
					eval(evt.target.attributes.getNamedItem('aftersubmit').value+"('"+evt.target.id+"')");
				
				return false;
			};
		}
		// Only check fields
		else
		{
			// Don't use addEventListener because it will not block the submition (return false)
			form[i].onsubmit = function(evt) { return JXHTML.checkForm(evt.target); };
		}
	}
}
