(function() {
    var locale = {
  firstDayOfWeek: 1,

  weekdays: {
    shorthand: ["Sv", "Pr", "Ot", "Tr", "Ce", "Pk", "Se"],
    longhand: [
      "Svētdiena",
      "Pirmdiena",
      "Otrdiena",
      "Trešdiena",
      "Ceturtdiena",
      "Piektdiena",
      "Sestdiena",
    ],
  },

  months: {
    shorthand: [
      "Jan",
      "Feb",
      "Mar",
      "Apr",
      "Mai",
      "Jūn",
      "Jūl",
      "Aug",
      "Sep",
      "Okt",
      "Nov",
      "Dec",
    ],
    longhand: [
      "Janvāris",
      "Februāris",
      "Marts",
      "Aprīlis",
      "Maijs",
      "Jūnijs",
      "Jūlijs",
      "Augusts",
      "Septembris",
      "Oktobris",
      "Novembris",
      "Decembris",
    ],
  },

  rangeSeparator: " līdz ",
  time_24hr: true,
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.lv = locale);
    }
})();