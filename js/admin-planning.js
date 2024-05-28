$j(".ba-plus-edit-btn").click(function (e) {
    e.preventDefault();
    var event_id = $j(this).closest('.ba-planning-event-box').data('event-id');
    var event_name = $j(this).closest('.ba-planning-event-box').find('p').eq(1).text();
    var event_start = $j(this).closest('.ba-planning-event-box').data('event-start');
    var event_end = $j(this).closest('.ba-planning-event-box').data('event-end');

    // pass the data to the popup content div
    $j('.ba-planning-popup-content').data('event-id', event_id);
    $j('.ba-planning-popup-content').data('event-start', event_start);
    $j('.ba-planning-popup-content').data('event-end', event_end);

    // open the popup
    $j('.ba-planning-popup-bg').css('display', 'block');
    // set the popup header
    $j('.ba-planning-popup-header h3').text('Modifier le cours');
    $j('.ba-planning-popup-header p').text(event_name + " - " + event_start + " / " + event_end);
    // add a form to edit the event and add a dropdown to select state of the event
    $j('.ba-planning-popup-content').html('<form id="ba-plus-edit-event-form" action="" method="post"><input type="text" name="event_name" value="' + event_name + '" /><select name="event_state"><option value="1">Actif</option><option value="0">Inactif</option></select><button id="ba-plus-edit-event-send">Modifier</button></form>');
});
$j('.ba-plus-add-btn').click(function (e) {
    e.preventDefault();
    var event_id = $j(this).closest('.ba-planning-event-box').data('event-id');
    var event_name = $j(this).closest('.ba-planning-event-box').find('p').eq(1).text();
    var event_start = $j(this).closest('.ba-planning-event-box').data('event-start');
    var event_end = $j(this).closest('.ba-planning-event-box').data('event-end');

    // pass the data to the popup content div
    $j('.ba-planning-popup-content').data('event-id', event_id);
    $j('.ba-planning-popup-content').data('event-start', event_start);
    $j('.ba-planning-popup-content').data('event-end', event_end);

    // open the popup
    $j('.ba-planning-popup-bg').css('display', 'block');
    // set the popup header
    $j('.ba-planning-popup-header h3').text('Ajouter un participant');
    $j('.ba-planning-popup-header p').text(event_name + " - " + event_start + " / " + event_end);
    // show .user-add-popup and hide .ba-planning-popup-content
    document.querySelector('.user-add-popup').style.display = 'flex';
    $j('.ba-planning-popup-content').html('');

    // add the event listener to the btn 
    document.querySelector('#ba-plus-user-search-send').addEventListener('click', ba_plus_add_user_callback);
});

$j('.ba-booked li').click(function (e) {
    e.preventDefault();
    // open the popup
    $j('.ba-planning-popup-bg').css('display', 'block');
    // get the user id
    var user_id = $j(this).data('user-id');
    // get the user name
    var user_name = $j(this).text();
    // get the event id
    var event_id = $j(this).closest('.ba-planning-event-box').data('event-id');
    // get the event name
    var event_name = $j(this).closest('.ba-planning-event-box').find('p').eq(1).text();
    // get the event start date
    var event_start = $j(this).closest('.ba-planning-event-box').data('event-start');
    // get the event end date
    var event_end = $j(this).closest('.ba-planning-event-box').data('event-end');
    var booking_id = $j(this).data('booking-id');

    // set the data to the popup
    $j('.ba-planning-popup-content').data('event-id', event_id);
    $j('.ba-planning-popup-content').data('user-id', user_id);
    $j('.ba-planning-popup-content').data('event-start', event_start);
    $j('.ba-planning-popup-content').data('event-end', event_end);
    $j('.ba-planning-popup-content').data('booking-id', booking_id);

    // set the popup header
    $j('.ba-planning-popup-header h3').text(user_name);
    $j('.ba-planning-popup-header p').text(event_name + " - " + event_start + " / " + event_end);
    // add two button to the popup
    $j('.ba-planning-popup-content').html('<button class="ba-planning-popup-booking-delete">Supprimer</button><button class="ba-planning-popup-booking-refund">Rembourser</button>');
    $j('.ba-planning-popup-booking-delete').click(ba_plus_cancel_booking_callback);
    $j('.ba-planning-popup-booking-refund').click(ba_plus_refund_booking_callback);
});
$j('.ba-planning-popup-close').click(function (e) {
    e.preventDefault();
    // close the popup
    $j('.ba-planning-popup-bg').css('display', 'none');
    document.querySelector('.user-add-popup').style.display = 'none';
});


$j('.ba-wl li').click(function (e) {
    e.preventDefault();
    // open the popup
    $j('.ba-planning-popup-bg').css('display', 'block');
    // get the user id
    var user_id = $j(this).data('user-id');
    // get the user name
    var user_name = $j(this).text();
    // get the event id
    var event_id = $j(this).closest('.ba-planning-event-box').data('event-id');
    // get the event name
    var event_name = $j(this).closest('.ba-planning-event-box').find('p').eq(1).text();
    // get the event start date
    var event_start = $j(this).closest('.ba-planning-event-box').data('event-start');
    // get the event end date
    var event_end = $j(this).closest('.ba-planning-event-box').data('event-end');

    // set the data to the popup
    $j('.ba-planning-popup-content').data('event-id', event_id);
    $j('.ba-planning-popup-content').data('user-id', user_id);
    $j('.ba-planning-popup-content').data('event-start', event_start);
    $j('.ba-planning-popup-content').data('event-end', event_end);

    // set the popup header
    $j('.ba-planning-popup-header h3').text(user_name);
    $j('.ba-planning-popup-header p').text(event_name + " - " + event_start + " / " + event_end);
    // add two button to the popup
    $j('.ba-planning-popup-content').html('<button class="ba-planning-popup-wl-delete">Supprimer</button>');
    $j('.ba-planning-popup-wl-delete').click(ba_plus_cancel_wl_callback);
});

function ba_plus_cancel_booking_callback(e) {
    console.log('cancel booking');
    e.preventDefault();
    // get the user id
    var user_id = $j(this).closest('.ba-planning-popup-bg').find('.ba-planning-popup-header h3').text();
    // get the event id
    var event_id = $j(this).closest('.ba-planning-popup-bg').find('.ba-planning-popup-header p').text();
    // get the booking id
    var booking_id = $j(this).closest('.ba-planning-popup-bg').find('.ba-planning-popup-content').data('booking-id');
    // send the ajax request
    $j.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'bookactiDeleteBooking',
            user_id: user_id,
            booking_id: booking_id,
            context: 'admin_booking_list',
            nonce: nonce_delete_booking
        },
        success: function (response) {
            if (response.status === 'success') {
                location.reload();
            } else {
                console.log(response);
            }
        },
        error: function (response) {
            console.log(response);
        }
    });
}

function ba_plus_refund_booking_callback(e) {
    e.preventDefault();
    // get the user id
    var user_id = $j(this).closest('.ba-planning-popup-bg').find('.ba-planning-popup-content').data('user-id');
    // get the event data
    var event_id = $j(this).closest('.ba-planning-popup-bg').find('.ba-planning-popup-content').data('event-id');
    var booking_id = $j(this).closest('.ba-planning-popup-bg').find('.ba-planning-popup-content').data('booking-id');
    // send the ajax request
    $j.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'bookactiRefundBooking',
            user_id: user_id,
            event_id: event_id,
            booking_id: booking_id,
            nonce: bookacti_localized.nonce_refund_booking,
            is_admin: 1,
            refund_action: 'booking_pass'
        },
        success: function (response) {
            if (response.status === 'success') {
                location.reload();
            } else {
                console.log(response);
            }
        },
        error: function (response) {
            console.log(response);
        }
    });
}

function ba_plus_cancel_wl_callback(e) {
    e.preventDefault();
    // get the user id
    var user_id = $j(this).closest('.ba-planning-popup-bg').find('.ba-planning-popup-content').data('user-id');
    // get the event data
    var event_id = $j(this).closest('.ba-planning-popup-bg').find('.ba-planning-popup-content').data('event-id');
    var event_start = $j(this).closest('.ba-planning-popup-bg').find('.ba-planning-popup-content').data('event-start');
    var event_end = $j(this).closest('.ba-planning-popup-bg').find('.ba-planning-popup-content').data('event-end');

    // send the ajax request
    $j.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'baPlusCancelWaitingList',
            user_id: user_id,
            waiting_id: event_id,
            start_date: event_start,
            end_date: event_end,
        },
        success: function (response) {
            if (response.status === 'success') {
                location.reload();
            } else {
                console.log(response);
            }
        },
        error: function (response) {
            console.log(response);
        }
    });
}

function ba_plus_update_event_callback(e) {
    e.preventDefault();
    // send info to action bookactiUpdateActivity
    // data : title, availability
}

function ba_plus_add_user_callback(e) {
    e.preventDefault();

    // send info to action baPlusAdminBooking
    // data : user_id, event_id, event_start, event_end
    var user_id = document.querySelector('#bookacti-booking-filter-customer option:nth-child(4)').value;
    var event_id = $j('.ba-planning-popup-content').data('event-id');
    var event_start = $j('.ba-planning-popup-content').data('event-start');
    var event_end = $j('.ba-planning-popup-content').data('event-end');
    console.log(user_id);
    $j.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'baPlusAdminBooking',
            user_id: user_id,
            event_id: event_id,
            event_start: event_start,
            event_end: event_end
        },
        success: function (response) {
            if (response.data.status === 'success') {
                location.reload();
            } else {
                console.log(response);
            }
        },
        error: function (response) {
            console.log(response);
        }
    });
}