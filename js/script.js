jQuery(function($){

	const lmgAddEventsToCal = () => {

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

	};

	const lmgAddEventsToList = () => {

		$('ul.upcoming-events').append($('div#asa-list-hidden > li'))

		$('ul.upcoming-events > li.upcoming-event').sort(function(a, b){
			let dateAttrA = $(a).attr('data-startdate');
			if (typeof dateAttrA !== typeof undefined && dateAttrA !== false) {
				dateAttrA = Date.parse(dateAttrA);
			}else{
				dateAttrA = Date.parse($(a).find('.asa-event-date .mc_db').text());
			}

			let dateAttrB = $(b).attr('data-startdate');
			if (typeof dateAttrB !== typeof undefined && dateAttrB !== false) {
				dateAttrB = Date.parse(dateAttrB);
			}else{
				dateAttrB = Date.parse($(b).find('.asa-event-date .mc_db').text());
			}

			return (dateAttrA > dateAttrB) ? 1 : -1;
		}).appendTo('ul.upcoming-events');

	};

	$(document).ready(function(){
		lmgAddEventsToCal();
		lmgAddEventsToList();
	});

	new MutationObserver(function(mutations, observer){
		lmgAddEventsToCal();
	}).observe(document.querySelector('div.mc-main'), {childList: true});

});
