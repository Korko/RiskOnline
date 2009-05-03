var Risk = function(id, m_id, adjacent) {
	/**
	 * Callback for actionPerformed on click on a territory
	 */
	this.territoryClick = function(event) {
		var id = event.target.getAttributeNS(null,"id");
		
		if( id == '' ) return;
		
		switch(this.gameStep) {
			case 0:
				this.actionPerformed_step0(id);
				break;
			
			case 2:
				this.actionPerformed_step2(id);
				break;
		}
	};
	
	this.actionPerformed_step0 = function(id) {
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
	
	/**
	 * Confirm all the actions performed and wait the answer from the server
	 */
	this.confirm = function() {
		//Util.Ajax({url: '?action=act&game={$game}&from='+from+'&to='+id});
	};
	
	/**
	 * Game Step :
	 * 	0 => Waiting for actions (attack, moving) from the player
	 *  1 => Waiting for response from the server
	 *  2 => Waiting for actions (placing reserve armies) from the player
	 *  3 => Waiting for response from the server
	 */
	this.gameStep = 0;
	this.m_id = m_id;
	this.actionFrom = null;
	this.document = $(id).getSVGDocument();
	this.actions = new Array();
	this.adjacent = adjacent;
	
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
