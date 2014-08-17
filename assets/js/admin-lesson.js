( function( $, _ , Backbone  ){
	
	// the Drip meta box view reponsible for all things inside
	console.log($('#content-drip-lesson'));
	var DripMetaBox = Backbone.View.extend({

		el:  '#content-drip-lesson .inside',
		events: {
			'change .sdc-lesson-drip-type': 'dripTypeChange' 
		},
		/**
		* Initlize function, which runs after the object is returned 
		* with a new operator
		*/
		initialize: function(){
			this.dripType = 'none';
			this.render();
		},	

		/**
		* dripTypeChange, this function repsonds to a select box change event. 
		*/
		render: function(e){

			// on the inital page load run through each of the options 
			// and hide (add display: none) to the options that hass the class
			// hideen removing the hidden class as it is no longer needed
			this.$el.find('.dripTypeOptions').each(function(index , item ){
				if( $( item ).hasClass('hidden') ){
						$( item ).hide().removeClass('hidden');
				}; 
			});

			// exit if none with all elements hidden
			if( this.dripType === 'none' ){ 	
				return;
			}

			// show the selected drip type's options
			this.$el.find( '.dripTypeOptions.' + this.dripType).show();
		},
		/**
		* dripTypeChange, this function repsonds to a select box change event. 
		*/
		dripTypeChange: function(e){
			if( 'change' !== e.type || "sdc-lesson-drip-type" !== e.target.className ){
				return;
			}
			
			this.dripType =  e.target.value;
			this.render();
		},

	});

	window.dripMetaBox  = new DripMetaBox();
}( jQuery, _ , Backbone  ) );