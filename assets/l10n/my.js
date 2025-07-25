(function() {
    var locale = {
  weekdays: {
    shorthand: ["နွေ", "လာ", "ဂါ", "ဟူး", "ကြာ", "သော", "နေ"],
    longhand: [
      "တနင်္ဂနွေ",
      "တနင်္လာ",
      "အင်္ဂါ",
      "ဗုဒ္ဓဟူး",
      "ကြာသပတေး",
      "သောကြာ",
      "စနေ",
    ],
  },

  months: {
    shorthand: [
      "ဇန်",
      "ဖေ",
      "မတ်",
      "ပြီ",
      "မေ",
      "ဇွန်",
      "လိုင်",
      "သြ",
      "စက်",
      "အောက်",
      "နို",
      "ဒီ",
    ],
    longhand: [
      "ဇန်နဝါရီ",
      "ဖေဖော်ဝါရီ",
      "မတ်",
      "ဧပြီ",
      "မေ",
      "ဇွန်",
      "ဇူလိုင်",
      "သြဂုတ်",
      "စက်တင်ဘာ",
      "အောက်တိုဘာ",
      "နိုဝင်ဘာ",
      "ဒီဇင်ဘာ",
    ],
  },

  firstDayOfWeek: 1,

  ordinal: () => {
    return "";
  },
  time_24hr: true,
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.my = locale);
    }
})();