(function() {
    var locale = {
  weekdays: {
    shorthand: ["კვ", "ორ", "სა", "ოთ", "ხუ", "პა", "შა"],
    longhand: [
      "კვირა",
      "ორშაბათი",
      "სამშაბათი",
      "ოთხშაბათი",
      "ხუთშაბათი",
      "პარასკევი",
      "შაბათი",
    ],
  },
  months: {
    shorthand: [
      "იან",
      "თებ",
      "მარ",
      "აპრ",
      "მაი",
      "ივნ",
      "ივლ",
      "აგვ",
      "სექ",
      "ოქტ",
      "ნოე",
      "დეკ",
    ],
    longhand: [
      "იანვარი",
      "თებერვალი",
      "მარტი",
      "აპრილი",
      "მაისი",
      "ივნისი",
      "ივლისი",
      "აგვისტო",
      "სექტემბერი",
      "ოქტომბერი",
      "ნოემბერი",
      "დეკემბერი",
    ],
  },
  firstDayOfWeek: 1,
  ordinal: function () {
    return "";
  },
  rangeSeparator: " — ",
  weekAbbreviation: "კვ.",
  scrollTitle: "დასქროლეთ გასადიდებლად",
  toggleTitle: "დააკლიკეთ გადართვისთვის",
  amPM: ["AM", "PM"],
  yearAriaLabel: "წელი",
  time_24hr: true,
};
    
    if (typeof window !== "undefined" && window.flatpickr !== undefined) {
        window.flatpickr.localize(window.flatpickr.l10ns.ka = locale);
    }
})();