( function( $, _ , Backbone  ){
	
	// the Drip meta box view reponsible for all things dripped 
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
			this.setInitialDripType();
			this.takeControl();
			this.render();
		},

		/**
		* look at the select box and determine the intial dripType
		*/
		setInitialDripType: function(){
			//check the select box
			var currentSelection = this.$('select.sdc-lesson-drip-type').val();

			// set the drip type
			if( _.isEmpty( currentSelection ) ){
				this.dripType = 'none';
			} else {
				this.dripType = currentSelection;
			}

			return this;
		},
		

		/**
		* Initialize the metabox for so that visiblitly is complete controlled by this view
		* This function ads display: none to .hidden elements and remove the hidden class
		*/
		takeControl: function(){
			// removing the hidden class as it is no longer needed
			this.$el.find('.dripTypeOptions').each(function(index , item ){
				if(  $( item ).hasClass('hidden') ){
						$( item ).hide().removeClass('hidden');
				}; 
			});
		},	

		/**
		* dripTypeChange, this function repsonds to a select box change event. 
		*/
		render: function(e){


			//hide everything
			this.$el.find( '.dripTypeOptions').hide();
			
			// exit if none with all elements hidden
			if( this.dripType === 'none' ){ 	
				return;
			}

			// show the selected drip type's options
			this.$el.find( '.dripTypeOptions.' + this.dripType).show();
			console.log('render');
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