
// call ajax when user click on cancel waiting list button
$j('.bookacti-cancel-waiting-list').click(function () {
    var waiting_id = $j(this).data('waiting-id');
    $j.ajax({
        url: ajaxurl,
        type: 'POST',
        data: { 
            action: 'ba_plus_cancel_waiting_list_booking',
            waiting_id: waiting_id,
            nonce: bookacti_localized.nonce
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                console.log('AJAX ' + bookacti_localized.success);
                location.reload();
            } else {
                console.log('AJAX ' + bookacti_localized.error);
                console.log(response.data);
            }

        },
        error: function (e) {
            console.log('AJAX ' + bookacti_localized.error);
            console.log(e);
        },
        complete: function () {
        }
    });
});