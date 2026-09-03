document.getElementById('Zaregatsa').addEventListener('click', function() {
    window.location.href = 'Zaregistrirovatsa.html';
});

$(".Vhod").click(function () {

    let login = $("#Login").val();
    let password = $("#Parol").val();

    $.post("login.php", {
        login: login,
        password: password
    }, function (data) {

        if (data === "success") {
            window.location.href = "Polzovatel.html";
        } else {
            alert(data);
        }

    });

});

$("#Zaregatsa").click(function () {
    window.location.href = "Zaregistrirovatsa.html";
});