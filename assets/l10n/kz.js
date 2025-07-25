(function() {
    var locale = {
  weekdays: {
    shorthand: ["Жс", "Дс", "Сc", "Ср", "Бс", "Жм", "Сб"],
    longhand: [
      "Жексенбi",
      "Дүйсенбi",
      "Сейсенбi",
      "Сәрсенбi",
      "Бейсенбi",
      "Жұма",
      "Сенбi",
    ],
  },
  months: {
    shorthand: [
      "Қаң",
      "Ақп",
      "Нау",
      "Сәу",
      "Мам",
      "Мау",
      "Шiл",
      "Там",
      "Қыр",
      "Қаз",
      "Қар",
      "Жел",
    ],
    longhand: [
      "Қаңтар",
      "Ақпан",
      "Наурыз",
      "Сәуiр",
      "Мамыр",
      "Маусым",
      "Шiлде",
      "Тамыз",
      "Қыркүйек",
      "Қазан",
      "Қараша",
      "Желтоқсан",
    ],
  },
  firstDayOfWeek: 1,
  ordinal: function () {
    return "";
  },
  rangeSeparator: " — ",
  weekAbbreviation: "Апта",
  scrollTitle: "Үлкейту үшін айналдырыңыз",
  toggleTitle: "Ауыстыру үшін басыңыз",
  amPM: ["ТД", "ТК"],
  yearAriaLabel: "Жыл",
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.kz = locale);
    }
})();