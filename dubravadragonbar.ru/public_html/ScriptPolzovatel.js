document.addEventListener("DOMContentLoaded", async function () {
    let userRole = "user";

    try {
        const res = await fetch("profile.php", {
            credentials: "include",
            cache: "no-store"
        });
        const data = await res.json();

        if (!data.auth) {
            window.location.href = "Avtorizacia.html";
            return;
        }

        // Заполняем поле имени
        document.getElementById("Imay").value = data.full_name;
        userRole = data.role;

    } catch (e) {
        console.error(e);
    }

    // кнопки
    document.getElementById('Zabron').onclick = () => window.location.href = 'Bronirovanie.html';
    document.getElementById('Zaregatsa').onclick = () => window.location.href = 'SvoyaIgra.html';

    document.getElementById('Bron').onclick = () => {
        if (userRole === "admin") {
            window.location.href = "AdminZapiciBron.php";
        } else {
            window.location.href = "MoiZapiciBron.php";
        }
    };

    document.getElementById('Regi').onclick = () => {
        if (userRole === "admin") {
            window.location.href = "AdminZapiciIgra.php";
        } else {
            window.location.href = "MoiZapiciIgra.php";
        }
    };
    
     if (userRole !== 'admin') {
  const adminButtonsContainer = document.querySelector('.Knopki3');
  if (adminButtonsContainer) {
    adminButtonsContainer.remove();
  }
} else {
  // Для админа назначаем обработчики
  document.getElementById('Sotrudniki').onclick = () => {
    window.location.href = "AdminSotrudniki.php";
  };

  document.getElementById('Grafic').onclick = () => {
    window.location.href = "AdminSmens.php";
  };
}

    $("#Logout").click(() => {
        $.post("logout.php", (data) => {
            if (data === "success") window.location.href = "Avtorizacia.html";
        });
    });
});