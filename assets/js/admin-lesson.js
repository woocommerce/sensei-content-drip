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
			this.$el.find('.dripTypeOptions').hide();

			// exit if none with all elements hidden
			if( this.dripType === 'none' ){ 
				return;
			}  

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