this.imagePreview = function(){	
	/* CONFIG */
		
		xOffset = 50;
		yOffset = 30;
		
		// these 2 variable determine popup's distance from the cursor
		// you might want to adjust to get the right result
		
	/* END CONFIG */
	jQuery("a.watermark_preview").hover(function(e){
		this.t = this.title;
		this.title = "";	
		var c = (this.t != "") ? "<br/>" + this.t : "";
		jQuery("body").append("<p id='watermark_preview'><img src='"+ this.href +"' alt='Image preview' />"+ c +"</p>");								 
		jQuery("#watermark_preview")
			.css("top",(e.pageY - xOffset) + "px")
			.css("left",(e.pageX + yOffset) + "px")
			.fadeIn("fast");						
    },
	function(){
		this.title = this.t;	
		jQuery("#watermark_preview").remove();
    });	
	jQuery("a.watermark_preview").mousemove(function(e){
		jQuery("#watermark_preview")
			.css("top",(e.pageY - xOffset) + "px")
			.css("left",(e.pageX + yOffset) + "px");
	});			
};

