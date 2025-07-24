(function() {
    var locale = {
  firstDayOfWeek: 1,

  weekdays: {
    shorthand: ["su", "ma", "ti", "ke", "to", "pe", "la"],
    longhand: [
      "sunnuntai",
      "maanantai",
      "tiistai",
      "keskiviikko",
      "torstai",
      "perjantai",
      "lauantai",
    ],
  },

  months: {
    shorthand: [
      "tammi",
      "helmi",
      "maalis",
      "huhti",
      "touko",
      "kesä",
      "heinä",
      "elo",
      "syys",
      "loka",
      "marras",
      "joulu",
    ],
    longhand: [
      "tammikuu",
      "helmikuu",
      "maaliskuu",
      "huhtikuu",
      "toukokuu",
      "kesäkuu",
      "heinäkuu",
      "elokuu",
      "syyskuu",
      "lokakuu",
      "marraskuu",
      "joulukuu",
    ],
  },

  ordinal: () => {
    return ".";
  },
  time_24hr: true,
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.fi = locale);
    }
})();