(function() {
    var locale = {
  weekdays: {
    shorthand: ["रवि", "सोम", "मंगल", "बुध", "गुरु", "शुक्र", "शनि"],
    longhand: [
      "रविवार",
      "सोमवार",
      "मंगलवार",
      "बुधवार",
      "गुरुवार",
      "शुक्रवार",
      "शनिवार",
    ],
  },

  months: {
    shorthand: [
      "जन",
      "फर",
      "मार्च",
      "अप्रेल",
      "मई",
      "जून",
      "जूलाई",
      "अग",
      "सित",
      "अक्ट",
      "नव",
      "दि",
    ],
    longhand: [
      "जनवरी ",
      "फरवरी",
      "मार्च",
      "अप्रेल",
      "मई",
      "जून",
      "जूलाई",
      "अगस्त ",
      "सितम्बर",
      "अक्टूबर",
      "नवम्बर",
      "दिसम्बर",
    ],
  },
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.hi = locale);
    }
})();