(function() {
    var locale = {
  weekdays: {
    shorthand: ["Di", "Hë", "Ma", "Më", "En", "Pr", "Sh"],
    longhand: [
      "E Diel",
      "E Hënë",
      "E Martë",
      "E Mërkurë",
      "E Enjte",
      "E Premte",
      "E Shtunë",
    ],
  },

  months: {
    shorthand: [
      "Jan",
      "Shk",
      "Mar",
      "Pri",
      "Maj",
      "Qer",
      "Kor",
      "Gus",
      "Sht",
      "Tet",
      "Nën",
      "Dhj",
    ],
    longhand: [
      "Janar",
      "Shkurt",
      "Mars",
      "Prill",
      "Maj",
      "Qershor",
      "Korrik",
      "Gusht",
      "Shtator",
      "Tetor",
      "Nëntor",
      "Dhjetor",
    ],
  },
  firstDayOfWeek: 1,
  rangeSeparator: " deri ",
  weekAbbreviation: "Java",
  yearAriaLabel: "Viti",
  monthAriaLabel: "Muaji",
  hourAriaLabel: "Ora",
  minuteAriaLabel: "Minuta",
  time_24hr: true,
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.sq = locale);
    }
})();