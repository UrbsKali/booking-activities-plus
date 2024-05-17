$j('.ba-plus-cancel-waiting-list').click(function () {
    var waiting_id = $j(this).data('waiting-id');
    var user_id = $j(this).data('user-id');
    $j.ajax({
        url: ajaxurl,
        type: 'POST',
        data: { 
            action: 'baPlusCancelWaitingList',
            waiting_id: waiting_id,
            user_id: user_id,
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