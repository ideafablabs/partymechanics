var $ = jQuery.noConflict();

$(document).ready( function(){
	
	pageModel = {

		showAttended:false,
		filter:false,		
		refresh:function() {
			console.log("refreshing UI");

			$(".member_select_list .list-group-item").hide();		
			
			if (!this.filter) {
				$(".member_select_list .list-group-item").show();			
			}
			else if (this.filter == 'special-guests') {
				$(".member_select_list .special-guest").show();				
			}
			else if (this.filter == 'members') {
				$(".member_select_list .member").show();				
			}

			if (!this.showAttended) { $(" .member_select_list .attended").hide(); }
		}
	}

	// console.log("PartyMechanics: Plugin Active");

	$(".iflpm-member-table").on(
		'click',
		'.guest-list-toggle', 
		iflpm_ajax_guest_list_toggle
	);
		
	if ($(".nfc_button").length) {
		
		$(".nfc_button").on('click', iflpm_ajax_get_token_id_from_reader);
		
		// Lock submit button until good token is pulled.
		$('.submit-button').click(function(event){
    		event.preventDefault();
		});

		///
		// Interval for regular checking of token.
		// var tokenCheckIntervalID = setInterval(function(){
			// iflpm_ajax_get_token_id_from_reader();
		// }, 5000);

		// if new token, wipe checker? Maybe not in case they change their mind.
		// clearInterval(tokenCheckIntervalID);
	}
	
	$(".toggle-attended").click(function(event){
		var target = $(event.target);

		if (target.data("action") == 'show') {
			pageModel.showAttended = true;
			target.text("Hide Attended");
			target.data("action",'hide');
		} else {
			pageModel.showAttended = false;
			target.text("Show Attended");
			target.data("action",'show');
		}

		pageModel.refresh();
	});

	$(".toggle-members").click(function(event){
		var target = $(event.target);
			
		if (target.data("action") == 'show') {
			pageModel.filter = 'members';
			target.text("Hide Members");
			target.data("action",'hide');
		} else {
			pageModel.filter = false;
			target.text("Show Members");
			target.data("action",'show');
		}

		pageModel.refresh();
	});

	$(".toggle-guest-list").click(function(event){
		var target = $(event.target);
			
		if (target.data("action") == 'show') {
			pageModel.filter = 'special-guests';
			target.text("Hide Guest List");
			target.data("action",'hide');
		} else {
			pageModel.filter = false;
			target.text("Show Guest List");
			target.data("action",'show');
		}
		pageModel.refresh();
	});

	// Activate filterable tables/lists
	$("#q").on( 'keyup',
		{
			target:'.filterable',
			children:'.filter-item'			
		}
		,filter);

	// Auto focus on the search box when we load.
	setTimeout(function(){
		$("#q").focus();          
	},500);

});

/*
 	This makes an instant search filter.	
 */
function filter(event) {

	delay(function(){
		
		$(event.data.target).show();
		
		// First we create a variable for the value from the search-box.
		var searchTerm = $(event.target).val();

		// Then a variable for the list-items (to keep things clean).
		var listItem = $(event.data.target).children(event.data.children);
		
		// Extends the default :contains functionality to be case insensitive if you want case sensitive search, just remove this next chunk
		$.extend($.expr[':'], {
			'containsi': function(elem, i, match, array) {
				return (elem.textContent || elem.innerText || '').toLowerCase().indexOf((match[3] || "").toLowerCase()) >= 0;
			}
		}); // End of case insensitive chunk.

		// Optional
		// Here we are replacing the spaces with another :contains
		// What this does is to make the search less exact by searching all words and not full strings
		var searchSplit = searchTerm.replace(/ /g, "'):containsi('")
		
		// Here is the meat. We are searching the list based on the search terms
		$(event.data.target+" "+event.data.children).not(":containsi('" + searchSplit + "')").each(function(e)   {				  
			  $(this).addClass('hidden');
		});
		
		// This does the opposite -- brings items back into view
		$(event.data.target+" "+event.data.children+":containsi('" + searchSplit + "')").each(function(e) {				  
			$(this).removeClass('hidden');
		});
	
	},500);
}

// Setup to delay until user stops typing
var delay = (function(){
	var timer = 0;
	return function(callback, ms){
		clearTimeout (timer);
		timer = setTimeout(callback, ms);
	};
})();

// Front end ajax for getting token.
function iflpm_ajax_get_token_id_from_reader() {
			
	///TODO Add loading graphic 
	
	// Get the relevant data.
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
				/// UIdisplayMessage(response);	we could make a function that manages the UI message			
				var usermessage = '<p class="ajax-success">'+response.message+'</p>';
			}			
						
			$(".submit-button").addClass('active');
			$('.submit-button.active').unbind('click');
			
			// console.log(token_color);

			$(".token-response").html('<span class="token '+response.token_color+'" />');			
			

		// Or failure.
		} else {                
			// var usermessage = '<p class="ajax-error error">'+response.message+'</p>';                
			$(".ajax-message").prepend(build_wp_notice(response).fadeIn());	
		}

		// Give some sort of affirmation...
		/// 
			
	}

	// Send the package ==>
	iflpm_ajax_request(package);
			
}

// Admin ajax for guest list toggling
function iflpm_ajax_guest_list_toggle(event) {

	var target = event.target;

	///TODO Add loading graphic 
	// showLoader('guest-list',target);
	
	// Get the relevant data.	
	// var reader_id = jQuery(target).data( 'reader_id' );        
	var user_id = $(target).data( 'uid' );
	var event_id = $(target).data( 'event' );
	var action = $(target).data( 'action' );

	console.log("Toggling guest list for user: "+user_id+", event: "+event_id);
	
	$(target).html('<span class="loading"></span>');
	
	console.log(target);
	// Bundle the package.
	package = {
		target : event.target,
		request : 'guest_list_toggle',
		data : {
			action : action,
			user_id : user_id,
			event_id : event_id,			
		}
	}

	// Forsee outcomes.
	package.success = function(response) {
		
		// Actual success.
		if (response.success == true) {
			
			userRow = $("tr.user-"+user_id);

			if ($(target).data('action') == 'add') {
				$(target).data('action','remove');				
				$(target).html('Remove From Guest List');
				userRow.addClass('special-guest');
			} else {
				$(target).data('action','add');
				$(target).html('Add to Guest List');
				userRow.removeClass('special-guest');
			}			


		// Or failure.
		} else { 

		}
		// Give some sort of affirmation...
		$(".iflpm-wrap").prepend(build_wp_notice(response).fadeIn());		
	}

	// Send the package ==>
	iflpm_ajax_request(package);
			
}

// Build WP Notice box for AJAX actions.
function build_wp_notice(response) {

	// var testresponse = {
	// 	message:'test message',
	// 	notice : {
	// 		level:'',
	// 		display:true,
	// 		dismissible:true			
	// 	}
	// }

	if (!response.notice.display) return false;

	var notice = $('<div class="notice hidden"></div>').addClass(response.notice.level); 
	
	var noticeMessage = document.createElement('p'); 
	noticeMessage.innerText =response.message ;
	
	notice.append(noticeMessage);
	
	if (response.notice.dismissible) {		
		var dismissbutton = $('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
		notice.append(dismissbutton);
		notice.addClass('is-dismissible');

		dismissbutton.on('click',function(e){			
			$(e.target).parent('.notice').fadeOut();
		})
	}

	return notice;
}

function iflpm_ajax_request(package) {

	$.ajax({
		url : iflpm_ajax.ajaxurl,
		type : 'post',
		data : {
			action : 'iflpm_async_controller',                
			security : iflpm_ajax.check_nonce, 
			// target : package.target,
			request : package.request,
			package : package.data
		},		
		success : function( json ) {                
			console.log(json);
			var response = JSON.parse(json);
			package.success(response);			
		},
		complete : function() {
			// $(package.target).children('.loading').removeClass('loading');
		},
		error : function(jqXHR, textStatus, errorThrown) {
			console.log(jqXHR + " :: " + textStatus + " :: " + errorThrown);
		}
	});
}