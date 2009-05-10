var Risk = function(id, g_id, g_step, m_id, adjacent, confirmed) {
	this.getArrow = function(str1, str2) {
		var tostr = 'Arrow-';

		var parts = str1.split('_');
		for(var i=0; i<parts.length; i++)
		{
			tostr += parts[i][0].toUpperCase() + parts[i].substring(1);
		}
		
		tostr += '-';
		
		var parts = str2.split('_');
		for(var i=0; i<parts.length; i++)
		{
			tostr += parts[i][0].toUpperCase() + parts[i].substring(1);
		}
		
		return this.document.getElementById(tostr);
	}
	
	// Loops so for calcs, 5 => 1
	this.getStep = function() {
		if( this.g_step < 5 )
			return this.g_step;
		else
			return this.g_step %5 + 1;
	}
	
	this.displayArrows = function(from) {
		var neighbors = Risk.getAdjacent(from, this.adjacent);
		
		for(var i=0; i<neighbors.length; i++)
		{
			var arrow = this.getArrow(neighbors[i][0], neighbors[i][1]);
			
			if( arrow != null )
			{
				setStyle(arrow, 'display', 'inline');
			}
		}
	}
	
	this.isInActions = function(from, to) {
		for(var i=0; i<this.actions.length; i++)
		{
			if( this.actions[i][0] == from && this.actions[i][1] == to )
			{
				return true;
			}
		}
		
		return false;
	}
	
	this.hideArrows = function(from) {
		var neighbors = Risk.getAdjacent(from, this.adjacent);
		
		for(var i=0; i<neighbors.length; i++)
		{
			var arrow = this.getArrow(neighbors[i][0], neighbors[i][1]);
			
			if( arrow != null && !this.isInActions(neighbors[i][0], neighbors[i][1]) )
			{
				setStyle(arrow, 'display', 'none');
			}
		}
	}
	
	this.getTroops = function(id) {
		var troops = (this.document.getElementById(id+'_troops')) ? parseInt(this.document.getElementById(id+'_troops').textContent) : -1;
		return troops;
	}
	
	this.setTroops = function(id, troops) {
		if(this.document.getElementById(id+'_troops')) {
			this.document.getElementById(id+'_troops').textContent = ""+troops+"";
		}
	}
	
	this.actionPerformed_step2 = function(id) {
		
	}
	
	this.actionPerformed_step1 = function(id) {
		if( (this.actionFrom == null) && Risk.checkClass(this.document.getElementById(id), 'player_'+this.m_id) && this.getTroops(id) > 1 )
		{
			this.displayArrows(id);
			this.actionFrom = id;
		}
		else if( this.actionFrom != null )
		{
			if( this.actionFrom == id )
			{
				this.hideArrows(this.actionFrom);
				this.actionFrom = null;
			}
			else if( Risk.checkAdjacent(this.actionFrom, id, this.adjacent) )
			{
				this.addAction(this.actionFrom, id, 1, 1);
			}
			else
			{
				alert('no link');
			}
		}
	}

	this.evtMouse = function(event) {
		var id = event.target.getAttributeNS(null,"id");
		
		if( id == '' )
			return;
			
		/**
		 * Game Step :
		 * 	1 => Waiting for actions (attack, moving) from the player
		 *  2 => Waiting for actions (placing reserve armies) from the player
		 */
		switch(this.getStep()) {
			case '1':
				this.actionPerformed_step1(id);
				break;
			
			case '2':
				this.actionPerformed_step2(id);
				break;
		}
	};
	
	this.enableCursors = function() {
		$('risk_confirm').value="Valider";
		$('risk_confirm').disabled=false;
		
		var terr = this.document.getElementsByTagName('g');
		for(var i=0; i<terr.length; i++)
		{
			if( !Risk.checkClass(terr[i], 'territory') ) continue;
			
			if( Risk.checkClass(terr[i], 'player_'+this.m_id) )
			{
				setStyle(terr[i], 'cursor', 'pointer');
			}
			else
			{
				setStyle(terr[i], 'cursor', 'url(view/sword.cur), crosshair');
			}
			
			var risk=this;
			terr[i].addEventListener("click", function(event) {
				risk.evtMouse(event);
			}, false);
		}
	};
	
	this.disableCursors = function() {
		$('risk_confirm').value="Attente...";
		$('risk_confirm').disabled=true;
		
		var terr = this.document.getElementsByTagName('g');
		for(var i=0; i<terr.length; i++)
		{
			if( !Risk.checkClass(terr[i], 'territory') ) continue;
			
			setStyle(terr[i], 'cursor', '');

			terr[i].onclick = null;
		}
	};
	
	/**
	 * Confirm all the actions performed and wait the answer from the server
	 */
	this.confirm = function() {		
		if( this.actions.length > 0 )
		{
			var postvars = '';
			
			// Actions to submit
			for(var i=0; i<this.actions.length; i++)
			{
				postvars += 'act[]='+this.actions[i][0]+';'+this.actions[i][1]+';'+this.actions[i][2]+';'+this.actions[i][3]+'&';
			}
			
			this.actions = new Array();
			
			Util.Ajax({
				url: '?action=act&game='+this.g_id,
				method: 'POST',
				args: postvars
			});
		}
		
		this.g_step++;
		this.wait();
	};
	
	this.wait = function() {
		var risk=this;
		this.cron = new Timeout.ajax({
			timeout: 3000,
			config: {
				url: '?action=solve&game='+this.g_id+'&step='+this.g_step,
				synchrone: false,
				callback: function(event) {
					risk.solveCallback(event);
				}
			}
		});
		
		this.cron.start();
	};
	
	this.solveCallback = function(event) {
		var confirmed = jeval(event.responseText);
		
		if( confirmed.confirm == 1 )
		{
			this.cron.stop();
			this.enableCursors();
		}
	};
	
	this.addAction = function(from, to, strength, priority) {
		if( !Risk.checkAdjacent(from, to, this.adjacent) || this.getTroops(from) <= strength ) {
			return;
		}
		
		var founded=false;
		
		// Is there any action with the same from and to ?
		for(var i=0; i<this.actions.length; i++)
		{
			if( this.actions[i][0] == from && this.actions[i][1] == to )
			{
				founded=true;
				this.actions[i][2]++;
			}	
		}
		
		if( !founded )
		{
			// From : To : Strengh : Priority
			this.actions.push(new Array(from, to, strength, priority));
		}
		
		var arrow = this.getArrow(from, to);
		
		if( arrow != null )
			Risk.addClass(arrow, 'action');
		
		this.setTroops(from, this.getTroops(from)-strength);
		this.hideArrows(from);
		this.actionFrom = null;
	};
	
	// Tempo
	this.actionFrom = null;
	
	this.g_step = g_step;
	this.m_id = m_id;
	this.g_id = g_id;
	this.document = $(id).getSVGDocument();
	this.actions = new Array();
	this.adjacent = adjacent;
	this.cron = null;
	
	if( confirmed == '0') {
		this.enableCursors();
	}
	else
	{
		// User have confirmed, it's like the next step	for him
		this.disableCursors();
		this.confirm();
	}
};

Risk.checkClass = function (elt, className) {
	return elt.getAttributeNS(null,"class").indexOf(className) != -1
}

Risk.addClass = function (elt, className) {
	if( Risk.checkClass(elt, className) )
		return;
		
	var totalClass = elt.getAttributeNS(null, "class");
	totalClass += " "+className;
	elt.setAttributeNS(null, "class", totalClass);
}

Risk.removeClass = function (elt, className) {
	if( !Risk.checkClass(elt, className) )
		return;
		
	var totalClass = elt.getAttributeNS(null, "class");
	totalClass.replace(className, '');
	elt.setAttributeNS(null, "class", totalClass);
}

Risk.getAdjacent = function(from, adjacent) {
	var array = new Array();
	
	for(var i=0; i<adjacent.length; i++)
	{
		if( adjacent[i][0] == from )
		{
			array.push(new Array(from, adjacent[i][1]));
		}
		else if( adjacent[i][1] == from )
		{
			array.push(new Array(from, adjacent[i][0]));
		}
	}
	
	return array;
}

Risk.checkAdjacent = function (from, to, adjacent) {
	check = false;
	
	for(var i=0; i<adjacent.length; i++)
	{
		if( ( adjacent[i][0] == from && adjacent[i][1] == to ) || ( adjacent[i][0] == to && adjacent[i][1] == from ))
		{
			check = true;
		}
	}
	
	return check;
}
