(function() {
    var locale = {
  weekdays: {
    shorthand: ["Вс", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб"],
    longhand: [
      "Воскресенье",
      "Понедельник",
      "Вторник",
      "Среда",
      "Четверг",
      "Пятница",
      "Суббота",
    ],
  },
  months: {
    shorthand: [
      "Янв",
      "Фев",
      "Март",
      "Апр",
      "Май",
      "Июнь",
      "Июль",
      "Авг",
      "Сен",
      "Окт",
      "Ноя",
      "Дек",
    ],
    longhand: [
      "Январь",
      "Февраль",
      "Март",
      "Апрель",
      "Май",
      "Июнь",
      "Июль",
      "Август",
      "Сентябрь",
      "Октябрь",
      "Ноябрь",
      "Декабрь",
    ],
  },
  firstDayOfWeek: 1,
  ordinal: function () {
    return "";
  },
  rangeSeparator: " — ",
  weekAbbreviation: "Нед.",
  scrollTitle: "Прокрутите для увеличения",
  toggleTitle: "Нажмите для переключения",
  amPM: ["ДП", "ПП"],
  yearAriaLabel: "Год",
  time_24hr: true,
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.ru = locale);
    }
})();