function send_settings(e){
    e.preventDefault();
    console.log("send_settings");

    var data = {
        "action": "baPlusUpdateSettings",
        "free_cancel_delay": document.getElementById("ba-admin-settings-refund-delay").value
    }
    
    // Send the same request with fetch
    fetch(ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data),
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            location.reload();
        } else {
            console.log('PHP ERROR');
            console.log(data);
        }
    })

}

let btn = document.getElementById("ba-admin-settings-save");
btn.addEventListener("click", send_settings);