(function() {
    var locale = {
  weekdays: {
    shorthand: ["Не", "По", "Вт", "Ср", "Че", "Пе", "Са"],
    longhand: [
      "Недела",
      "Понеделник",
      "Вторник",
      "Среда",
      "Четврток",
      "Петок",
      "Сабота",
    ],
  },

  months: {
    shorthand: [
      "Јан",
      "Фев",
      "Мар",
      "Апр",
      "Мај",
      "Јун",
      "Јул",
      "Авг",
      "Сеп",
      "Окт",
      "Ное",
      "Дек",
    ],
    longhand: [
      "Јануари",
      "Февруари",
      "Март",
      "Април",
      "Мај",
      "Јуни",
      "Јули",
      "Август",
      "Септември",
      "Октомври",
      "Ноември",
      "Декември",
    ],
  },

  firstDayOfWeek: 1,
  weekAbbreviation: "Нед.",
  rangeSeparator: " до ",
  time_24hr: true,
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.mk = locale);
    }
})();