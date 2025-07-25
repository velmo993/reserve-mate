(function() {
    var locale = {
  weekdays: {
    shorthand: ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"],
    longhand: ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"],
  },

  months: {
    shorthand: [
      "Jan",
      "Feb",
      "Mar",
      "Apr",
      "Mei",
      "Jun",
      "Jul",
      "Agu",
      "Sep",
      "Okt",
      "Nov",
      "Des",
    ],
    longhand: [
      "Januari",
      "Februari",
      "Maret",
      "April",
      "Mei",
      "Juni",
      "Juli",
      "Agustus",
      "September",
      "Oktober",
      "November",
      "Desember",
    ],
  },

  firstDayOfWeek: 1,

  ordinal: () => {
    return "";
  },
  time_24hr: true,
  rangeSeparator: " - ",
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.id = locale);
    }
})();