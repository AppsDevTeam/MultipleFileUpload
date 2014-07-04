var fallbackController = this;
(function($){
	// API: http://www.plupload.com/plupload/docs/api/index.html

	// Convert divs to queue widgets when the DOM is ready
	$(function(){
		
		var r = new Resumable({
			target: {$uploadLink|escapeJs|noescape},
			query: {},
			chunkSize: {$chunkSize},
			maxFiles: {$maxFiles},
			maxFileSize: {$sizeLimit},
		});
		// Resumable.js isn't supported, fall back on a different method
		if(!r.support) {
			fallbackController.fallback();
		}
		
		var $div = $('#'+{$id|escapeJs|noescape});
		var $progressvalue = $('#'+{$id|escapeJs|noescape}+'-progressvalue');
		var $cancel = $('#'+{$id|escapeJs|noescape}+'-cancel');
		$cancel.on('click', function(e){
			r.cancel();
		});
		
		var formLocks = 0;
		var $form = $div.closest('form');
		if ($form.length) {
			$form.on('submit', function(e){
				if (formLocks) {
					e.preventDefault();
					r.upload();
				}
			});
		}
		
		r.assignBrowse(document.getElementById({$id|escapeJs|noescape}+'-browse'));
		
		r.on('complete', function(){
			formLocks = 0;
			$form.trigger('submit');
		});
		r.on('fileSuccess', function(file){
			$form.trigger('submit');
		});
		r.on('fileProgress', function(file){
			$progressvalue.html(Math.floor(r.progress() * 100) + '%');
		});
		r.on('fileAdded', function(file, event){
			formLocks++;
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