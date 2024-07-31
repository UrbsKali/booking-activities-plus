let button_next = document.querySelector('.fc-next-button');
let button_prev = document.querySelector('.fc-prev-button');
let week_btn = document.querySelector('.fc-timeGridWeek-button');
let day_btn = document.querySelector('.fc-timeGridDay-button');
let month_btn = document.querySelector('.fc-dayGridMonth-button');

let btn_init = false;
let is_enable = false;

// Enable clicking on event to add to waiting list
$j('.bookacti-booking-form').on('bookacti_trigger_event_click', function (info, trigger) {
    trigger.click = true;
});
$j('.bookacti-booking-form').on('bookacti_update_quantity', function (info, qty_data) {
    if( qty_data.avail == 0 || qty_data.avail < 0 ) { 
        document.querySelector('.bookacti-submit-form.button').value = 'Ajouter à la liste d\'attente';
    } else {
        document.querySelector('.bookacti-submit-form.button').value = 'Réserver';
    }
});




$j('.bookacti-booking-form').on('bookacti_validate_picked_events', function (info, valid_form) {
    if (!valid_form.is_qty_inf_to_avail) {
        valid_form.is_qty_inf_to_avail = true;
        valid_form.send = true;
    }
    console.log(valid_form);
});

// enable waiting list by removing 
// wait for the event to be printed on the page
function main() {
    for (let i = 0; i < 10; i++) { // c'est du bricolage, c'est pas propre mais ça marche
        setTimeout(() => {
            load_btn();
            if (is_enable) {
                return;
            }
            add_waiting_number();
        }, 50 * i);
    }
}

function add_waiting_number() {
    event_col = document.querySelectorAll('.bookacti-availability-container');
    if (event_col.length === 0) {
        return;
    }
    event_col.forEach(element => {
        id = element.parentElement.parentElement.dataset.eventId;
        start_date = element.parentElement.parentElement.dataset.eventStart;
        if (id === null || start_date === null) {
            return;
        }
        // get first child of booking_system
        tmp = bookacti.booking_system
        tmp = tmp[Object.keys(tmp)[0]]
        // get the event
        if (tmp.waiting_list[id] == undefined) {
            return;
        }
        wl = tmp.waiting_list[id][start_date];
        if (wl == undefined || wl == 0) {
            return;
        }
        wl = wl.length;
        element.firstChild.classList.remove('bookacti-booked');
        element.firstChild.classList.remove('bookacti-full');
        element.firstChild.firstChild.innerHTML = wl;
        element.firstChild.childNodes[2].innerHTML = 'En Att.';
        is_enable = true;
    });
}

function load_btn() {
    if (btn_init) {
        return;
    }
    button_next = document.querySelector('.fc-next-button');
    button_prev = document.querySelector('.fc-prev-button');

    week_btn = document.querySelector('.fc-timeGridWeek-button');
    day_btn = document.querySelector('.fc-timeGridDay-button');
    month_btn = document.querySelector('.fc-dayGridMonth-button');

    if (button_next !== null && button_prev !== null && week_btn !== null && day_btn !== null && month_btn !== null) {
        button_next.addEventListener('click', add_waiting_number);
        button_prev.addEventListener('click', add_waiting_number);
        week_btn.addEventListener('click', add_waiting_number);
        day_btn.addEventListener('click', add_waiting_number);
        month_btn.addEventListener('click', add_waiting_number);
        btn_init = true;
    }
}






document.addEventListener('DOMContentLoaded', main);


row = $j( '.bookacti-refund-booking[data-booking-id="' + booking_id + '"]' ).closest( 'tr' );
row.first().on( 'bookacti_booking_action_data', function(data, booking_id, booking_type, type) {
    if (data["refund_action"] == undefined) {
        data["refund_action"] = "booking_pass";
    }
    console.log(data);
});