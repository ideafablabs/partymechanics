var $ = jQuery.noConflict();

$(document).ready( function(){
    

    // $('.nfc-wrapper').insertAfter($(('#input_1_14')));

    // console.log("tetstetsetets");

    /* REGISTRATION FORM */
    $('button.get_token_id').click(function(){

        // get reader id
        reader_id = 1;
        reader_value = 0;
        url = "https://mint.ideafablabs.com/index.php/wp-json/mint/v1/readers/"+reader_id;
        // get reader value.
        $.get(url,function(data,status) {
            console.log(`${data}`);
            // reader_value = data.reader;
            $("#nfcid").val(data);
        });
        // 

        

        // if (reader_value != ) {

            // get form field.
            

        // }

    });

    /* RETURN ATTENDEE LIST */

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

    // Hide Members List
    $(".member_select_list").hide(); //Debug: show all the members. 

    attendeesli.detach().appendTo(attendees);

    console.log("Plugin Active");

    $('a.admit-button').on('click', function(e) { 
        
        e.preventDefault();
        console.log("Admitting Attendee...");
        $(e.target).addClass('admitting');
                
        //TODO Add loading graphic 

        // var iflag_entry_id = jQuery(this).data( 'id' );    

        $.ajax({
            url : iflpm_ajax.ajax_url,
            type : 'post',
            data : {
                action : 'ifl_admit_guest',
                entry_id : $(this).data( 'entry' ) ,
                attendee_id : $(this).data( 'attendee' )
            },
            // security : iflpm_ajax.check_nonce,
            success : function( response ) {
                console.log("Success!");
                console.log(response);
                $(e.target).addClass('admitted');
                // jQuery('.iflag_contents').html(response);
            }
        });
    /*
        $.ajax({
            url : iflpm_ajax.ajax_url,
            type : 'post',
            data : {
                action : 'ifl_sanity_check'
                // entry_id : $(this).data( 'entry' ) ,
                // attended_id : $(this).data( 'attended' )
            },
            // security : iflpm_ajax.check_nonce,
            success : function( response ) {
                console.log("Success!");
                console.log(response);                
            }
        });*/
        
        // jQuery(this).hide();            
    });     

    // Clear search / hide attendee list.
    $('.clear-search').on('click', function(e) { 
        // document.getElementById('q').value = '';
        $(".member_select_list").hide(); //Debug: show all the members. 
    });

    $('a.admit-all').on('click', function(e) { 
        
        e.preventDefault();
        console.log("Admitting All Attendees...");
        $(e.target).addClass('admitting');
                
        //TODO Add loading graphic 

        // var iflag_entry_id = jQuery(this).data( 'id' );    
        
        $.ajax({
            url : iflpm_ajax.ajax_url,
            type : 'post',
            data : {
                action : 'ifl_admit_all',
                entry_id : $(this).data( 'entry' ) ,
                // attended_id : $(this).data( 'attended' )
            },
            // security : iflpm_ajax.check_nonce,
            success : function( response ) {
                console.log("Success!");
                console.log(response);
                $(e.target).parent('div').children('.admit-button').addClass('admitted');
                // jQuery('.iflag_contents').html(response);
            }
        });
        
        // jQuery(this).hide();            
    });



    // Sort list function.
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


// /


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

    if ($(".nfc_button").length) {
        reader_id = $(".nfc_button").attr('data-reader_id');        
        setTimeout(ajax_get_token_id_from_reader(reader_id),3000);
    }

});

function ajax_get_token_id_from_reader(reader_id) {
            
    console.log("Getting Token from reader "+reader_id);                
    //TODO Add loading graphic 
    
    $.ajax({
        url : iflpm_ajax.ajax_url,            
        type : 'get',
        data : {
            action : 'iflpm_get_token_from_reader',
            reader_id : reader_id
        },
        
        // security : iflpm_ajax.check_nonce,
        success : function( response ) {
            console.log("Success!");
            console.log(response);
            $('.token_id').html(response);            
        }
    });
            
}

function ajax_associate_medallion_with_user(reader_id,user_id) {
            
    console.log("Associating Token with user "+user_id+" with reader: ");                
    //TODO Add loading graphic 
    
    $.ajax({
        url : iflpm_ajax.ajax_url,            
        type : 'get',
        data : {
            action : 'iflpm_associate_user_with_token_from_reader',
            reader_id : reader_id,
            user_id : user_id
        },
        
        // security : iflpm_ajax.check_nonce,
        success : function( response ) {
            console.log("Success!");
            console.log(response);
            $('.token-response').html(response);
        }
    });
            
}