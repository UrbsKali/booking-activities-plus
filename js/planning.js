function main() {

    if (tables.length <= 5) {
        return;
    }

    tables.forEach(table => {
        // get all the .bookacti-column-start_date children of the table
        // remove the Full date, leave juste the hours and minutes
        // put the full date on day variable
        let rows = table.querySelectorAll('.bookacti-column-start_date');
        if (rows.length == 0) {
            return;
        }
        rows = Array.from(rows);
        rows.shift();
        let day = '';
        rows.forEach(row => {
            day = row.textContent.split(' ')[0] + ' ' + row.textContent.split(' ')[1] + ' ' + row.textContent.split(' ')[2] + ' ' + row.textContent.split(' ')[3];
            row.textContent = row.textContent.split(' ')[4];
        });
        // append the day on the Top of the table
        let thead = table.querySelector('thead');
        let tr = document.createElement('tr');
        let th = document.createElement('th');
        th.textContent = day;
        th.setAttribute('colspan', '5');
        tr.appendChild(th);
        thead.appendChild(tr);
        // reverse the order of  the tr in the thead, so the date is on top
        let trs = thead.querySelectorAll('tr');
        let trsArray = Array.from(trs);
        trsArray.reverse();
        thead.innerHTML = '';
        trsArray.forEach(tr => {
            thead.appendChild(tr);
        });

    });
}

let is_enabled = false;
let tables = document.querySelectorAll('.bookacti-user-booking-list-table');
// on load
document.addEventListener('DOMContentLoaded', function () {
    for (let i = 0; i < 5; i++) {
        setTimeout(function () {
            tables = document.querySelectorAll('.bookacti-user-booking-list-table');
            if (tables.length > 5 && !is_enabled) {
                main();
                is_enabled = true;
            }
        }, 50 * i);
    }
});

let print_btn = document.querySelector('#ba-planning-print');
print_btn.addEventListener('click', function () {
    window.print();
});

let today_btn = document.querySelector('#ba-planning-today');
today_btn.addEventListener('click', function () {
    let date = new Date();
    let day = date.getDate();
    let month = date.getMonth() + 1;
    let year = date.getFullYear();
    let date_str = day + '-' + month + '-' + year;
    let url = window.location.href;
    url = url.split('?')[0];
    url += '?start_date=' + date_str;
    window.location.href = url;
});

let prev_btn = document.querySelector('#ba-planning-prev-week');
let next_btn = document.querySelector('#ba-planning-next-week');

prev_btn.addEventListener('click', function () {
    // get the current date
    let url = window.location.href;
    let date = url.split('?')[1].split('=')[1];
    if (date === '') {
        date = new Date();
    } else {
        date = date.split('-');
        let day = parseInt(date[0]);
        let month = parseInt(date[1]);
        let year = parseInt(date[2]);
        date = new Date(year, month - 1, day);
    }

    // get the previous week
    date.setDate(date.getDate() - 7);
    let day = date.getDate();
    let month = date.getMonth() + 1;
    let year = date.getFullYear();
    let date_str = day + '-' + month + '-' + year;
    url = url.split('?')[0];
    url += '?start_date=' + date_str;
    window.location.href = url;
});

next_btn.addEventListener('click', function () {
    // get the current date
    let url = window.location.href;
    let date = url.split('?')[1].split('=')[1];
    if (date === '') {
        date = new Date();
    } else {
        date = date.split('-');
        let day = parseInt(date[0]);
        let month = parseInt(date[1]);
        let year = parseInt(date[2]);
        date = new Date(year, month - 1, day);
    }

    // get the next week
    date.setDate(date.getDate() + 7);
    let day = date.getDate();
    let month = date.getMonth() + 1;
    let year = date.getFullYear();
    let date_str = day + '-' + month + '-' + year;
    url = url.split('?')[0];
    url += '?start_date=' + date_str;
    window.location.href = url;
});
