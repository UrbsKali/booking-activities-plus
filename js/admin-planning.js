// Click on Edit course btn
$j(".ba-plus-edit-btn").click(function (e) {
    e.preventDefault();
    var event_id = $j(this).closest('.ba-planning-event-box').data('event-id');
    var event_name = $j(this).closest('.ba-planning-event-box').find('p').eq(2).text();
    var event_start = $j(this).closest('.ba-planning-event-box').data('event-start');
    var event_end = $j(this).closest('.ba-planning-event-box').data('event-end');
    var is_recurring = $j(this).closest('.ba-planning-event-box').data('is-recurring');
    var availability = $j(this).closest('.ba-planning-event-box').find('p.quantity').eq(0).data('availability');
    var current_booked = $j(this).closest('.ba-planning-event-box').find('p.quantity').eq(0).data('count');

    // pass the data to the popup content div
    $j('.ba-planning-popup-content').data('event-id', event_id);
    $j('.ba-planning-popup-content').data('event-start', event_start);
    $j('.ba-planning-popup-content').data('event-end', event_end);
    $j('.ba-planning-popup-content').data('is-recurring', is_recurring);
    $j('.ba-planning-popup-content').data('availability', availability);

    // open the popup
    $j('.ba-planning-popup-bg').css('display', 'block');
    // set the popup header
    $j('.ba-planning-popup-header h3').text('Modifier le cours');
    $j('.ba-planning-popup-header p').text(event_name + " - " + event_start + " / " + event_end);
    // add a form to edit the event and add a dropdown to select state of the event
    var options = '';
    if (availability == current_booked) {
        options = '<option value="complet">Complet</option><option value="actif">Actif</option><option value="ferme">Fermé</option>';
    } else {
        options = '<option value="actif">Actif</option><option value="complet">Complet</option><option value="ferme">Fermé</option>';
    }
    $j('.ba-planning-popup-content').html('<form id="ba-plus-edit-event-form" action="" method="post"><input type="text" name="event_name" value="' + event_name + '" /><select name="event_state">'+ options +'</select><input type="number" name="new_availability" value="'+ availability +'" min="0"/><button id="ba-plus-edit-event-send">Modifier</button></form>');

    // add the event listener to the btn
    document.querySelector('#ba-plus-edit-event-send').addEventListener('click', ba_plus_update_event_callback);
});

// Click on add user btn
$j('.ba-plus-add-btn').click(function (e) {
    e.preventDefault();
    var event_id = $j(this).closest('.ba-planning-event-box').data('event-id');
    var event_name = $j(this).closest('.ba-planning-event-box').find('p').eq(2).text();
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

// Click on booked user
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
    var event_name = $j(this).closest('.ba-planning-event-box').find('p').eq(2).text();
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
    clear_info();
    // close the popup
    $j('.ba-planning-popup-bg').css('display', 'none');
    document.querySelector('.user-add-popup').style.display = 'none';
});

// Click on waiting user
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
    var event_name = $j(this).closest('.ba-planning-event-box').find('p').eq(2).text();
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
    e.preventDefault();
    // get the user id
    var user_id = $j(this).closest('.ba-planning-popup-bg').find('.ba-planning-popup-header h3').text();
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
                show_info('success', 'Réservation supprimée', true);
            } else {
                show_info('error', response.message, false);
            }
        },
        error: function (response) {
                show_info('error', response, false);
            }
    });
}

function ba_plus_refund_booking_callback(e) {
    e.preventDefault();
    // get the event data
    var booking_id = $j(this).closest('.ba-planning-popup-bg').find('.ba-planning-popup-content').data('booking-id');
    // send the ajax request
    $j.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'baPlusRefundBooking',
            booking_id: booking_id,
        },
        success: function (response) {
            if (response.data.status === 'success') {
                show_info('success', 'Réservation remboursée', true);
            } else {
                show_info('error', response.data.message, false);
            }
        },
        error: function (response) {
            show_info('error', response, false);
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
                show_info('success', 'Participant supprimé de la liste d\'attente', true);
            } else {
                show_info('error', response.message, false);
            }
        },
        error: function (response) {
            show_info('error', response, false);
        }
    });
}

function ba_plus_update_event_callback(e) {
    e.preventDefault();
    // send info to action bookactiUpdateActivity
    // data : title, availability
    // check if field has been edit or not
    var event_name = document.querySelector('#ba-plus-edit-event-form input[name="event_name"]').value;

    var event_state = document.querySelector('#ba-plus-edit-event-form select[name="event_state"]').value;

    var new_availability = document.querySelector('#ba-plus-edit-event-form input[name="new_availability"]').value;

    var old_availability = $j('.ba-planning-popup-content').data('availability');

    
    var old_name = $j('.ba-planning-popup-content').find('p').text().split(' - ')[0];
    if (event_name === '' || event_state === '') {
        console.log('error');
        return;
    }
    var event_id = $j('.ba-planning-popup-content').data('event-id');
    var event_start = $j('.ba-planning-popup-content').data('event-start');
    var event_end = $j('.ba-planning-popup-content').data('event-end');
    var is_recurring = $j('.ba-planning-popup-content').data('is-recurring');

    var data = {
        action: 'baPlusUpdateEvent',
        event_id: event_id,
        event_start: event_start,
        event_end: event_end,
        is_recurring: is_recurring,
        event_title : event_name,
        event_state : event_state,
    };

    if (old_availability != new_availability){
        console.log(old_availability);
        console.log(new_availability);
        data['new_availability'] = new_availability;
    }

    $j.ajax({
        url: ajaxurl,
        type: 'POST',
        data: data,
        success: function (response) {
            if (response.data.status === 'success') {
                show_info('success', 'Cours modifié', true);
            } else {
                show_info('error', response.data.message, false);
            }
        },
        error: function (response) {
            show_info('error', response, false);
        }
    });

}

function ba_plus_add_user_callback(e) {
    e.preventDefault();

    // send info to action baPlusAdminBooking
    // data : user_id, event_id, event_start, event_end
    var user_id = $j( '#bookacti-booking-filter-customer' ).select2('data')[0].id;
    var event_id = $j('.ba-planning-popup-content').data('event-id');
    var event_start = $j('.ba-planning-popup-content').data('event-start');
    var event_end = $j('.ba-planning-popup-content').data('event-end');
    if (user_id === '' || event_id === '' || event_start === '' || event_end === '') {
        show_info('error', "Vous devez remplir le champ utilisateur", false);
        return;
    }
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
                show_info('success', 'Participant ajouté', true);
            } else {
                show_info('error', response.data.message, false);
            }
        },
        error: function (response) {
            show_info('error', response.data.message, false);
        }
    });
}


let print_btn = document.querySelector('#ba-planning-print');
print_btn.addEventListener('click', function () {
    window.print();
});

let today_btn = document.querySelector('#ba-planning-today');
today_btn.addEventListener('click', function () {
    let date = new Date();
    let day = date.getDate();
    let month = date.getMonth() + 1;
    let year = date.getFullYear();
    let date_str = day + '-' + month + '-' + year;
    let url = window.location.href;
    url = url.split('?')[0];
    url += '?start_date=' + date_str;
    window.location.href = url;
});

let prev_btn = document.querySelector('#ba-planning-prev-week');
let next_btn = document.querySelector('#ba-planning-next-week');

prev_btn.addEventListener('click', function () {
    // get the current date
    let url = window.location.href;
    let date = url.split('?');
    if (date.length === 1) {
        date = new Date();
    } else {
        date = date[1].split('=')[1].split('-');
        let day = parseInt(date[0]);
        let month = parseInt(date[1]);
        let year = parseInt(date[2]);
        date = new Date(year, month - 1, day);
    }

    // get the previous week
    date.setDate(date.getDate() - 7);
    let day = date.getDate();
    let month = date.getMonth() + 1;
    let year = date.getFullYear();
    let date_str = day + '-' + month + '-' + year;
    url = url.split('?')[0];
    url += '?start_date=' + date_str;
    window.location.href = url;
});

next_btn.addEventListener('click', function () {
    // get the current date
    let url = window.location.href;
    let date = url.split('?');
    if (date.length === 1) {
        date = new Date();
    } else {
        date = date[1].split('=')[1].split('-');
        let day = parseInt(date[0]);
        let month = parseInt(date[1]);
        let year = parseInt(date[2]);
        date = new Date(year, month - 1, day);
    }

    // get the next week
    date.setDate(date.getDate() + 7);
    let day = date.getDate();
    let month = date.getMonth() + 1;
    let year = date.getFullYear();
    let date_str = day + '-' + month + '-' + year;
    url = url.split('?')[0];
    url += '?start_date=' + date_str;
    window.location.href = url;
});

/**
 * Show info message on the popup
 * @param {string} status success, error
 * @param {string} text the message to display
 * @param {boolean} reload reload the page after 2s
 */
function show_info(status, text, reload = false) {
    let info = document.querySelector('.ba-plus-info');
    info.style.display = 'block';
    info.classList.add(status);
    let p = document.createElement('p');
    p.innerHTML = text;
    info.appendChild(p);
    if (reload) {
        setTimeout(() => {
            location.reload();
        }, 2000);
    }
}

function clear_info(){
    let info = document.querySelector('.ba-plus-info');
    info.style.display = 'none';
    info.classList.remove('success');
    info.classList.remove('error');
    info.innerHTML = '';
}