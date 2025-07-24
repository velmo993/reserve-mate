(function() {
    var locale = {
  weekdays: {
    shorthand: ["Ne", "Po", "Út", "St", "Čt", "Pá", "So"],
    longhand: [
      "Neděle",
      "Pondělí",
      "Úterý",
      "Středa",
      "Čtvrtek",
      "Pátek",
      "Sobota",
    ],
  },
  months: {
    shorthand: [
      "Led",
      "Ún",
      "Bře",
      "Dub",
      "Kvě",
      "Čer",
      "Čvc",
      "Srp",
      "Zář",
      "Říj",
      "Lis",
      "Pro",
    ],
    longhand: [
      "Leden",
      "Únor",
      "Březen",
      "Duben",
      "Květen",
      "Červen",
      "Červenec",
      "Srpen",
      "Září",
      "Říjen",
      "Listopad",
      "Prosinec",
    ],
  },
  firstDayOfWeek: 1,
  ordinal: function () {
    return ".";
  },
  rangeSeparator: " do ",
  weekAbbreviation: "Týd.",
  scrollTitle: "Rolujte pro změnu",
  toggleTitle: "Přepnout dopoledne/odpoledne",
  amPM: ["dop.", "odp."],
  yearAriaLabel: "Rok",
  time_24hr: true,
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.cs = locale);
    }
})();