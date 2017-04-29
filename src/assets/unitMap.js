// mapy api https://api.mapy.cz/view?page=markercard
;(function(){
	
	function loadScript(url,callback){
		var scriptTag = document.createElement('script');
		scriptTag.src = url;
		scriptTag.type = "text/javascript";
		document.body.appendChild(scriptTag);
		scriptTag.addEventListener('load', callback);
	}
	
	function loadMap(){
		Loader.async = true;
		Loader.load(null, null, createMap);
	}
	
	function createMap(){
		
		var mapEl = document.getElementById("unitMap");
		var marks = JSON.parse(mapEl.getAttribute("data-marks"));
		var markerUrl = "https://api.mapy.cz/img/api/marker/drop-red.png";
		var coords = [];
		
		var m = new SMap(mapEl);
		m.addDefaultLayer(SMap.DEF_BASE).enable();
		m.addDefaultControls();
		
		var markerLayer = new SMap.Layer.Marker();
		m.addLayer(markerLayer);
		markerLayer.enable();
		
		Object.keys(marks).forEach(function(key){
			var card = new SMap.Card();
			card.getHeader().innerHTML = "<strong>"+ marks[key].title +"</strong>";
			card.getBody().innerHTML = marks[key].desc;
			
			var c = SMap.Coords.fromWGS84(marks[key].lng, marks[key].lat);
			coords.push(c);

			var options = {
				url:markerUrl,
				title:marks[key].title,
				anchor: {left:10, bottom: 1}
			};

			var marker = new SMap.Marker(c, null, options);
			marker.decorate(SMap.Marker.Feature.Card, card);
			markerLayer.addMarker(marker);
		});
		
		var cz = m.computeCenterZoom(coords);
		m.setCenterZoom(cz[0], cz[1]); 
	}
	
	loadScript("https://api.mapy.cz/loader.js", loadMap);
	
}());