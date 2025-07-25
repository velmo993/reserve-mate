(function() {
    var locale = {
  weekdays: {
    shorthand: ["Κυ", "Δε", "Τρ", "Τε", "Πέ", "Πα", "Σά"],
    longhand: [
      "Κυριακή",
      "Δευτέρα",
      "Τρίτη",
      "Τετάρτη",
      "Πέμπτη",
      "Παρασκευή",
      "Σάββατο",
    ],
  },

  months: {
    shorthand: [
      "Ιαν",
      "Φεβ",
      "Μάρ",
      "Απρ",
      "Μάι",
      "Ιούν",
      "Ιούλ",
      "Αύγ",
      "Σεπ",
      "Οκτ",
      "Νοέ",
      "Δεκ",
    ],
    longhand: [
      "Ιανουάριος",
      "Φεβρουάριος",
      "Μάρτιος",
      "Απρίλιος",
      "Μάιος",
      "Ιούνιος",
      "Ιούλιος",
      "Αύγουστος",
      "Σεπτέμβριος",
      "Οκτώβριος",
      "Νοέμβριος",
      "Δεκέμβριος",
    ],
  },

  firstDayOfWeek: 1,

  ordinal: function () {
    return "";
  },

  weekAbbreviation: "Εβδ",
  rangeSeparator: " έως ",
  scrollTitle: "Μετακυλήστε για προσαύξηση",
  toggleTitle: "Κάντε κλικ για αλλαγή",
  amPM: ["ΠΜ", "ΜΜ"],
  yearAriaLabel: "χρόνος",
  monthAriaLabel: "μήνας",
  hourAriaLabel: "ώρα",
  minuteAriaLabel: "λεπτό",
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.gr = locale);
    }
})();