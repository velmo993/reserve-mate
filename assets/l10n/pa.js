(function() {
    var locale = {
  weekdays: {
    shorthand: ["ਐਤ", "ਸੋਮ", "ਮੰਗਲ", "ਬੁੱਧ", "ਵੀਰ", "ਸ਼ੁੱਕਰ", "ਸ਼ਨਿੱਚਰ"],
    longhand: [
      "ਐਤਵਾਰ",
      "ਸੋਮਵਾਰ",
      "ਮੰਗਲਵਾਰ",
      "ਬੁੱਧਵਾਰ",
      "ਵੀਰਵਾਰ",
      "ਸ਼ੁੱਕਰਵਾਰ",
      "ਸ਼ਨਿੱਚਰਵਾਰ",
    ],
  },

  months: {
    shorthand: [
      "ਜਨ",
      "ਫ਼ਰ",
      "ਮਾਰ",
      "ਅਪ੍ਰੈ",
      "ਮਈ",
      "ਜੂਨ",
      "ਜੁਲਾ",
      "ਅਗ",
      "ਸਤੰ",
      "ਅਕ",
      "ਨਵੰ",
      "ਦਸੰ",
    ],
    longhand: [
      "ਜਨਵਰੀ",
      "ਫ਼ਰਵਰੀ",
      "ਮਾਰਚ",
      "ਅਪ੍ਰੈਲ",
      "ਮਈ",
      "ਜੂਨ",
      "ਜੁਲਾਈ",
      "ਅਗਸਤ",
      "ਸਤੰਬਰ",
      "ਅਕਤੂਬਰ",
      "ਨਵੰਬਰ",
      "ਦਸੰਬਰ",
    ],
  },
  time_24hr: true,
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.pa = locale);
    }
})();