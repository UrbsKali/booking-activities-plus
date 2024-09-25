let event_col = document.querySelectorAll('.bookacti-column-title-events');
let is_enable = false;
let user_id = 0;

function main() {
    is_enable = false
    // get url params
    let url_args = new URLSearchParams(window.location.search);
    let order = url_args.get('order');
    if (order !== 'asc' || order !== 'desc') {
        order = 'asc';
    }

    for (let i = 0; i < 10; i++) { // c'est du bricolage, c'est pas propre mais ça marche
        setTimeout(() => {
            if (is_enable) {
                return;
            }
            event_col = document.querySelector('.bookacti-column-title-events');
            if (event_col === null) {
                return;
            }

            enable(order, true);


        }, 50 * i);
    }
}

function enable(order, setup = false) {
    user_id = document.querySelector('.bookacti-user-booking-list').dataset.userId;
    event_col = document.querySelector('.bookacti-column-title-events');

    event_col.innerHTML = 'Événements <span class="sort-by-date" style="cursor:pointer;">▲</span><style>.bookacti-column-title-events:after{visibility:hidden;}</style>';
    event_col.style.visibility = 'visible';

    let sort_btn = event_col.querySelector('.sort-by-date');
    sort_btn.addEventListener('click', click_callback);

    if (order === 'asc') {
        sort_btn.innerHTML = '▲';
        sort_btn.classList.remove('reverse');
    } else {
        sort_btn.innerHTML = '▼';
        sort_btn.classList.add('reverse');
    }

    is_enable = true;

    let previous_page = document.querySelector('.bookacti-user-booking-list-previous-page>a');
    if (previous_page !== null) {
        previous_page.href = previous_page.href.replace('mon-compte-2/?', 'mon-compte-2/bookingtab/?')
        // add order to next page
        let new_url = new URL(previous_page.href);
        let search_params = new_url.searchParams;
        search_params.set('order', order);
        previous_page.href = new_url.href;
    }

    let next_page = document.querySelector('.bookacti-user-booking-list-next-page>a');
    if (next_page !== null) {
        next_page.href = next_page.href.replace('mon-compte-2/?', 'mon-compte-2/bookingtab/?')
        // add order to next page
        let new_url = new URL(next_page.href);
        let search_params = new_url.searchParams;
        search_params.set('order', order);
        next_page.href = new_url.href;
    }

    let url_args = new URLSearchParams(window.location.search);
    let page = url_args.get('bookacti_booking_list_paged_1');
    // if page is not an int or less than 1, set to 1
    if (page === null) {
        page = 1;
    }

    if (setup) {
        setTable(order === 'asc', page)
    }



}

function click_callback(event) {
    let target = event.target;
    let url_args = new URLSearchParams(window.location.search);
    let page = url_args.get('bookacti_booking_list_paged_1');
    if (page === null) {
        page = 1;
    }
    if (target.tagName === 'SPAN') {
        let reverse = target.classList.contains('reverse');
        // set the params to url 
        let new_url = new URL(window.location.href);
        let search_params = new_url.searchParams;
        search_params.set('order', reverse ? 'asc' : 'desc');
        // send ajax request to ba_plus_get_booking_list
        setTable(reverse, page)
    }
}

async function setTable(reverse, page = 1) {
    formData = new URLSearchParams();
    formData.append('action', 'baPlusGetBookingList');
    formData.append('order', reverse ? 'asc' : 'desc');
    formData.append('user_id', user_id);
    formData.append('uri', window.location.pathname);


    return fetch(`${ajaxurl}?bookacti_booking_list_paged_1=${page}`, {
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
            // replace the table
            let booking_table = document.querySelector('.bookacti-user-booking-list');
            booking_table.replaceWith(htmlToNode(data.data.html.trim()));
            enable(reverse ? 'asc' : 'desc');
        } else {
            console.log(data);
        }
    }).catch(error => {
        console.error(error);
    });
}

function htmlToNode(html) {
    const template = document.createElement('template');
    template.innerHTML = html;
    return template.content.firstChild;
}

document.addEventListener('DOMContentLoaded', main);