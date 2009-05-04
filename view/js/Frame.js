var Frame = function(config, ret) {
	var parent = config.renderTo;

	if( !config.items ) config.items = new Array();
	if( !config.config ) config.config = null;

	switch(config.layout) {
		case 'border':
			var items = new BorderLayout(config.items, config.config);
			break;

		case 'form':
			var items = new FormLayout(config.items, config.config);
			break;

		case 'absolute':
		default:
			var items = new AbsoluteLayout(config.items, config.config);
	}

	if( ret )
	{
		return items;
	}
	else
	{
		for(var i=0; i<items.length; i++)
		{
			document.body.appendChild(items[i]);
		}
	}
}

var FormLayout = function(items, config) {
	var all = document.createElement('fieldset');
	all.id = (config.id) ? config.id : Util.generateId();
	setStyle(all, 'width', config.width);

	var labels = document.createElement('div');
	setStyle(labels, 'float', 'left');
	setStyle(labels, 'width', '50%');

	var inputs = document.createElement('div');
	setStyle(inputs, 'float', 'left');
	setStyle(inputs, 'width', '50%');

	all.appendChild(labels);
	all.appendChild(inputs);

	for(var i=0; i<items.length; i++)
	{
		var id = (items[i].id) ? items[i].id : Util.generateId();

		var elt_input = document.createElement('input'); // TODO
		elt_input.type = items[i].type;
		elt_input.name = items[i].name;
		elt_input.id = id;
		setStyle(elt_input, 'display', 'block');
		inputs.appendChild(elt_input);

		var elt_label = document.createElement('label');
		elt_label.textContent = items[i].lang;
		elt_label.htmlFor = id;
		setStyle(elt_label, 'display', 'block');
		labels.appendChild(elt_label);


	}

	var r_items = new Array();
	r_items.push(all);
	return r_items;
}

var AbsoluteLayout = function(items, config) {
	var r_items = new Array();

	for(var i=0; i<items.length; i++)
	{
		var elt = document.createElement('div');
		elt.className = 'frame';
		elt.id = (items[i].id) ? items[i].id : '';

		setStyle(elt, 'position', 'relative');
		setStyle(elt, 'left', (items[i].x) ? items[i].x + 'px' : 0);
		setStyle(elt, 'top', (items[i].y) ? items[i].y + 'px' : 0);
		setStyle(elt, 'width', (items[i].width) ? items[i].width + 'px' : 0);
		setStyle(elt, 'height', (items[i].height) ? items[i].height + 'px' : 0);
		setStyle(elt, 'clear', 'both');

		var title = document.createElement('h1');
		title.textContent = items[i].title;
		setStyle(title, 'margin-top', 0);
		elt.appendChild(title);

		var content = document.createElement('div');
		content.innerHTML = items[i].html;
		elt.appendChild(content);

		r_items.push(elt);
	}

	return r_items;
}

var BorderLayout = function(items, config) {
	var north;
	var south;
	var east;
	var west;
	var center;

	var r_items = new Array();

	for(var i=0; i<items.length; i++)
	{
		var elt = document.createElement('div');
		elt.id = (items[i].id) ? items[i].id : '';
		setStyle(elt, 'width', (items[i].width) ? items[i].width + 'px' : '');
		//setStyle(elt, 'height', (items[i].height) ? items[i].height + 'px' : '');

		var title = document.createElement('h1');
		title.textContent = items[i].title;
		setStyle(title, 'margin-top', 0);
		elt.appendChild(title);

		var content = document.createElement('div');
		content.innerHTML = items[i].html;
		elt.appendChild(content);

		switch( items[i].border ) {
			case 'west':
				west = elt;
				break;

			case 'east':
				east = elt;
				break;

			case 'north':
				north = elt;
				break;

			case 'south':
				south = elt;
				break;

			case 'center':
			default:
				center = elt;
		}
	}

	if( north )
	{
		setStyle(north, 'background-color', '#EEE');
		setStyle(south, 'clear', 'both');
		r_items.push(north);
	}

	if( west )
	{
		setStyle(west, 'float', 'left');
		setStyle(west, 'width', '100px');
		setStyle(west, 'background-color', '#DDD');
		r_items.push(west);
	}

	if( east )
	{
		setStyle(east, 'float', 'right');
		setStyle(east, 'width', '100px');
		setStyle(east, 'background-color', '#DDD');
		r_items.push(east);
	}

	if( center )
	{
		setStyle(center, 'background-color', '#CCC');
		setStyle(center, 'margin', 0);
		//setStyle(center, 'padding', '0px 160px 5px 160px');
		r_items.push(center);
	}

	if( south )
	{
		setStyle(south, 'clear', 'both');
		setStyle(south, 'background-color', '#EEE');
		r_items.push(south);
	}

	return r_items;
}