function main(){
    let tables = document.querySelectorAll('.bookacti-user-booking-list-table');

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