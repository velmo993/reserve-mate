(function() {
    var locale = {
  weekdays: {
    shorthand: ["S", "Pr", "A", "T", "K", "Pn", "Š"],
    longhand: [
      "Sekmadienis",
      "Pirmadienis",
      "Antradienis",
      "Trečiadienis",
      "Ketvirtadienis",
      "Penktadienis",
      "Šeštadienis",
    ],
  },

  months: {
    shorthand: [
      "Sau",
      "Vas",
      "Kov",
      "Bal",
      "Geg",
      "Bir",
      "Lie",
      "Rgp",
      "Rgs",
      "Spl",
      "Lap",
      "Grd",
    ],
    longhand: [
      "Sausis",
      "Vasaris",
      "Kovas",
      "Balandis",
      "Gegužė",
      "Birželis",
      "Liepa",
      "Rugpjūtis",
      "Rugsėjis",
      "Spalis",
      "Lapkritis",
      "Gruodis",
    ],
  },

  firstDayOfWeek: 1,

  ordinal: function () {
    return "-a";
  },
  rangeSeparator: " iki ",
  weekAbbreviation: "Sav",
  scrollTitle: "Keisti laiką pelės rateliu",
  toggleTitle: "Perjungti laiko formatą",
  time_24hr: true,
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.lt = locale);
    }
})();