(function() {
    var locale = {
  weekdays: {
    shorthand: ["Aha", "Isn", "Sel", "Rab", "Kha", "Jum", "Sab"],
    longhand: ["Ahad", "Isnin", "Selasa", "Rabu", "Khamis", "Jumaat", "Sabtu"],
  },

  months: {
    shorthand: [
      "Jan",
      "Feb",
      "Mac",
      "Apr",
      "Mei",
      "Jun",
      "Jul",
      "Ogo",
      "Sep",
      "Okt",
      "Nov",
      "Dis",
    ],
    longhand: [
      "Januari",
      "Februari",
      "Mac",
      "April",
      "Mei",
      "Jun",
      "Julai",
      "Ogos",
      "September",
      "Oktober",
      "November",
      "Disember",
    ],
  },

  firstDayOfWeek: 1,

  ordinal: () => {
    return "";
  },
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.ms = locale);
    }
})();