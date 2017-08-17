/* Crawler - A website indexer (and stuff)
 * @author Robert Parham
 * @license Apache 2.0 Lic.
 */

$(function(){
	var d = new Date();
	var starttime = d.getMilliseconds(); 
	$.ajax({
		url: "api/",
		type: "POST",
		data: {action: "emails" }
	}).done(function(resp){
		var r = resp.data;

		var item, div;

		var ee = new Date();
		var stoptime = ee.getMilliseconds(); 
		var seconds = Math.abs(stoptime - starttime) / 1000;
		var totalSeconds = Math.round(seconds * 100) / 100;

		$("#results").empty();
		$("#results").append("<div> About "+r.length+" emails found ("+totalSeconds+" seconds)</div><br>");
		var $ta = $("<textarea class='form-control' rows='5'></textarea>");
		$ta.val(r.join("\n"));
		var $div = $('<div class="form-group"></div>');
		$div.append($ta);
		$("#results").append($div);
	});
});