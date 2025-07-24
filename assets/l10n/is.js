(function() {
    var locale = {
  weekdays: {
    shorthand: ["Sun", "Mán", "Þri", "Mið", "Fim", "Fös", "Lau"],
    longhand: [
      "Sunnudagur",
      "Mánudagur",
      "Þriðjudagur",
      "Miðvikudagur",
      "Fimmtudagur",
      "Föstudagur",
      "Laugardagur",
    ],
  },

  months: {
    shorthand: [
      "Jan",
      "Feb",
      "Mar",
      "Apr",
      "Maí",
      "Jún",
      "Júl",
      "Ágú",
      "Sep",
      "Okt",
      "Nóv",
      "Des",
    ],
    longhand: [
      "Janúar",
      "Febrúar",
      "Mars",
      "Apríl",
      "Maí",
      "Júní",
      "Júlí",
      "Ágúst",
      "September",
      "Október",
      "Nóvember",
      "Desember",
    ],
  },

  ordinal: () => {
    return ".";
  },

  firstDayOfWeek: 1,
  rangeSeparator: " til ",
  weekAbbreviation: "vika",
  yearAriaLabel: "Ár",
  time_24hr: true,
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.is = locale);
    }
})();