(function() {
    var locale = {
  weekdays: {
    shorthand: ["یک", "دو", "سه", "چهار", "پنج", "جمعه", "شنبه"],
    longhand: [
      "یک‌شنبه",
      "دوشنبه",
      "سه‌شنبه",
      "چهارشنبه",
      "پنچ‌شنبه",
      "جمعه",
      "شنبه",
    ],
  },

  months: {
    shorthand: [
      "ژانویه",
      "فوریه",
      "مارس",
      "آوریل",
      "مه",
      "ژوئن",
      "ژوئیه",
      "اوت",
      "سپتامبر",
      "اکتبر",
      "نوامبر",
      "دسامبر",
    ],
    longhand: [
      "ژانویه",
      "فوریه",
      "مارس",
      "آوریل",
      "مه",
      "ژوئن",
      "ژوئیه",
      "اوت",
      "سپتامبر",
      "اکتبر",
      "نوامبر",
      "دسامبر",
    ],
  },
  firstDayOfWeek: 6,
  ordinal: () => {
    return "";
  },
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.fa = locale);
    }
})();