/* Crawler - A website indexer (and stuff)
 * @author Robert Parham
 * @license Apache 2.0 Lic.
 */

$(document).ready(function(){

	$("#sform").submit(function(e){
		$("#sbtn").html("loading..");
		$.ajax({
			url: "api/",
			type: "POST",
			data: {action: "search", term: term}
		}).done(function(resp){
			
		});
	});

});	