let event_col = document.querySelectorAll('.bookacti-column-title-events');


function sort_booking_by_date(reverse = false) {
    let booking_table = document.querySelector('.bookacti-user-booking-list-table');
    let rows = booking_table.querySelectorAll('tr');
    let rows_array = Array.from(rows);
    rows_array.shift(); // remove the header
    rows_array.sort((a, b) => {
        let text_a = "vendredi 1 janvier 1970 10h00"
        let text_b = "vendredi 1 janvier 1970 10h00"
        try {
            text_a = a.querySelector('td').querySelector('span.bookacti-booking-event-start').innerText;
            text_b = b.querySelector('td').querySelector('span.bookacti-booking-event-start').innerText;
        } catch (error) {
            console.log(error);
        }


        let date_a = parse_date(text_a);
        let date_b = parse_date(text_b);
        if (reverse) {
            return date_b - date_a;
        }
        return date_a - date_b;
    });
    booking_table.innerHTML = '';
    booking_table.appendChild(rows[0]);
    rows_array.forEach((row) => {
        booking_table.appendChild(row);
    });
}

function parse_date(str) {
    // receive a string like "lundi 29 avril 2024 18h45" 
    // return a date object
    let date = str.split(' ');
    let day = parseInt(date[1]);
    let month_str = date[2];
    let month = 0;
    let month_array = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'aout', 'septembre', 'octobre', 'novembre', 'décembre'];
    for (let i = 0; i < month_array.length; i++) {
        if (month_array[i] === month_str) {
            month = i;
            break;
        }
    }
    let year = parseInt(date[3]);
    let time = date[4];
    let hour = parseInt(time.split('h')[0]);
    let minute = parseInt(time.split('h')[1]);
    return new Date(year, month, day, hour, minute);
}
function main() {
    is_enable = false
    for (let i = 0; i < 10; i++) { // c'est du bricolage, c'est pas propre mais ça marche
        setTimeout(() => {
            if (is_enable) {
                return;
            }
            event_col = document.querySelector('.bookacti-column-title-events');
            if (event_col === null) {
                return;
            }
            event_col.innerHTML = 'Événements <span class="sort-by-date" style="cursor:pointer;">▲</span><style>.bookacti-column-title-events:after{visibility:hidden;}</style>';
            event_col.style.visibility = 'visible';
            sort_booking_by_date();
            let sort_btn = event_col.querySelector('.sort-by-date');
            sort_btn.addEventListener('click', click_callback);
            is_enable = true;

        }, 50 * i);
    }
}

function click_callback(event) {
    let target = event.target;
    if (target.tagName === 'SPAN') {
        let reverse = target.classList.contains('reverse');
        if (reverse) {
            target.innerHTML = '▲';
        } else {
            target.innerHTML = '▼';
        }
        sort_booking_by_date(!reverse);
        target.classList.toggle('reverse');
    }
}

document.addEventListener('DOMContentLoaded', main);