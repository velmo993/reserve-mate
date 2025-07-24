(function() {
    var locale = {
  firstDayOfWeek: 1,

  weekdays: {
    shorthand: ["V", "H", "K", "Sz", "Cs", "P", "Szo"],
    longhand: [
      "Vasárnap",
      "Hétfő",
      "Kedd",
      "Szerda",
      "Csütörtök",
      "Péntek",
      "Szombat",
    ],
  },

  months: {
    shorthand: [
      "Jan",
      "Feb",
      "Már",
      "Ápr",
      "Máj",
      "Jún",
      "Júl",
      "Aug",
      "Szep",
      "Okt",
      "Nov",
      "Dec",
    ],
    longhand: [
      "Január",
      "Február",
      "Március",
      "Április",
      "Május",
      "Június",
      "Július",
      "Augusztus",
      "Szeptember",
      "Október",
      "November",
      "December",
    ],
  },

  ordinal: function () {
    return ".";
  },

  weekAbbreviation: "Hét",
  scrollTitle: "Görgessen",
  toggleTitle: "Kattintson a váltáshoz",
  rangeSeparator: " - ",
  time_24hr: true,
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.hu = locale);
    }
})();