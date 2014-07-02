var fallbackController = this;
(function($){
	// API: http://www.plupload.com/plupload/docs/api/index.html

	// Convert divs to queue widgets when the DOM is ready
	$(function(){
		
		var r = new Resumable({
			target: {$uploadLink|escapeJs|noescape},
			query: { upload_token:'my_token'},
			chunkSize:10*1024,
		});
		// Resumable.js isn't supported, fall back on a different method
		if(!r.support) {
			fallbackController.fallback();
		}
		
		r.assignBrowse(document.getElementById({$id|escapeJs|noescape}));
		r.assignDrop(document.getElementById('cu-drop'));
		
		r.on('fileSuccess', function(file){
				console.debug('fileSuccess', file);
			});
		r.on('fileProgress', function(file){
				console.debug('fileProgress', file);
			});
		r.on('fileAdded', function(file, event){
				r.upload();
				//console.debug(file, event);
			});
		/*
		r.on('filesAdded', function(array){
				//console.debug(array);
			});
		r.on('fileRetry', function(file){
				//console.debug(file);
			});
		r.on('fileError', function(file, message){
				//console.debug(file, message);
			});
		r.on('uploadStart', function(){
				//console.debug();
			});
		r.on('complete', function(){
				//console.debug();
			});
		r.on('progress', function(){
				//console.debug();
			});
		r.on('error', function(message, file){
				//console.debug(message, file);
			});
		r.on('pause', function(){
				//console.debug();
			});
		r.on('cancel', function(){
				//console.debug();
			});
		*/
		
	});

	return true; // OK

})(jQuery);