$(document).ready(function() {
    loadUserBookings();
});

function loadUserBookings() {
    $.ajax({
        url: 'get_bookings.php',
        method: 'GET',
        success: function(response) {
            $('#bookings-container').html(response);
        },
        error: function() {
            $('#bookings-container').html('<div class="alert alert-danger">Ошибка загрузки бронирований</div>');
        }
    });
}
