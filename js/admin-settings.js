function ba_plus_prevent_form(e) {
    // Prevent submission
    e.preventDefault();

    // Get form data
    let form = e.target;
    let data = ba_plus_parse_forms(form);
    data["action"] = 'baPlusSaveSettings';
    console.log(data);


    // AJAX request
    let xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxurl, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            console.log(xhr.responseText);
        }
        else {
            console.log(xhr);
        }
    };
    xhr.send(data);
}

let form = document.querySelector('#ba_plus_settings_form');
form.addEventListener('submit', ba_plus_prevent_form);

/**
 * Parse form data
 * @param {HTMLElement} form 
 * @returns array of form data
 */
function ba_plus_parse_forms(form) {
    let data = {};
    for (let i = 0; i < form.elements.length; i++) {
        let element = form.elements[i];
        let name = element.name;
        let value = element.value;
        if (name) {
            data[name] = value;
        }
    }
    delete data['action'];
    return data;
}