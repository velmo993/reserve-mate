(function() {
    var locale = {
  weekdays: {
    shorthand: ["Dg", "Dl", "Dt", "Dc", "Dj", "Dv", "Ds"],
    longhand: [
      "Diumenge",
      "Dilluns",
      "Dimarts",
      "Dimecres",
      "Dijous",
      "Divendres",
      "Dissabte",
    ],
  },

  months: {
    shorthand: [
      "Gen",
      "Febr",
      "Març",
      "Abr",
      "Maig",
      "Juny",
      "Jul",
      "Ag",
      "Set",
      "Oct",
      "Nov",
      "Des",
    ],
    longhand: [
      "Gener",
      "Febrer",
      "Març",
      "Abril",
      "Maig",
      "Juny",
      "Juliol",
      "Agost",
      "Setembre",
      "Octubre",
      "Novembre",
      "Desembre",
    ],
  },

  ordinal: (nth) => {
    const s = nth % 100;
    if (s > 3 && s < 21) return "è";
    switch (s % 10) {
      case 1:
        return "r";
      case 2:
        return "n";
      case 3:
        return "r";
      case 4:
        return "t";
      default:
        return "è";
    }
  },

  firstDayOfWeek: 1,
  rangeSeparator: " a ",
  time_24hr: true,
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.cat = locale);
    }
})();