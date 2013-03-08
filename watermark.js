this.imagePreview = function(){	
	/* CONFIG */
		
		yOffset = 100;
		xOffset = -320;
		
		// these 2 variable determine popup's distance from the cursor
		// you might want to adjust to get the right result
		
	/* END CONFIG */
	jQuery("a.watermark_preview").hover(function(e){
		this.t = this.title;
		this.title = "";	
		
		var c = (this.t != "") ? "<br/>" + this.t : "";
		jQuery("body").append("<p id='watermark_preview'><img class='watermark_preview_image' src='"+ this.href +"' alt='Image preview' />"+ c +"</p>");	
		
		var img = jQuery('.watermark_preview_image');
			   
		yOffset = (img.height()) ? (img.height() + 50) : 100;
		xOffset = (img.width()) ? (img.width()*(-.5)) : -320;
		
		jQuery("#watermark_preview")
			.css("top",(e.pageY - yOffset) + "px")
			.css("left",(e.pageX + xOffset) + "px")
			.fadeIn("fast");						
    },
	function(e){
		this.title = this.t;	
		jQuery("#watermark_preview").remove();
    });	
	
	jQuery("a.watermark_preview").mousemove(function(e){
		jQuery("#watermark_preview")
			.css("top",(e.pageY - yOffset) + "px")
			.css("left",(e.pageX + xOffset) + "px");
	});	
	
};

