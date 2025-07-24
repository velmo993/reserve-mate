(function() {
    var locale = {
  weekdays: {
    shorthand: ["أحد", "اثنين", "ثلاثاء", "أربعاء", "خميس", "جمعة", "سبت"],
    longhand: [
      "الأحد",
      "الاثنين",
      "الثلاثاء",
      "الأربعاء",
      "الخميس",
      "الجمعة",
      "السبت",
    ],
  },

  months: {
    shorthand: ["1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12"],
    longhand: [
      "يناير",
      "فبراير",
      "مارس",
      "أبريل",
      "مايو",
      "يونيو",
      "يوليو",
      "أغسطس",
      "سبتمبر",
      "أكتوبر",
      "نوفمبر",
      "ديسمبر",
    ],
  },
  firstDayOfWeek: 6,
  rangeSeparator: " إلى ",
  weekAbbreviation: "Wk",
  scrollTitle: "قم بالتمرير للزيادة",
  toggleTitle: "اضغط للتبديل",
  amPM: ["ص", "م"],
  yearAriaLabel: "سنة",
  monthAriaLabel: "شهر",
  hourAriaLabel: "ساعة",
  minuteAriaLabel: "دقيقة",
  time_24hr: false,
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.ar = locale);
    }
})();