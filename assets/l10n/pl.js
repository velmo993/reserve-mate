(function() {
    var locale = {
  weekdays: {
    shorthand: ["Nd", "Pn", "Wt", "Śr", "Cz", "Pt", "So"],
    longhand: [
      "Niedziela",
      "Poniedziałek",
      "Wtorek",
      "Środa",
      "Czwartek",
      "Piątek",
      "Sobota",
    ],
  },

  months: {
    shorthand: [
      "Sty",
      "Lut",
      "Mar",
      "Kwi",
      "Maj",
      "Cze",
      "Lip",
      "Sie",
      "Wrz",
      "Paź",
      "Lis",
      "Gru",
    ],
    longhand: [
      "Styczeń",
      "Luty",
      "Marzec",
      "Kwiecień",
      "Maj",
      "Czerwiec",
      "Lipiec",
      "Sierpień",
      "Wrzesień",
      "Październik",
      "Listopad",
      "Grudzień",
    ],
  },
  rangeSeparator: " do ",
  weekAbbreviation: "tydz.",
  scrollTitle: "Przewiń, aby zwiększyć",
  toggleTitle: "Kliknij, aby przełączyć",
  firstDayOfWeek: 1,
  time_24hr: true,

  ordinal: () => {
    return ".";
  },
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.pl = locale);
    }
})();