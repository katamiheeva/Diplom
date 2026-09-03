document.addEventListener('DOMContentLoaded', async function() {
  let isAuth = false;

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
    boxShadow: '0 2px 8px rgba(0, 0, 0, 0.3)',
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

  //проврека авторизации
  try {
    const res = await fetch('check_auth.php', {
      credentials: 'include'
    });

    const data = await res.text();
    isAuth = (data === 'auth');

    if (!isAuth) {
      showNotification('Вы не авторизованы! Бронирование недоступно', false);
      const submitBtn = document.querySelector('form button[type="submit"]');
      if (submitBtn) submitBtn.disabled = true;
    }
  } catch (e) {
    console.error(e);
    showNotification('Ошибка подключения к серверу', false);
  }

  //маски ввода
  const dateInput = document.getElementById('Date');
  if (dateInput) {
    IMask(dateInput, { mask: '00.00.0000' });
  }

  const timeInputs = [
    document.getElementById('TimeNach'),
    document.getElementById('TimeCon')
  ];
  const timeMask = { mask: '00:00' };
  timeInputs.forEach(input => {
    if (input) IMask(input, timeMask);
  });

  const form = document.querySelector('form');
  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();

      if (!isAuth) {
        showNotification('Пожалуйста, войдите в аккаунт', false);
        return;
      }

      const timeFrom = document.getElementById('TimeNach').value;
      if (timeFrom && timeFrom < '16:00') {
        showNotification('Рабочий день начинается с 16:00', false);
        return;
      }

      const formData = new FormData(form);

      fetch('BronirovanieStol.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
      })
        .then(res => res.json())
        .then(data => {
          data.messages.forEach(msg => showNotification(msg, data.success));
          if (data.success) {
            form.reset();
          }
          updateTables(statusDateInput.value);
        })
        .catch(err => {
          console.error('Ошибка отправки формы:', err);
          showNotification('Произошла ошибка при отправке данных', false);
        });
    });
  }

  const statusDateInput = document.getElementById('statusDate');

  if (statusDateInput) {
    statusDateInput.addEventListener('change', () => {
      updateTables(statusDateInput.value);
    });
  }

  function updateTables(dateStr = null) {
    // Форматирование даты в YYYY-MM-DD
    let date;
    if (dateStr) {
      //дд.мм.гггг в гггг-мм-дд
      const parts = dateStr.split('.');
      if (parts.length === 3) {
        date = `${parts[2]}-${parts[1]}-${parts[0]}`;
      } else {
        date = dateStr;
      }
    } else {
      date = statusDateInput?.value || new Date().toISOString().slice(0, 10);
    }

    fetch(`GetTablesStatus.php?date=${date}`, {
      credentials: 'include'
    })
      .then(res => {
        if (!res.ok) {
          throw new Error(`HTTP error! status: ${res.status}`);
        }
        return res.json();
      })
      .then(data => {
        //ошибки от сервера
        if (data.error) {
          showNotification(data.message || 'Ошибка загрузки статуса столов', false);
          return;
        }

        data.forEach(table => {
          const el = document.getElementById('table' + table.table_number);
          if (!el) return;

          el.dataset.seats = table.seats;

          if (table.bookings.length > 0) {
            el.style.backgroundColor = '#ff4d4d';
          } else {
            el.style.backgroundColor = '#66cc66';
          }

          //времени из бд в формат чч:мм
          const bookingsText = table.bookings.map(b => {
            const from = b.time_from.substring(0, 5);
            const to = b.time_to.substring(0, 5);
            return `${from}-${to}`;
          });

          el.dataset.bookings = bookingsText.join(', ') || '-';
        });
      })
      .catch(err => {
        console.error('Ошибка загрузки статуса столов:', err);
        showNotification('Не удалось загрузить статус столов', false);
      });
  }

  updateTables();
  setInterval(() => updateTables(statusDateInput?.value), 60000); 

  //обработчики кликов для столов 
  document.querySelectorAll('.krug, .oval, .reg').forEach(el => {
    el.addEventListener('click', () => {
      const seats = el.dataset.seats || '-';
      const bookings = el.dataset.bookings || '-';

      showNotification(
        `Стол ${el.id.replace('table', '')}\nМест: ${seats}\nБрони: ${bookings}`,
        true
      );
    });
  });
});
