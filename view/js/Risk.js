var Risk = function(id, g_id, g_step, m_id, adjacent, confirmed) {
	/**
	 * Callback for actionPerformed on click on a territory
	 */
	this.territoryClick = function(event) {
		var id = event.target.getAttributeNS(null,"id");
		
		if( id == '' ) return;
		
		switch(this.getStep()) {
			case '1':
				this.actionPerformed_step1(id);
				break;
			
			case '3':
				this.actionPerformed_step3(id);
				break;
		}
	};
	
	// Loops so for calcs, 5 => 1
	this.getStep = function() {
		if( this.g_step < 5 )
			return this.g_step;
		else
			return this.g_step %5 + 1;
	}
	
	this.actionPerformed_step1 = function(id) {
		if( (this.actionFrom == null) && Risk.checkClass(this.document.getElementById(id), 'player_'+this.m_id) )
		{
			this.actionFrom = id;
			alert('from : '+this.actionFrom);
		}
		else if( this.actionFrom != null )
		{
			if( Risk.checkAdjacent(this.actionFrom, id, this.adjacent) )
			{
				// From : To : Strengh : Priority
				this.actions.push(new Array(this.actionFrom, id, 1, 1));
				
				alert('from : '+this.actionFrom+' to '+id);
				this.actionFrom = null;
			}
			else if( this.actionFrom == id )
			{
				alert('reset');
				this.actionFrom = null;
			}
			else
			{
				alert('no link');
			}
		}
	}

	this.enableCursors = function() {
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
			terr[i].onclick = function(event) {
				risk.territoryClick(event);
			};
		}
	}
	
	this.disableCursors = function() {
		var terr = this.document.getElementsByTagName('g');
		for(var i=0; i<terr.length; i++)
		{
			if( !Risk.checkClass(terr[i], 'territory') ) continue;
			
			setStyle(terr[i], 'cursor', '');

			terr[i].onclick = null;
		}
	}
	
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
				callback: function(event) {
					risk.solveCallback(event);
				}
			}
		});
		
		this.disableCursors();
		$('risk_confirm').value="Attente...";
		$('risk_confirm').disabled=true;
		this.cron.start();
	}
	
	this.solveCallback = function(event) {
		// If return, then display changes and go to the next step !
	};
	
	/**
	 * Game Step :
	 * 	1 => Waiting for actions (attack, moving) from the player
	 *  2 => Waiting for response from the server
	 *  3 => Waiting for actions (placing reserve armies) from the player
	 *  4 => Waiting for response from the server
	 */
	this.actionStep = function() {
		switch(this.getStep())
		{
			case '1':
			case '3':
				this.enableCursors();
				break;
			
			case '2':
			case '4':
				this.wait();
				break;
		}
	}
	
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
		this.actionStep(); // He's not different from the others so same problem !
	}
	else
	{
		// User have confirmed, it's like the next step	for him
		this.confirm();
	}
};

Risk.checkClass = function (elt, className) {
	return elt.getAttributeNS(null,"class").indexOf(className) != -1
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
