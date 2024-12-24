/**
 * Admin .js file
 */

const body = document.querySelector('body');

const ajaxWrapper = async (parameters = {}) => {

    const data = new FormData();

    for (const key in parameters) {
        data.append(key, parameters[key]);
    }

    let response = [];

    try {
        response = await fetch(
            ajaxurl,
            {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                },
                body: data,
            },
        );
    }
    catch (e) {
        console.log(e.message);
    }

    return response.json();
}

document.addEventListener('DOMContentLoaded', () => {
    const conserveCheckboxes = document.querySelectorAll('.conserve-page-checkbox');
    conserveCheckboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            conservePage(this);
        });
    });
});

window.onload = () => {
};


window.onresize = () => {

};


window.onscroll = () => {

};

const conservePage = (checkbox) => {
    const postId = checkbox.dataset.postId;

    if (!postId) {
        return;
    }

    const pageList = document.getElementById('posts-filter');
    const filterList = document.querySelectorAll('.subsubsub');

    if (pageList) {
        pageList.classList.add('conserve-loading');
    }

    filterList.forEach(el => el.classList.add('conserve-loading'));


    ajaxWrapper({
        action: 'toggle_conserve_page',
        post_id: postId,
        is_conserved: checkbox.checked
    })
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => console.error(error));
};