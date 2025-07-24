(function() {
    var locale = {
  weekdays: {
    shorthand: ["Søn", "Man", "Tir", "Ons", "Tor", "Fre", "Lør"],
    longhand: [
      "Søndag",
      "Mandag",
      "Tirsdag",
      "Onsdag",
      "Torsdag",
      "Fredag",
      "Lørdag",
    ],
  },

  months: {
    shorthand: [
      "Jan",
      "Feb",
      "Mar",
      "Apr",
      "Mai",
      "Jun",
      "Jul",
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
  weekAbbreviation: "Uke",
  scrollTitle: "Scroll for å endre",
  toggleTitle: "Klikk for å veksle",
  time_24hr: true,

  ordinal: () => {
    return ".";
  },
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.no = locale);
    }
})();