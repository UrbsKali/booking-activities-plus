
$j('.bookacti-booking-form').on('bookacti_trigger_event_click', function (info, trigger) {
    trigger.click = true;
});


// connect to bookacti_validate_picked_events with agrs [valid_form]
$j('.bookacti-booking-form').on('bookacti_validate_picked_events', function (info, valid_form) {
    console.log(valid_form);
    if (!valid_form.is_qty_inf_to_avail) {
        valid_form.is_qty_inf_to_avail = true;
        valid_form.send = true;
    }

});