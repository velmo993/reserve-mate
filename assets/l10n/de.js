(function() {
    var locale = {
  weekdays: {
    shorthand: ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"],
    longhand: [
      "Sonntag",
      "Montag",
      "Dienstag",
      "Mittwoch",
      "Donnerstag",
      "Freitag",
      "Samstag",
    ],
  },

  months: {
    shorthand: [
      "Jan",
      "Feb",
      "Mär",
      "Apr",
      "Mai",
      "Jun",
      "Jul",
      "Aug",
      "Sep",
      "Okt",
      "Nov",
      "Dez",
    ],
    longhand: [
      "Januar",
      "Februar",
      "März",
      "April",
      "Mai",
      "Juni",
      "Juli",
      "August",
      "September",
      "Oktober",
      "November",
      "Dezember",
    ],
  },

  firstDayOfWeek: 1,
  weekAbbreviation: "KW",
  rangeSeparator: " bis ",
  scrollTitle: "Zum Ändern scrollen",
  toggleTitle: "Zum Umschalten klicken",
  time_24hr: true,
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.de = locale);
    }
})();