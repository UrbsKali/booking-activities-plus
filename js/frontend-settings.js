function send_settings(e) {
    e.preventDefault();
    console.log("send_settings");

    // send via fetch url encoded data

    let formData = new URLSearchParams();
    formData.append("action", "baPlusUpdateSettings");
    formData.append("settings[nb_cancel_left]", document.getElementById("ba-cancel-balance").value);
    formData.append("user_id", document.getElementById("ba-cancel-balance").dataset.userId);


    fetch(ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData
    }).then(response => {
        if (response.ok) {
            return response.json();
        } else {
            throw new Error('Something went wrong');
        }
    }).then(data => {
        if (data.data.status == "success") {
            create_popup("Paramètre sauvegardé !", "Ce paramètre à bien été mise à jour !", "success");
        } else {
            create_popup("Erreur", "Une erreur est survenue lors de l'enregistrement. Si vous avez indiqué la même quantité que précédemant, c'est normal. Sinon, veuillez contacter votre administrateur.", "error");
            console.log(data);
        }
    }).catch(error => {
        console.error(error);
        create_popup("Erreur", "Une erreur est survenue lors de l'enregistrement, veuillez contacter votre administrateur.", "error");
    });



}

let btn = document.getElementById("ba-cancel-balance-save");
btn.addEventListener("click", send_settings);


function create_popup(title, message, level) {
    let popupBG = document.createElement("div");
    popupBG.classList.add("ba-popup-bg");

    let popup = document.createElement("div");
    popup.classList.add("ba-popup");
    popup.classList.add("ba-popup-" + level);
    popupBG.appendChild(popup);

    let popupHeader = document.createElement("div");
    popupHeader.classList.add("ba-popup-header");
    popup.appendChild(popupHeader);

    let popupTitle = document.createElement("h3");
    popupTitle.innerText = title;
    popupHeader.appendChild(popupTitle);

    let popupContent = document.createElement("div");
    popupContent.classList.add("ba-popup-content");
    popup.appendChild(popupContent);

    let popupMessage = document.createElement("p");
    popupMessage.innerText = message;
    popupContent.appendChild(popupMessage);

    let popupCross = document.createElement("button");
    popupCross.classList.add("ba-popup-close");
    popupCross.innerText = "X";
    popupCross.addEventListener("click", function () {
        popup.remove();
    });
    popupHeader.appendChild(popupCross);

    let popupClose = document.createElement("button");
    popupClose.innerText = "Ok";
    popupClose.addEventListener("click", function () {
        popup.remove();
    });
    popup.appendChild(popupClose);

    document.body.appendChild(popup);
}