(function() {
    var locale = {
  weekdays: {
    shorthand: ["อา", "จ", "อ", "พ", "พฤ", "ศ", "ส"],
    longhand: [
      "อาทิตย์",
      "จันทร์",
      "อังคาร",
      "พุธ",
      "พฤหัสบดี",
      "ศุกร์",
      "เสาร์",
    ],
  },

  months: {
    shorthand: [
      "ม.ค.",
      "ก.พ.",
      "มี.ค.",
      "เม.ย.",
      "พ.ค.",
      "มิ.ย.",
      "ก.ค.",
      "ส.ค.",
      "ก.ย.",
      "ต.ค.",
      "พ.ย.",
      "ธ.ค.",
    ],
    longhand: [
      "มกราคม",
      "กุมภาพันธ์",
      "มีนาคม",
      "เมษายน",
      "พฤษภาคม",
      "มิถุนายน",
      "กรกฎาคม",
      "สิงหาคม",
      "กันยายน",
      "ตุลาคม",
      "พฤศจิกายน",
      "ธันวาคม",
    ],
  },

  firstDayOfWeek: 1,
  rangeSeparator: " ถึง ",
  scrollTitle: "เลื่อนเพื่อเพิ่มหรือลด",
  toggleTitle: "คลิกเพื่อเปลี่ยน",
  time_24hr: true,

  ordinal: () => {
    return "";
  },
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.th = locale);
    }
})();