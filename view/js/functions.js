function $() {
	var elements = new Array();
	for (var i = 0; i < arguments.length; i++) {
		var element = arguments[i];

		if (typeof element == 'string')
			element = document.getElementById(element);

		if (!element.id)
			element.id = new Date().getTime();

		if (arguments.length == 1)
			return element;

		elements.push(element);
	}
	return elements;
}

function $_(className, refNode, tag)
{
	var tag = tag || '*';
	var refNode = refNode || document;

	if( (typeof refNode) == 'string' )
	{
		refNode = document.getElementById(refNode);
	}

	var nodesList = (tag == '*' && refNode.all) ? refNode.all : refNode.getElementsByTagName(tag);

	var result = new Array();

	for(var i=0; i<nodesList.length; i++)
	{
		if( isClass(nodesList[i], className) )
		{
			result.push(nodesList[i]);
		}
	}

	return result;
}

function bind(obj, methodName, args){
    return function(event){obj[methodName](event, args);}
}

function isClass(obj, className) {
	var regexClass = new RegExp("(^|s)" + className + "(s|$)");

	return regexClass.test(obj.className);
}

function sleep(time) {
	begin = new Date();

	while(new Date.getTime()-begin > time) {}

	return true;
}

function setStyle(obj, param, value) {
	switch(param) {
		case 'float':
			obj.style.cssFloat = value; // Firefox
			obj.style.styleFloat = value; // IE
			break;

		case 'background-color':
			obj.style.backgroundColor = value;
			break;

		case 'margin-top':
			obj.style.marginTop = value;
			break;

		case 'margin-left':
			obj.style.marginLeft = value;
			break;

		case 'margin-right':
			obj.style.marginRight = value;
			break;

		case 'margin-bottom':
			obj.style.marginBottom = value;
			break;

		case 'list-style':
			obj.style.listStyle = value;
			break;

		case 'font-family':
			obj.style.fontFamily = value;
			break;

		case 'font-style':
			obj.style.fontStyle = value;
			break;

		case 'font-variant':
			obj.style.fontVariant = value;
			break;

		case 'font-weight':
			obj.style.fontWeight = value;
			break;

		case 'line-height':
			obj.style.lineHeight = value;
			break;

		case 'text-align':
			obj.style.textAlign = value;
			break;

		case 'text-decoration':
			obj.style.textDecoration = value;
			break;

		case 'text-indent':
			obj.style.textIndent = value;
			break;

		case 'text-transform':
			obj.style.textTransform = value;
			break;

		case 'vertical-align':
			obj.style.verticalAlign = value;
			break;

		case 'opacity':
			obj.style.filter = "alpha(opacity:"+value+")"; // IE/Win
			obj.style.KHTMLOpacity = value/100; // Safari<1.2, Konqueror
			obj.style.MozOpacity = value/100; // Older Mozilla and Firefox
			obj.style.opacity = value/100; // Safari 1.2, newer Firefox and Mozilla, CSS3
			break;

		default:
			obj.style[param] = value;
	}
}

function getStyle(obj, style) {
	// TODO
}

function strreplace(search, replace, totalstring) {
	var begin = totalstring.indexOf(search);
	
	if( begin == -1 )
		return totalstring;
		
	var before = totalstring.substring(0, begin);
	
	var after = totalstring.substring(begin+search.length, totalstring.length);
	
	return before+replace+after;
}
