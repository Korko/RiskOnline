var Effects = {};

/**
 * from: opacity at the beginning (defaut to 100)
 * duration: time in seconds for the effect (default to 10)
 */
Effects.hide = function(element, duration, from) {
	element = $(element);
	from = from || 100;
	duration = duration || 10;

	var step=5;
	var wait = Math.abs(duration/(from/step));

	if( from > 0 )
	{
		from = from - step;

		if(from <= 0){ element.style.display = 'none'; }

		setStyle(element, 'opacity', from);

		if( from > 0 ) setTimeout( "Effects.hide('"+element.id+"', "+duration+", "+from+");", wait);
	}

	return true;
}

/**
 * duration: time in seconds for the effect (default to 10)
 * from: max size
 */
Effects.fold = function(element, duration, from) {
	element = $(element);

	element.style.overflow = 'hidden';

	duration = duration || 10;
	from = parseInt(from) || element.clientHeight;

	var step = 5;
	var wait = duration/(from/step);

	if(from > 0)
	{
		from = from - step;
		if(from <= 0){ element.style.display = 'none'; }

		element.style.height = from + 'px';
		if( from > 0 ) setTimeout("Effects.fold('"+element.id+"', "+duration+", "+from+")", wait);
	}

	return true;
}

/**
 * duration: time in seconds for the effect (default to 10)
 * from: opacity from
 */
Effects.puff = function(element, duration, from) {
	element = $(element);

	duration = duration || 10;
	from = from || Effects.getOpacity(element);

	var step = 5;
	var wait = duration/(from/step);

	element.parentNode.style.overflow = 'visible';

	if(from > 0)
	{
		from = from - step;
		if(from <= 0){
			element.style.display = 'none';
		}

		var height = element.clientHeight*0.03;
		var width = element.clientWidth*0.03;

		setStyle(element, 'opacity', from);
		element.style.position = 'relative';
		element.style.width = element.clientWidth + width + 'px';
		element.style.height = element.clientHeight + height + 'px';

		element.style.left = Math.round(element.offsetLeft - element.parentNode.offsetLeft - width/2) + 'px';
		element.style.top = Math.round(element.offsetTop - element.parentNode.offsetTop - height/2) + 'px';

		if( from > 0 ) setTimeout("Effects.puff('"+element.id+"', "+duration+", "+from+")", wait);
	}

	element.parentNode.style.overflow = '';
	return true;
}

Effects.scrollTo = function(element, duration) {
	//
}

Effects.getOpacity = function(obj) {
	if( !obj ) return;

	obj = $(obj);

	if( obj.style.KHTMLOpacity ) return obj.style.KHTMLOpacity*100;
	if( obj.style.MozOpacity ) return obj.style.MozOpacity*100;
	if( obj.style.opacity ) return obj.style.opacity*100;
	//if( obj.style.filter ) return obj.style.filter; //Unworking

	return 100;
}