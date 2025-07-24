(function() {
    var locale = {
  weekdays: {
    shorthand: ["Paz", "Pzt", "Sal", "Çar", "Per", "Cum", "Cmt"],
    longhand: [
      "Pazar",
      "Pazartesi",
      "Salı",
      "Çarşamba",
      "Perşembe",
      "Cuma",
      "Cumartesi",
    ],
  },

  months: {
    shorthand: [
      "Oca",
      "Şub",
      "Mar",
      "Nis",
      "May",
      "Haz",
      "Tem",
      "Ağu",
      "Eyl",
      "Eki",
      "Kas",
      "Ara",
    ],
    longhand: [
      "Ocak",
      "Şubat",
      "Mart",
      "Nisan",
      "Mayıs",
      "Haziran",
      "Temmuz",
      "Ağustos",
      "Eylül",
      "Ekim",
      "Kasım",
      "Aralık",
    ],
  },
  firstDayOfWeek: 1,
  ordinal: () => {
    return ".";
  },
  rangeSeparator: " - ",
  weekAbbreviation: "Hf",
  scrollTitle: "Artırmak için kaydırın",
  toggleTitle: "Aç/Kapa",
  amPM: ["ÖÖ", "ÖS"],
  time_24hr: true,
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.tr = locale);
    }
})();