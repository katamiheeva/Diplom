let isAuth = false;

// уведомление
const notification = document.createElement('div');
notification.id = 'notification';

Object.assign(notification.style, {
    position: 'fixed',
    top: '20px',
    right: '20px',
    padding: '15px 25px',
    borderRadius: '8px',
    color: '#fff',
    fontWeight: 'bold',
    display: 'none',
    zIndex: 9999,
    boxShadow: '0 2px 8px rgba(0,0,0,0.3)',
    whiteSpace: 'pre-line'
});

document.body.appendChild(notification);

function showNotification(message, success = true) {
    notification.textContent = message;
    notification.style.backgroundColor = success ? '#28a745' : '#dc3545';
    notification.style.display = 'block';

    setTimeout(() => {
        notification.style.display = 'none';
    }, 4000);
}

// ПРОВЕРКА АВТОРИЗАЦИИ
async function checkAuth() {
    try {
        const res = await fetch('check_auth.php', {
            credentials: 'include',
            cache: 'no-store'
        });

        const data = await res.text();
        isAuth = (data === 'auth');

        if (!isAuth) {
            showNotification('Вы не авторизованы! Запись недоступна', false);

            const btn = document.querySelector('form button[type="submit"]');
            if (btn) btn.disabled = true;
        }

    } catch (e) {
        console.error(e);
    }
}

// маски
function initInputMasks() {
    IMask(document.getElementById('Date'), { mask: '00.00.0000' });

    IMask(document.getElementById('TimePodacha'), {
        mask: '00:00'
    });
}

// форма
function setupFormSubmission() {
    const form = document.querySelector('form');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!isAuth) {
            showNotification('Пожалуйста, войдите в аккаунт', false);
            return;
        }

        if (!form.checkValidity()) {
            showNotification('Пожалуйста, заполните все поля', false);
            return;
        }

        const formData = new FormData(form);

        fetch('RegistrationSvoyaIgra.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })
        .then(res => res.json())
        .then(data => {
            showNotification(data.message, data.success);

            if (data.success) {
                form.reset();
                document.getElementById('check').checked = false;
            }
        })
        .catch(err => {
            console.error(err);
            showNotification('Ошибка отправки', false);
        });
    });
}

// карусель 
function initCarousel() {
    $('.carousel').each(function() {
        const carousel = $(this);
        let currentIndex = 0;
        const images = carousel.find('.carousel-images img');
        const totalImages = images.length;

        function getSlideWidth() {
            return images.first().outerWidth(true);
        }

        function showSlide(index) {
            if (index >= totalImages) currentIndex = 0;
            else if (index < 0) currentIndex = totalImages - 1;
            else currentIndex = index;

            const offset = -currentIndex * getSlideWidth();

            carousel.find('.carousel-images')
                .css('transform', `translateX(${offset}px)`);
        }

        carousel.find('.prev').click(e => {
            e.preventDefault();
            showSlide(currentIndex - 1);
        });

        carousel.find('.next').click(e => {
            e.preventDefault();
            showSlide(currentIndex + 1);
        });

        $(window).on('resize', () => showSlide(currentIndex));

        showSlide(0);
    });
}

// запуск
document.addEventListener('DOMContentLoaded', async function() {
    await checkAuth(); 
    initInputMasks();
    setupFormSubmission();
});

$(document).ready(function() {
    initCarousel();
});