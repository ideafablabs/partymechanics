var $ = jQuery.noConflict();

$(document).ready( function(){
	
	// $('.nfc-wrapper').insertAfter($(('#input_1_14')));	
	console.log("PartyMechanics: Plugin Active");

	$(".nfc_button").on('click', iflpm_ajax_get_token_id_from_reader);
	$(".iflpm-user-tokens").on('click','.guest-list-toggle', iflpm_ajax_guest_list_toggle);

	if ($(".nfc_button").length) {
	// if (0) {	
		
		$('.submit-button').click(function(event){
    		event.preventDefault();
		});

		reader_id = $(".nfc_button").attr('data-reader_id');		
		
		// var tokenCheckIntervalID = setInterval(function(){
			// iflpm_ajax_get_token_id_from_reader(reader_id);
		// }, 5000);

		// if new token, wipe checker? Maybe not in case they change their mind.
		// clearInterval(tokenCheckIntervalID);
	}
	

	/* RETURN ATTENDEE LIST */

	// SORT
	var attendees = $('.member_select_list'),
	attendeesli = attendees.children('li');

	attendeesli.sort(function(a,b) {
		var an = a.getAttribute('data-sort').toLowerCase(),
			bn = b.getAttribute('data-sort').toLowerCase();

		if(an > bn) {
			return 1;
		}
		if(an < bn) {
			return -1;
		}
		return 0;
	});

	// Hide Members List
	// $(".member_select_list").hide(); //Debug: show all the members. 

	attendeesli.detach().appendTo(attendees);

	/// try and async/await this?
	// Clear search / hide attendee list.
	$('.clear-search').on('click', function(e) { 
		// document.getElementById('q').value = '';
		$(".member_select_list").hide(); //Debug: show all the members. 
	});

	/*
	This makes an instant search for the gallery member sign-in list
		@jordan
	*/
	
	// Setup to delay until user stops typing
	var delay = (function(){
		var timer = 0;
		return function(callback, ms){
			clearTimeout (timer);
			timer = setTimeout(callback, ms);
		};
	})();

	//we want this function to fire whenever the user types in the search-box
	$(".member_select_search #q").keyup(function () {
		
		delay(function(){

			$(".member_select_list").show();
		
			//first we create a variable for the value from the search-box
			var searchTerm = $(".member_select_search #q").val();

			//then a variable for the list-items (to keep things clean)
			var listItem = $('.member_select_list').children('li');
			
			//extends the default :contains functionality to be case insensitive
			//if you want case sensitive search, just remove this next chunk
			$.extend($.expr[':'], {
			  'containsi': function(elem, i, match, array)
			  {
				return (elem.textContent || elem.innerText || '').toLowerCase()
				.indexOf((match[3] || "").toLowerCase()) >= 0;
			  }
			});//end of case insensitive chunk

			//this part is optional
			//here we are replacing the spaces with another :contains
			//what this does is to make the search less exact by searching all words and not full strings
			var searchSplit = searchTerm.replace(/ /g, "'):containsi('")
			
			//here is the meat. We are searching the list based on the search terms
			$(".member_select_list li").not(":containsi('" + searchSplit + "')").each(function(e)   {

				  //add a "hidden" class that will remove the item from the list
				  $(this).addClass('hidden');

			});
			
			//this does the opposite -- brings items back into view
			$(".member_select_list li:containsi('" + searchSplit + "')").each(function(e) {

				  //remove the hidden class (reintroduce the item to the list)
				  $(this).removeClass('hidden');

			});

			// SORT
			var attendees = $('.member_select_list'),
			attendeesli = attendees.children('li');

			attendeesli.sort(function(a,b){
				var an = a.getAttribute('data-sort').toLowerCase(),
					bn = b.getAttribute('data-sort').toLowerCase();

				if(an > bn) {
					return 1;
				}
				if(an < bn) {
					return -1;
				}
				return 0;
			});
		}, 500 );
	}); 

	// Auto focus on the search box when we load.
	setTimeout(function(){
		$(".member_select_search #q").focus();          
	},0);


});

function iflpm_ajax_get_token_id_from_reader(reader_id) {
			
	//TODO Add loading graphic 
	
	// Get the relevant data.
	// var reader_id = jQuery(target).data( 'reader_id' );        
	var reader_id = $(".nfc_button").data( 'reader_id' );
	console.log("Getting Token from reader "+reader_id);
	
	// Bundle the package.
	package = {
		request : 'get_token',
		data : {
			reader_id : reader_id
		}
	}

	// Forsee outcomes.
	package.success = function(response) {
		
		// Actual success.
		if (response.success == true) {                
			if (response.message) {
				var usermessage = '<p class="ajax-success">'+response.message+'</p>';
			}
			// response.token_id;
			var usermessage = '<p class="ajax-success">'+response.token_id+'</p>';
			
			$(".submit-button").addClass('active');
			$('.submit-button.active').unbind('click');

			/// Gotta change this if we change the code. Find a new way!
			// var new_token = '<li>'+response.token_id+' <a class="remove-token icon" data-tid="'+response.token_id+'">x</a></li>';
			// $('tr.user-'+uid+' .user-tokens ul').append(new_token);

			// $(target).parent('tr').children('.user-tokens ul')

		// Or failure.
		} else {                
			var usermessage = '<p class="ajax-error">'+response.message+'</p>';                
		}

		// Give some sort of affirmation...
		$(".ajax-message").html(usermessage);
		console.log(response.message);
	}

	// Send the package ==>
	iflpm_ajax_request(package);
			
}

function iflpm_ajax_guest_list_toggle(event) {
			
	//TODO Add loading graphic 
	
	var target = event.target;

	// Get the relevant data.	
	// var reader_id = jQuery(target).data( 'reader_id' );        
	var user_id = $(target).data( 'uid' );
	var event_id = $(target).data( 'event' );
	var action = $(target).data( 'action' );

	console.log("Toggling guest list for user: "+user_id+", event: "+event_id);
	
	// Bundle the package.
	package = {
		request : 'guest_list_toggle',
		data : {
			action : action,
			user_id : user_id,
			event_id : event_id
		}
	}

	// Forsee outcomes.
	package.success = function(response) {
		
		// Actual success.
		if (response.success == true) {                
			if (response.message) {
				var usermessage = '<p class="ajax-success">'+response.message+'</p>';
			}
			
			userRow = $("tr.user-"+user_id);

			if ($(target).data('action') == 'add') {
				$(target).data('action','remove');
				userRow.addClass('guest-list-active');
			} else {
				$(target).data('action','add');
				userRow.removeClass('guest-list-active');
			}			

		// Or failure.
		} else { 
			var usermessage = '<p class="ajax-error">'+response.message+'</p>';                
		}

		// Give some sort of affirmation...
		$(".ajax-message").html(usermessage);
		console.log(response.message);
	}

	// Send the package ==>
	iflpm_ajax_request(package);
			
}

function iflpm_ajax_request(package) {

	$.ajax({
		url : iflpm_ajax.ajaxurl,
		type : 'post',
		data : {
			action : 'iflpm_async_controller',                
			security : iflpm_ajax.check_nonce, 
			request : package.request,
			package : package.data
		},
		success : function( json ) {                
			console.log(json);
			var response = JSON.parse(json);
			package.success(response);
		},
		error : function(jqXHR, textStatus, errorThrown) {
			console.log(jqXHR + " :: " + textStatus + " :: " + errorThrown);
		}
	});
}

// Sort list function. /// Deprecated?
function sortUnorderedList(ul, sortDescending) {
	if(typeof ul == "string")
		ul = document.getElementById(ul);

	// Idiot-proof, remove if you want
	if(!ul) {
		alert("The UL object is null!");
		return;
	}

	// Get the list items and setup an array for sorting
	var lis = ul.getElementsByTagName("LI");
	var vals = [];

	// Populate the array
	for(var i = 0, l = lis.length; i < l; i++)
		vals.push(lis[i].innerHTML);

	// Sort it
	vals.sort();

	// Sometimes you gotta DESC
	if(sortDescending)
		vals.reverse();

	// Change the list on the page
	for(var i = 0, l = lis.length; i < l; i++)
		lis[i].innerHTML = vals[i];
}
