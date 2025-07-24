(function() {
    var locale = {
  weekdays: {
    shorthand: ["Нд", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб"],
    longhand: [
      "Неделя",
      "Понеделник",
      "Вторник",
      "Сряда",
      "Четвъртък",
      "Петък",
      "Събота",
    ],
  },

  months: {
    shorthand: [
      "Яну",
      "Фев",
      "Март",
      "Апр",
      "Май",
      "Юни",
      "Юли",
      "Авг",
      "Сеп",
      "Окт",
      "Ное",
      "Дек",
    ],
    longhand: [
      "Януари",
      "Февруари",
      "Март",
      "Април",
      "Май",
      "Юни",
      "Юли",
      "Август",
      "Септември",
      "Октомври",
      "Ноември",
      "Декември",
    ],
  },
  time_24hr: true,
  firstDayOfWeek: 1,
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.bg = locale);
    }
})();