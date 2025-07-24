(function() {
    var locale = {
  weekdays: {
    shorthand: ["Կիր", "Երկ", "Երք", "Չրք", "Հնգ", "Ուրբ", "Շբթ"],
    longhand: [
      "Կիրակի",
      "Եկուշաբթի",
      "Երեքշաբթի",
      "Չորեքշաբթի",
      "Հինգշաբթի",
      "Ուրբաթ",
      "Շաբաթ",
    ],
  },
  months: {
    shorthand: [
      "Հնվ",
      "Փտր",
      "Մար",
      "Ապր",
      "Մայ",
      "Հնս",
      "Հլս",
      "Օգս",
      "Սեպ",
      "Հոկ",
      "Նմբ",
      "Դեկ",
    ],
    longhand: [
      "Հունվար",
      "Փետրվար",
      "Մարտ",
      "Ապրիլ",
      "Մայիս",
      "Հունիս",
      "Հուլիս",
      "Օգոստոս",
      "Սեպտեմբեր",
      "Հոկտեմբեր",
      "Նոյեմբեր",
      "Դեկտեմբեր",
    ],
  },
  firstDayOfWeek: 1,
  ordinal: function () {
    return "";
  },
  rangeSeparator: " — ",
  weekAbbreviation: "ՇԲՏ",
  scrollTitle: "Ոլորեք՝ մեծացնելու համար",
  toggleTitle: "Սեղմեք՝ փոխելու համար",
  amPM: ["ՄԿ", "ԿՀ"],
  yearAriaLabel: "Տարի",
  monthAriaLabel: "Ամիս",
  hourAriaLabel: "Ժամ",
  minuteAriaLabel: "Րոպե",
  time_24hr: true,
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.hy = locale);
    }
})();