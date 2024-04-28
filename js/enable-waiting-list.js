function main() {
    let unaviable = document.querySelectorAll('.bookacti-event-unavailable');
    for (let i = 0; i < 10; i++) { // c'est du bricolage, c'est pas propre mais ça marche
        setTimeout(() => {
            unaviable = document.querySelectorAll('.bookacti-event-unavailable');
            // remove their class
            unaviable.forEach((el) => {
                el.classList.remove('bookacti-event-unavailable');
            });
        }, 50 * i);
    }
}



window.addEventListener('DOMContentLoaded', main);


// cancel the waiting list - send to the php 
function cancel() {
    let xhr = new XMLHttpRequest();
    xhr.open('POST', 'cancel.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                console.log(xhr.responseText);
            } else {
                console.error(xhr.statusText);
            }
        }
    };
    xhr.send();
}

