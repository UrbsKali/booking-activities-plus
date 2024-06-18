
// call ajax when user click on cancel waiting list button
$j('.bookacti-cancel-waiting-list').click(function () {
    var waiting_id = $j(this).data('waiting-id');
    $j.ajax({
        url: ajaxurl,
        type: 'POST',
        data: { 
            action: 'baPlusCancelWaitingList',
            waiting_id: waiting_id,
            nonce: bookacti_localized.nonce
        },
        dataType: 'json',
        success: function (response) {
            if (response.status === 'success') {
                location.reload();
            } else {
                console.log('PHP ERROR');
                console.log(response);
            }

        },
        error: function (e) {
            console.log('AJAX ERROR');
            console.log(e);
        },
        complete: function () {
        }
    });
});

$j('.bookacti-refund-booking').closest("tr").on("bookacti_booking_action_data", function (event, data, booking_id, booking_type, action) {
    console.log(data);
    if (!data.refund_action) {
        console.log('No refund action, adding booking_pass');
        data.refund_action = 'booking_pass';
    }
});