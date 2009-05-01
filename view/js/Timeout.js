var Timeout = {};

Timeout.ajax = function(config) {
	this.config = config;
	this.timeout = config.timeout;
	
	// desactive le timeout
	this.config.timeout = 0;
	
	this.socket = function() {
		if( this.config.callfront )
			this.config.callfront(this.config);
		
		Util.Ajax(this.config.config);
		
		if( this.config.callback )
			this.config.callback(this.config);
	};

	this.timer = function() {
		this.socket();
		
		if( this.config.timeout > 0 )
		{
			obj = this;
			setTimeout("obj.timer();", this.config.timeout);
		}
	};
	
	this.start = function() {
		this.config.timeout = this.timeout;
		this.timer();
	};
	
	this.stop = function() {
		this.config.timeout = 0;
	}
};
