(function() {
    var locale = {
  weekdays: {
    shorthand: ["Ned", "Pon", "Tor", "Sre", "Čet", "Pet", "Sob"],
    longhand: [
      "Nedelja",
      "Ponedeljek",
      "Torek",
      "Sreda",
      "Četrtek",
      "Petek",
      "Sobota",
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
      "Avg",
      "Sep",
      "Okt",
      "Nov",
      "Dec",
    ],
    longhand: [
      "Januar",
      "Februar",
      "Marec",
      "April",
      "Maj",
      "Junij",
      "Julij",
      "Avgust",
      "September",
      "Oktober",
      "November",
      "December",
    ],
  },

  firstDayOfWeek: 1,
  rangeSeparator: " do ",
  time_24hr: true,
  ordinal: function () {
    return ".";
  },
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.sl = locale);
    }
})();