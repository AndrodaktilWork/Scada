document.addEventListener("DOMContentLoaded", function () {
  const hamBurger = document.querySelector("#toggle-btn");
  if (hamBurger) {
      hamBurger.addEventListener("click", function () {
          document.querySelector("#sidebar").classList.toggle("expand");
      });
  }
});

document.querySelectorAll(".save-schedule").forEach(btn => {
  btn.addEventListener("click", function() {
    const plantId = this.dataset.plant;
    const date = document.getElementById(`date-${plantId}`).value;
    if (!date) { alert("Моля изберете дата!"); return; }

    const values = {};
    document.querySelectorAll(`.schedule-input[data-plant='${plantId}']`).forEach(sel => {
      values[sel.dataset.hour] = sel.value;
    });

    fetch("/save_schedule", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ plant_id: plantId, date: date, values: values })
    }).then(r => r.json()).then(res => {
      if (res.status === "success") {
        alert("Графикът е записан успешно!");
        location.reload();
      }
    });
  });
});