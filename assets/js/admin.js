document.addEventListener('DOMContentLoaded', function() {
    let tabButtons = document.querySelectorAll('.tab-button');
    let tabContents = document.querySelectorAll('.tab-content');
    let calendarCheckBox = document.getElementById('save_to_google_calendar');

    // Manage Bookings Page JS -- Separate these in the future
    if (window.location.search.includes('page=manage-bookings')) {
        const activeTab = localStorage.getItem('activeTab');
        const propertySelect = document.getElementById("property_id");
        if (activeTab) {
            document.querySelectorAll('.tab-button, .tab-content').forEach(function(elem) {
                elem.classList.remove('active');
            });
            document.querySelector(activeTab).classList.add('active');
            document.querySelector(`.tab-button[data-target="${activeTab}"]`).classList.add('active');
        }
    
        document.querySelectorAll('.tab-button').forEach(function(button) {
            button.addEventListener('click', function() {
                document.querySelectorAll('.tab-button, .tab-content').forEach(function(elem) {
                    elem.classList.remove('active');
                });
    
                this.classList.add('active');
                const target = this.getAttribute('data-target');
                document.querySelector(target).classList.add('active');
    
                localStorage.setItem('activeTab', target);
            });
        });
        
        updatePropertyFields(propertySelect);
        setupToggleDetails('.toggle-details-booking', 'booking');
        propertySelect.addEventListener("change", () => {
            updatePropertyFields(propertySelect);
        });
    }
    
    function updatePropertyFields(propertySelect) {
        const selectedOption = propertySelect.options[propertySelect.selectedIndex];
        const adultsSelect = document.getElementById("adults");
        const childrenSelect = document.getElementById("children");
        const petsSelect = document.getElementById("pets");
        const childrenRow = document.querySelector(".children-row");
        const petsRow = document.querySelector(".pets-row");
        if(selectedOption) {
            const maxAdults = parseInt(selectedOption.getAttribute("data-max-adults"), 10) || 1;
            const maxChildren = parseInt(selectedOption.getAttribute("data-max-children"), 10) || 0;
            const maxPets = parseInt(selectedOption.getAttribute("data-max-pets"), 10) || 0;
            const allowChildren = selectedOption.getAttribute("data-allow-children") === "1";
            const allowPets = selectedOption.getAttribute("data-allow-pets") === "1";
        
            // Update Adults Dropdown
            adultsSelect.innerHTML = "";
            for (let i = 1; i <= maxAdults; i++) {
                adultsSelect.innerHTML += `<option value="${i}">${i}</option>`;
            }
    
            // Update Children Dropdown
            childrenSelect.innerHTML = "";
            if (allowChildren) {
                childrenRow.style.display = "";
                for (let i = 0; i <= maxChildren; i++) {
                    childrenSelect.innerHTML += `<option value="${i}">${i}</option>`;
                }
            } else {
                childrenRow.style.display = "none";
            }
    
            // Update Pets Dropdown
            petsSelect.innerHTML = "";
            if (allowPets) {
                petsRow.style.display = "";
                for (let i = 0; i <= maxPets; i++) {
                    petsSelect.innerHTML += `<option value="${i}">${i}</option>`;
                }
            } else {
                petsRow.style.display = "none";
            }
        }
        
    }
    
    function setupToggleDetails(toggleClass, dataIdPrefix) {
        const toggleButtons = document.querySelectorAll(toggleClass);
    
        if (toggleButtons) {
            toggleButtons.forEach(button => {
                const toggleDetails = function(event) {
                    if (event.type === 'touchstart') {
                        event.preventDefault();
                    }
    
                    const entityId = this.getAttribute(`data-${dataIdPrefix}-id`);
                    const detailsRow = document.getElementById('details-' + entityId);
                    const isVisible = detailsRow.style.display === 'table-row';
    
                    detailsRow.style.display = isVisible ? 'none' : 'table-row';
                    this.innerHTML = isVisible ? '<i>▼</i>' : '<i>▲</i>';
                };
    
                button.addEventListener('click', toggleDetails);
                button.addEventListener('touchstart', toggleDetails);
            });
        }
    }
    
    tabButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            tabButtons.forEach(function(btn) { btn.classList.remove('active'); });
            tabContents.forEach(function(content) { content.classList.remove('active'); });

            button.classList.add('active');

            var target = document.querySelector(button.getAttribute('data-target'));
            target.classList.add('active');
        });
    });
    
    function toggleCalendarSettings() {
        const checkbox = document.getElementById('save_to_google_calendar');
        const fields = [
            'calendar_api_key',
            'calendar_id',
            'calendar_timezones'
        ];
    
        fields.forEach(function(fieldId) {
            let field = document.querySelector(`[name="booking_settings[${fieldId}]"]`);
            if (field) {
                field.disabled = !checkbox.checked;
            }
        });
    }
    
    if(calendarCheckBox) {
        calendarCheckBox.addEventListener("click" , (e) => {
            e.preventDefault();
            toggleCalendarSettings();
        });
    }
    
    
    // Manage Properties Page JS -- Separate these in the future
    if (window.location.search.includes('page=manage-properties')) {
        const allowChildren = document.getElementById("allow_children");
        const allowPets = document.getElementById("allow_pets");
        const seasonalRulesBtn = document.getElementById("seasonal-rules-btn");
        const seasonalRulesContainer = document.querySelectorAll(".seasonal-rules-container");
        
        allowChildren.addEventListener("change", () => {
            toggleFields(allowChildren, allowPets);
        });
        allowPets.addEventListener("change", () => {
            toggleFields(allowChildren, allowPets);
        });
        seasonalRulesBtn.addEventListener("click", (e) => {
            e.preventDefault();
            toggleSeasonalRules(seasonalRulesContainer, seasonalRulesBtn);
        });
        
        setupToggleDetails('.toggle-details-property', 'property');
        
    }
    
    function toggleSeasonalRules(seasonalRulesContainer, seasonalRulesBtn) {
        seasonalRulesContainer.forEach(el => {
            if(el.classList.contains('hidden')) {
                el.classList.remove('hidden');
                seasonalRulesBtn.innerHTML = '<i>▲</i>';                
            } else {
                el.classList.add('hidden');
                seasonalRulesBtn.innerHTML = '<i>▼</i>';
            }
        });
    }
    
    function toggleFields(allowChildren, allowPets) {
        document.querySelectorAll(".children-field").forEach(el => {
            allowChildren.checked ? el.classList.remove('hidden') : el.classList.add('hidden');
        });

        document.querySelectorAll(".pets-field").forEach(el => {
            allowPets.checked ? el.classList.remove('hidden') : el.classList.add('hidden');
        });
    }
    
});