$(document).ready(function() {
    $('.carousel').each(function() {
        const carousel = $(this);
        let currentIndex = 0;
        const images = carousel.find('.carousel-images img');
        const totalImages = images.length;

        function getSlideWidth() {
            // Берём ширину текущего изображения с учётом padding/margin/border
            return images.first().outerWidth(true);
        }

        function showSlide(index) {
            // Зацикливание
            if (index >= totalImages) {
                currentIndex = 0;
            } else if (index < 0) {
                currentIndex = totalImages - 1;
            } else {
                currentIndex = index;
            }

            const slideWidth = getSlideWidth();
            const offset = -currentIndex * slideWidth;
            carousel.find('.carousel-images').css(
                'transform',
                `translateX(${offset}px)`
            );
        }

        function moveSlide(direction) {
            showSlide(currentIndex + direction);
        }

        // Обработчики кнопок
        carousel.find('.prev').on('click', () => {
            moveSlide(-1);
        });

        carousel.find('.next').on('click', () => {
            moveSlide(1);
        });

        // При ресайзе пересчитываем слайдWidth и корректируем трансформацию
        $(window).on('resize', function() {
            showSlide(currentIndex);
        });

        // Инициализация
        showSlide(0);
    });
});


$(document).ready(function() {
    $('.carousel').each(function() {
        const carousel = $(this);
        let currentIndex = 0;
        const images = carousel.find('.carousel-images img');
        const totalImages = images.length;

        function getSlideWidth() {
            // Берём ширину текущего изображения с учётом padding/margin/border
            return images.first().outerWidth(true);
        }

        function showSlide(index) {
            // Зацикливание
            if (index >= totalImages) {
                currentIndex = 0;
            } else if (index < 0) {
                currentIndex = totalImages - 1;
            } else {
                currentIndex = index;
            }

            const slideWidth = getSlideWidth();
            const offset = -currentIndex * slideWidth;
            carousel.find('.carousel-images').css(
                'transform',
                `translateX(${offset}px)`
            );
        }

        function moveSlide(direction) {
            showSlide(currentIndex + direction);
        }

        // Обработчики кнопок — убираем onclick из HTML, работаем только через JS
        carousel.find('.prev').on('click', function(e) {
            e.preventDefault();
            moveSlide(-1);
        });

        carousel.find('.next').on('click', function(e) {
            e.preventDefault();
            moveSlide(1);
        });

        // При ресайзе пересчитываем слайдWidth и корректируем трансформацию
        $(window).on('resize', function() {
            showSlide(currentIndex);
        });

        // Инициализация
        showSlide(0);
    });
});

