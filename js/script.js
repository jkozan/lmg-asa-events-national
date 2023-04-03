jQuery(function($){

	const lmgAddEvents = () => {

		$asaEvents = $('div#asa-events-hidden > div');

		$('div.mc-main table.my-calendar-table td:not(.nextmonth)').each(function(){
			let $td = $(this);
			let tdDate = Date.parse($td.find('span.mc-date span.screen-reader-text').text());

			$items = $asaEvents.filter(function(){
				let itemStartDate = Date.parse($(this).find('.mc-start-date').text());
				let itemEndDate = Date.parse($(this).find('.mc-end-date').text());

				if(
						(
							   (tdDate == itemStartDate) 
							|| (tdDate > itemStartDate && tdDate < itemEndDate) 
							|| (tdDate == itemEndDate)
						)

						/*&&

						(

							// TODO: prevent duplicates

						)*/
				){
					return true;
				}else{
					return false;
				}
			}).clone();

			if($items.length){
				$items.find('h3.event-title').on('click', function(){
					$(this).next('div.details').show();
				});

				$td.append($items);
				$td.addClass('has-events');
			}
			
		});


		let i = 0;
		$('div.asa-events-calendar ul.upcoming-events > li.upcoming-event').each(function(){
			i++;
			console.log('kk');

		});
	};

	$(document).ready(function(){
		lmgAddEvents();
	});

	new MutationObserver(function(mutations, observer){
		lmgAddEvents();
	}).observe(document.querySelector('div.mc-main'), {childList: true});

});
