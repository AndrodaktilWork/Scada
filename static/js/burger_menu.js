document.addEventListener("DOMContentLoaded", function () {
  const hamBurger = document.querySelector("#toggle-btn");
  if (hamBurger) {
      hamBurger.addEventListener("click", function () {
          document.querySelector("#sidebar").classList.toggle("expand");
      });
  }
});

// Добавете тази функция в началото на script тага
function generateCalendar(clientId, year, month) {
  const calendarBody = document.getElementById(`calendar-body-${clientId}`);
  const monthDisplay = document.querySelector(`.current-month[data-client="${clientId}"]`);
  
  // Актуализираме display на месеца
  const monthNames = ["Януари", "Февруари", "Март", "Април", "Май", "Юни", 
                     "Юли", "Август", "Септември", "Октомври", "Ноември", "Декември"];
  monthDisplay.textContent = `${monthNames[month]} ${year}`;
  
  // Изчисляваме първия ден от месеца
  const firstDay = new Date(year, month, 1).getDay();
  // Коригираме понеделник да е първи ден (0 = неделя, 1 = понеделник...)
  const firstDayIndex = firstDay === 0 ? 6 : firstDay - 1;
  
  // Брой дни в месеца
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  
  let calendarHTML = '';
  let date = 1;
  
  // Генерираме седмиците
  for (let i = 0; i < 6; i++) {
    calendarHTML += '<tr>';
    
    for (let j = 0; j < 7; j++) {
      if (i === 0 && j < firstDayIndex) {
        // Празни клетки преди първия ден
        const prevMonthDays = new Date(year, month, 0).getDate();
        calendarHTML += `<td class="text-muted">${prevMonthDays - firstDayIndex + j + 1}</td>`;
      } else if (date > daysInMonth) {
        // Празни клетки след последния ден
        calendarHTML += `<td class="text-muted">${date - daysInMonth}</td>`;
        date++;
      } else {
        // Дни от текущия месец
        const today = new Date();
        const isToday = date === today.getDate() && 
                       month === today.getMonth() && 
                       year === today.getFullYear();
        
        const dayClass = isToday ? 'calendar-day today' : 'calendar-day';
        calendarHTML += `<td class="${dayClass}" data-day="${date}" data-month="${month + 1}" data-year="${year}">${date}</td>`;
        date++;
      }
    }
    
    calendarHTML += '</tr>';
    
    if (date > daysInMonth) {
      break;
    }
  }
  
  calendarBody.innerHTML = calendarHTML;
  
  // Добавяме event listeners за новите дни
  document.querySelectorAll(`#calendar-body-${clientId} .calendar-day`).forEach(day => {
    day.addEventListener('click', function() {
      // Премахваме селектирания клас от всички дни
      document.querySelectorAll(`#calendar-body-${clientId} .calendar-day`).forEach(d => {
        d.classList.remove('selected');
      });
      
      // Добавяме селектирания клас към кликнатия ден
      this.classList.add('selected');
      
      // Актуализираме полето за дата
      const selectedDay = this.getAttribute('data-day');
      const selectedMonth = this.getAttribute('data-month');
      const selectedYear = this.getAttribute('data-year');
      
      const selectedDate = `${selectedYear}-${selectedMonth.padStart(2, '0')}-${selectedDay.padStart(2, '0')}`;
      
      // Актуализираме display на избраната дата
      document.getElementById(`selected-date-${clientId}`).textContent = selectedDate;
      
      console.log('Избрана дата:', selectedDate);
    });
  });
}

// Функционалност за шаблоните
  const templateRadios = document.querySelectorAll('input[name^="scheduleTemplate-"]');
  
  templateRadios.forEach(radio => {
    radio.addEventListener('change', function() {
      const clientId = this.id.split('-').pop();
      const scheduleInputs = document.querySelectorAll(`.schedule-input[data-client="${clientId}"]`);
      
      if (this.id.includes('all-0')) {
        // Всички 0%
        scheduleInputs.forEach(input => {
          input.value = '0';
        });
      } else if (this.id.includes('all-50')) {
        // Всички 50%
        scheduleInputs.forEach(input => {
          input.value = '50';
        });
      } else if (this.id.includes('all-100')) {
        // Всички 100%
        scheduleInputs.forEach(input => {
          input.value = '100';
        });
      } else if (this.id.includes('workday')) {
        // Работен ден (8–18ч – 60%, друго 100%)
        scheduleInputs.forEach(input => {
          const hour = parseInt(input.getAttribute('data-hour'));
          if (hour >= 8 && hour < 18) {
            input.value = '60';
          } else {
            input.value = '100';
          }
        });
      }
    });
  });
  
  // Функционалност за запазване
  document.querySelectorAll(".save-schedule").forEach(btn => {
    btn.addEventListener("click", function() {
      const clientId = this.dataset.client;
      const selectedDate = document.getElementById(`selected-date-${clientId}`).textContent;
      
      if (selectedDate === "Не е избрана дата") { 
        alert("Моля изберете дата!"); 
        return; 
      }

      const values = {};
      document.querySelectorAll(`.schedule-input[data-client='${clientId}']`).forEach(sel => {
        values[sel.dataset.hour] = sel.value;
      });

      fetch("/save_schedule", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ client_id: clientId, date: selectedDate, values: values })
      }).then(r => r.json()).then(res => {
        if (res.status === "success") {
          alert("Графикът е записан успешно!");
          location.reload();
        }
      });
    });
  });