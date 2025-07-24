(function() {
    var locale = {
  firstDayOfWeek: 1,

  weekdays: {
    shorthand: ["Dom", "Lua", "Mái", "Céa", "Déa", "Aoi", "Sat"],
    longhand: [
      "Dé Domhnaigh",
      "Dé Luain",
      "Dé Máirt",
      "Dé Céadaoin",
      "Déardaoin",
      "Dé hAoine",
      "Dé Sathairn",
    ],
  },

  months: {
    shorthand: [
      "Ean",
      "Fea",
      "Már",
      "Aib",
      "Bea",
      "Mei",
      "Iúi",
      "Lún",
      "MFo",
      "DFo",
      "Sam",
      "Nol",
    ],
    longhand: [
      "Eanáir",
      "Feabhra",
      "Márta",
      "Aibreán",
      "Bealtaine",
      "Meitheamh",
      "Iúil",
      "Lúnasa",
      "Meán Fómhair",
      "Deireadh Fómhair",
      "Samhain",
      "Nollaig",
    ],
  },
  time_24hr: true,
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.ga = locale);
    }
})();