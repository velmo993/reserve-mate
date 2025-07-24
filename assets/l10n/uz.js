(function() {
    var locale = {
  weekdays: {
    shorthand: ["Якш", "Душ", "Сеш", "Чор", "Пай", "Жум", "Шан"],
    longhand: [
      "Якшанба",
      "Душанба",
      "Сешанба",
      "Чоршанба",
      "Пайшанба",
      "Жума",
      "Шанба",
    ],
  },
  months: {
    shorthand: [
      "Янв",
      "Фев",
      "Мар",
      "Апр",
      "Май",
      "Июн",
      "Июл",
      "Авг",
      "Сен",
      "Окт",
      "Ноя",
      "Дек",
    ],
    longhand: [
      "Январ",
      "Феврал",
      "Март",
      "Апрел",
      "Май",
      "Июн",
      "Июл",
      "Август",
      "Сентябр",
      "Октябр",
      "Ноябр",
      "Декабр",
    ],
  },
  firstDayOfWeek: 1,
  ordinal: function () {
    return "";
  },
  rangeSeparator: " — ",
  weekAbbreviation: "Ҳафта",
  scrollTitle: "Катталаштириш учун айлантиринг",
  toggleTitle: "Ўтиш учун босинг",
  amPM: ["AM", "PM"],
  yearAriaLabel: "Йил",
  time_24hr: true,
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.uz = locale);
    }
})();