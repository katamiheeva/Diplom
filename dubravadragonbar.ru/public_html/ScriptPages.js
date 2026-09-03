document.addEventListener('DOMContentLoaded', async function () {

    let isAuth = false;

    // проверяем авторизацию
    try {
        const res = await fetch('check_auth.php', {
            credentials: 'include',
            cache: 'no-store'
        });

        const data = await res.text();
        isAuth = (data === 'auth');

    } catch (e) {
        console.error(e);
    }

    // ереходы
    document.getElementById('PageGlavnay').onclick = () => window.location.href = 'Glavnay.html';
    document.getElementById('PageMenu').onclick = () => window.location.href = 'Menu.html';
    document.getElementById('PageBronirovanie').onclick = () => window.location.href = 'Bronirovanie.html';
    document.getElementById('PageShow').onclick = () => window.location.href = 'Show.html';
    document.getElementById('PageONas').onclick = () => window.location.href = 'ONas.html';

    //  авторизация
    document.getElementById('PageAvtorizacia').onclick = function (e) {
        e.preventDefault();

        if (isAuth) {
            window.location.href = 'Polzovatel.html';
        } else {
            window.location.href = 'Avtorizacia.html';
        }
    };

});