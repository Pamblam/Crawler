/* Crawler - A website indexer (and stuff)
 * @author Robert Parham
 * @license Apache 2.0 Lic.
 */

$(document).ready(function(){

	$("#sform").submit(function(e){
		if($("#sinput").val() == "") return false;
		var d = new Date();
		var starttime = d.getMilliseconds(); 
		e.preventDefault();
		var term = $("#sinput").val();
		$("#sbtn").html("loading..");
		$.ajax({
			url: "api/",
			type: "POST",
			data: {action: "search", term: term}
		}).done(function(resp){
			var r = resp.data;
			var item, div;

			var ee = new Date();
			var stoptime = ee.getMilliseconds(); 
			var seconds = Math.abs(stoptime - starttime) / 1000;
			var totalSeconds = Math.round(seconds * 100) / 100;

			$("#results").empty();
			$("#results").append("<div> About "+r.length+" results ("+totalSeconds+" seconds)</div><br>");
			for(var i=0; i<r.length; i++){
				item = r[i];
				div = $("<div class='resdiv'><a href='"+strip(item['url'])+"' style='color: blue'><big>"+item['title']+"</big></a> <small><span class='glyphicon glyphicon-signal' style='color:red'></span> Relevance Score: "+item['match_score']+"</small><br><a href='"+strip(item['url'])+"' style='color: green'><small><span class='glyphicon glyphicon-link'></span> <i>"+item['url']+"</i></small></a><div>"+item['body']+"</div></div>");
				$("#results").append(div);
			}
			$("#sbtn").html('<span class="glyphicon glyphicon-search"></span>');
		});
	});

	var q = getParameterByName("q");
	if(q && q.length > 0){
		$("#sinput").val(q)
		$("#sform").submit();
	}

});	