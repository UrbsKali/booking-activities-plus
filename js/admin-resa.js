$j('.ba-plus-add-resa-btn').click(function (e) {
    e.preventDefault();
    let form = $j(this).closest('form');
    let user_id = form.find('select[name="user_id"]').val();
    let data = form.find('select[name="event_id"]').val();
    let evend_id = data.split('#')[0];
    let start_date = data.split('#')[1];
    let end_date = data.split('#')[2];

    if (!user_id) {
        user_id = document.querySelectorAll("#ba-plus-add-resa-popup option[value]")[1].value
        if (isNaN(parseInt(user_id))) {
            user_id = document.querySelectorAll("#ba-plus-add-resa-popup option[value]")[2].value
        }
    }
    $j.ajax({
        url: ajaxurl,
        type: 'POST',
        data: { 
            action: 'baPlusAddResa',
            event_id: evend_id,
            user_id: user_id,
            start_date: start_date,
            end_date: end_date,
            nonce: bookacti_localized.nonce
        },
        dataType: 'json',
        success: function (response) {
            if (response.status === 'success') {
                //location.reload();
            } else {
                console.log('PHP ERROR');
                console.log(response);
                let error = $j('<div class="error">');
                error.text(response.message);
                form.prepend(error);
            }
        },
        error: function (e) {
            console.log('AJAX ERROR');
            console.log(e);
            // show error message
            let error = $j('<div class="error">');
            error.text(e.responseText);
            form.prepend(error);
        },
        complete: function () {
        }
    });
});