(function() {
    var locale = {
  weekdays: {
    shorthand: ["Sø.", "Må.", "Ty.", "On.", "To.", "Fr.", "La."],
    longhand: [
      "Søndag",
      "Måndag",
      "Tysdag",
      "Onsdag",
      "Torsdag",
      "Fredag",
      "Laurdag",
    ],
  },

  months: {
    shorthand: [
      "Jan",
      "Feb",
      "Mars",
      "Apr",
      "Mai",
      "Juni",
      "Juli",
      "Aug",
      "Sep",
      "Okt",
      "Nov",
      "Des",
    ],
    longhand: [
      "Januar",
      "Februar",
      "Mars",
      "April",
      "Mai",
      "Juni",
      "Juli",
      "August",
      "September",
      "Oktober",
      "November",
      "Desember",
    ],
  },

  firstDayOfWeek: 1,
  rangeSeparator: " til ",
  weekAbbreviation: "Veke",
  scrollTitle: "Scroll for å endre",
  toggleTitle: "Klikk for å veksle",
  time_24hr: true,

  ordinal: () => {
    return ".";
  },
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.nn = locale);
    }
})();