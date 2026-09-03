$("#Zaregatsa").click(function () {

    let username = $("#Imay").val();
    let login = $("#Login").val();
    let password = $("#Parol").val();
    let password2 = $("#ParolDub").val();

    if (password !== password2) {
        alert("Пароли не совпадают");
        return;
    }

    $.post("registr.php", {
        username: username,
        login: login,
        password: password
    }, function (data) {

        if (data === "success") {
            alert("Успешная регистрация");
            window.location.href = "Avtorizacia.html";
        } else {
            window.location.href = "error.php";
        }

    });

});