jQuery(function($){

	const lmgAddEvents = () => {

		$asaEvents = $('div#asa-events-hidden > div');

		$('div.mc-main table.my-calendar-table td:not(.nextmonth)').each(function(){
			let $td = $(this);
			let thisDate = Date.parse($td.find('span.mc-date span.screen-reader-text').text());

			$items = $asaEvents.filter(function(){
				return thisDate == Date.parse( $(this).find('.mc-start-date').text() );
			}).clone();

			if($items.length){
				$items.find('h3.event-title').on('click', function(){
					$(this).next('div.details').show();
				});

				$td.append($items);
				$td.addClass('has-events');
			}
			
		});
	};

	$(document).ready(function(){
		lmgAddEvents();
	});

	new MutationObserver(function(mutations, observer){
		lmgAddEvents();
	}).observe(document.querySelector('div.mc-main'), {childList: true});

});
