(function() {
    var locale = {
  firstDayOfWeek: 1,

  rangeSeparator: " ĝis ",
  weekAbbreviation: "Sem",
  scrollTitle: "Rulumu por pligrandigi la valoron",
  toggleTitle: "Klaku por ŝalti",

  weekdays: {
    shorthand: ["Dim", "Lun", "Mar", "Mer", "Ĵaŭ", "Ven", "Sab"],
    longhand: [
      "dimanĉo",
      "lundo",
      "mardo",
      "merkredo",
      "ĵaŭdo",
      "vendredo",
      "sabato",
    ],
  },

  months: {
    shorthand: [
      "Jan",
      "Feb",
      "Mar",
      "Apr",
      "Maj",
      "Jun",
      "Jul",
      "Aŭg",
      "Sep",
      "Okt",
      "Nov",
      "Dec",
    ],
    longhand: [
      "januaro",
      "februaro",
      "marto",
      "aprilo",
      "majo",
      "junio",
      "julio",
      "aŭgusto",
      "septembro",
      "oktobro",
      "novembro",
      "decembro",
    ],
  },

  ordinal: () => {
    return "-a";
  },
  time_24hr: true,
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.eo = locale);
    }
})();