let button_next = document.querySelector('.fc-next-button');
let button_prev = document.querySelector('.fc-prev-button');
let btn_init = false;
let is_enable = false;

// Enable clicking on event to add to waiting list
$j('.bookacti-booking-form').on('bookacti_trigger_event_click', function (info, trigger) {
    trigger.click = true;
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
    console.log(event_col)
    event_col.forEach(element => {
        id = element.parentElement.parentElement.dataset.eventId;
        if (id === null) {
            return;
        }
        // get first child of booking_system
        tmp = bookacti.booking_system
        tmp = tmp[Object.keys(tmp)[0]]
        // get the event
        wl = tmp.events_data[id].waiting_list
        if (wl == '0') {
            return;
        }
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

    if (button_next !== null && button_prev !== null) {
        button_next.addEventListener('click', add_waiting_number);
        button_prev.addEventListener('click', add_waiting_number);
        btn_init = true;
    }
}






document.addEventListener('DOMContentLoaded', main);