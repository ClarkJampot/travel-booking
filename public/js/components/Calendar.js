/**
 * Calendar Component - Displays a month view calendar with availability indicators
 */
class Calendar {
  /**
   * @param {object} options - Calendar options
   * @param {string|HTMLElement} options.container - Container selector or element
   * @param {function} options.onDateSelect - Callback when date is selected
   * @param {function} options.onRangeSelect - Callback when date range is selected
   * @param {string[]} options.availableDates - Array of available dates (YYYY-MM-DD)
   * @param {string[]} options.returnAvailableDates - Array of return available dates
   * @param {Date} options.minDate - Minimum selectable date
   * @param {Date} options.maxDate - Maximum selectable date
   * @param {Date} options.initialMonth - Initial month to display
   * @param {boolean} options.rangeMode - Enable date range selection
   */
  constructor(options = {}) {
    this.container = typeof options.container === 'string' 
      ? document.querySelector(options.container) 
      : options.container;
    this.onDateSelect = options.onDateSelect || (() => {});
    this.onRangeSelect = options.onRangeSelect || (() => {});
    this.availableDates = options.availableDates || [];
    this.returnAvailableDates = options.returnAvailableDates || [];
    this.minDate = options.minDate || new Date();
    this.maxDate = options.maxDate || null;
    this.selectedDate = null;
    this.departureDate = null;
    this.returnDate = null;
    this.rangeMode = options.rangeMode || false;
    this.currentMonth = options.initialMonth || new Date();
    
    if (!this.container) {
      console.error('Calendar: Container element not found');
      return;
    }
    
    this.render();
  }
  
  // Helper to format date as YYYY-MM-DD using local time (not UTC)
  formatDateLocal(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }
  
  setAvailableDates(dates) {
    this.availableDates = dates.map(date => {
      // Normalize dates to YYYY-MM-DD format using local time
      if (date instanceof Date) {
        return this.formatDateLocal(date);
      }
      return date.split('T')[0];
    });
    this.render();
  }
  
  setReturnAvailableDates(dates) {
    this.returnAvailableDates = dates.map(date => {
      // Normalize dates to YYYY-MM-DD format using local time
      if (date instanceof Date) {
        return this.formatDateLocal(date);
      }
      return date.split('T')[0];
    });
    this.render();
  }
  
  setRangeMode(enabled) {
    this.rangeMode = enabled;
    if (!enabled) {
      this.departureDate = null;
      this.returnDate = null;
    }
    this.render();
  }
  
  render() {
    const year = this.currentMonth.getFullYear();
    const month = this.currentMonth.getMonth();
    
    // Get first day of month and number of days
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay();
    
    // Get previous month's trailing days
    const prevMonth = new Date(year, month, 0);
    const daysInPrevMonth = prevMonth.getDate();
    
    // Build calendar HTML
    const showClearButton = this.rangeMode && this.departureDate && this.returnDate;
    const disableNavigation = this.rangeMode && this.departureDate && this.returnDate;
    let html = `
      <div class="calendar-container">
        <div class="calendar-header">
          <button class="calendar-nav-btn ${disableNavigation ? 'calendar-nav-btn-disabled' : ''}" 
                  data-action="prev" 
                  aria-label="Previous month"
                  ${disableNavigation ? 'disabled' : ''}>
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M10 12L6 8L10 4"/>
            </svg>
          </button>
          <div class="calendar-month-year">
            <span class="calendar-month">${this.getMonthName(month)}</span>
            <span class="calendar-year">${year}</span>
          </div>
          <div style="display: flex; gap: 8px; align-items: center;">
            ${showClearButton ? `
              <button class="calendar-clear-btn" data-action="clear" aria-label="Clear selection" title="Clear selection">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M4 4L12 12M12 4L4 12"/>
                </svg>
              </button>
            ` : ''}
            <button class="calendar-nav-btn ${disableNavigation ? 'calendar-nav-btn-disabled' : ''}" 
                    data-action="next" 
                    aria-label="Next month"
                    ${disableNavigation ? 'disabled' : ''}>
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 4L10 8L6 12"/>
              </svg>
            </button>
          </div>
        </div>
        <div class="calendar-weekdays">
          ${['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map(day => 
            `<div class="calendar-weekday">${day}</div>`
          ).join('')}
        </div>
        <div class="calendar-days">
    `;
    
    // Add previous month's trailing days
    for (let i = startingDayOfWeek - 1; i >= 0; i--) {
      const day = daysInPrevMonth - i;
      const date = new Date(year, month - 1, day);
      html += this.renderDay(date, true);
    }
    
    // Add current month's days
    for (let day = 1; day <= daysInMonth; day++) {
      const date = new Date(year, month, day);
      html += this.renderDay(date, false);
    }
    
    // Add next month's leading days to fill the grid
    const totalCells = startingDayOfWeek + daysInMonth;
    const remainingCells = 42 - totalCells; // 6 rows * 7 days
    for (let day = 1; day <= remainingCells && day <= 14; day++) {
      const date = new Date(year, month + 1, day);
      html += this.renderDay(date, true);
    }
    
    html += `
        </div>
      </div>
    `;
    
    this.container.innerHTML = html;
    this.attachEventListeners();
  }
  
  renderDay(date, isAdjacentMonth) {
    const dateStr = this.formatDateLocal(date);
    const day = date.getDate();
    const today = new Date();
    const todayStr = this.formatDateLocal(today);
    const isToday = dateStr === todayStr;
    const isPast = date < new Date(today.getFullYear(), today.getMonth(), today.getDate());
    
    // Determine availability based on mode
    let isAvailable = false;
    if (this.rangeMode && this.departureDate && !this.returnDate) {
      // In range mode with only departure selected, check return availability
      // Use returnAvailableDates if set, otherwise fall back to availableDates
      const returnDates = this.returnAvailableDates.length > 0 ? this.returnAvailableDates : this.availableDates;
      // Compare date strings directly to avoid timezone issues
      isAvailable = returnDates.includes(dateStr) && dateStr > this.departureDate;
    } else if (this.rangeMode && this.departureDate && this.returnDate) {
      // Both dates selected - allow clicking on any available date to reset
      // Dates before departure should be available for resetting
      isAvailable = this.availableDates.includes(dateStr);
    } else {
      // Normal mode or no departure selected, check departure availability
      isAvailable = this.availableDates.includes(dateStr);
    }
    
    // Range selection logic - compare date strings directly
    const isDeparture = this.departureDate === dateStr;
    const isReturn = this.returnDate === dateStr;
    const isInRange = this.rangeMode && this.departureDate && this.returnDate && 
                      dateStr > this.departureDate && dateStr < this.returnDate;
    
    const isSelected = this.rangeMode ? (isDeparture || isReturn) : (this.selectedDate === dateStr);
    // Compare date strings directly to avoid timezone issues
    // Disable all dates when both dates are selected (user must use clear button)
    // Only disable dates before departure when ONLY departure is selected (not when both are selected)
    const isDisabled = isPast || !isAvailable || isAdjacentMonth || 
                      (this.rangeMode && this.departureDate && this.returnDate) ||
                      (this.rangeMode && this.departureDate && !this.returnDate && dateStr <= this.departureDate);
    
    const classes = [
      'calendar-day',
      isAdjacentMonth ? 'calendar-day-adjacent' : '',
      isToday ? 'calendar-day-today' : '',
      isPast ? 'calendar-day-past' : '',
      isAvailable ? 'calendar-day-available' : '',
      isSelected ? 'calendar-day-selected' : '',
      isDisabled ? 'calendar-day-disabled' : '',
      isDeparture ? 'calendar-day-departure' : '',
      isReturn ? 'calendar-day-return' : '',
      isInRange ? 'calendar-day-in-range' : ''
    ].filter(Boolean).join(' ');
    
    return `
      <div class="${classes}" data-date="${dateStr}" ${isDisabled ? '' : 'tabindex="0" role="button" aria-label="Select date ${dateStr}"'}>
        <span class="calendar-day-number">${day}</span>
        ${isAvailable && !isAdjacentMonth ? '<span class="calendar-day-indicator"></span>' : ''}
      </div>
    `;
  }
  
  getMonthName(monthIndex) {
    const months = ['January', 'February', 'March', 'April', 'May', 'June',
      'July', 'August', 'September', 'October', 'November', 'December'];
    return months[monthIndex];
  }
  
  clearSelection() {
    this.departureDate = null;
    this.returnDate = null;
    this.selectedDate = null;
    this.render();
  }
  
  attachEventListeners() {
    // Navigation buttons
    this.container.querySelectorAll('.calendar-nav-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        // Don't allow navigation when both dates are selected
        if (this.rangeMode && this.departureDate && this.returnDate) {
          e.preventDefault();
          e.stopPropagation();
          return;
        }
        const action = btn.dataset.action;
        if (action === 'prev') {
          this.currentMonth = new Date(this.currentMonth.getFullYear(), this.currentMonth.getMonth() - 1, 1);
        } else if (action === 'next') {
          this.currentMonth = new Date(this.currentMonth.getFullYear(), this.currentMonth.getMonth() + 1, 1);
        }
        this.render();
      });
    });
    
    // Clear button
    this.container.querySelectorAll('.calendar-clear-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        this.clearSelection();
      });
    });
    
    // Day clicks
    this.container.querySelectorAll('.calendar-day:not(.calendar-day-disabled)').forEach(dayEl => {
      dayEl.addEventListener('click', (e) => {
        const dateStr = dayEl.dataset.date;
        if (!dateStr) return;
        
        if (this.rangeMode) {
          // Range selection mode
          if (!this.departureDate) {
            // First click - select departure
            this.departureDate = dateStr;
            this.selectedDate = dateStr;
            this.render();
            if (this.onDateSelect) {
              this.onDateSelect(dateStr);
            }
          } else if (!this.returnDate) {
            // Second click - select return (must be after departure)
            // Use returnAvailableDates if set, otherwise fall back to availableDates
            const returnDates = this.returnAvailableDates.length > 0 ? this.returnAvailableDates : this.availableDates;
            // Compare date strings directly to avoid timezone issues
            if (dateStr > this.departureDate && returnDates.includes(dateStr)) {
              this.returnDate = dateStr;
              this.selectedDate = dateStr;
              this.render();
              if (this.onRangeSelect) {
                this.onRangeSelect(this.departureDate, this.returnDate);
              }
            } else if (dateStr === this.departureDate) {
              // Clicking same date resets
              this.departureDate = null;
              this.returnDate = null;
              this.selectedDate = null;
              this.render();
            } else if (dateStr <= this.departureDate) {
              // Clicking before or on departure - reset and set new departure
              this.departureDate = dateStr;
              this.returnDate = null;
              this.selectedDate = dateStr;
              this.render();
              if (this.onDateSelect) {
                this.onDateSelect(dateStr);
              }
            }
          } else {
            // Both dates selected - disable clicking, user must use clear button
            // Do nothing when both dates are selected
            return;
          }
        } else {
          // Single date selection mode
          if (dateStr && this.availableDates.includes(dateStr)) {
            // Remove previous selection
            this.container.querySelectorAll('.calendar-day-selected').forEach(el => {
              el.classList.remove('calendar-day-selected');
            });
            
            // Add selection to clicked day
            dayEl.classList.add('calendar-day-selected');
            this.selectedDate = dateStr;
            
            // Call callback
            this.onDateSelect(dateStr);
          }
        }
      });
      
      // Keyboard support
      dayEl.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          dayEl.click();
        }
      });
    });
  }
  
  selectDate(dateStr) {
    // Normalize date string
    const normalized = dateStr.split('T')[0];
    
    if (this.availableDates.includes(normalized)) {
      // Update selected date
      this.selectedDate = normalized;
      
      // Update calendar view if needed
      const date = new Date(normalized);
      if (date.getMonth() !== this.currentMonth.getMonth() || 
          date.getFullYear() !== this.currentMonth.getFullYear()) {
        this.currentMonth = new Date(date.getFullYear(), date.getMonth(), 1);
      }
      
      this.render();
      
      // Call callback
      this.onDateSelect(normalized);
    }
  }
  
  getSelectedDate() {
    return this.selectedDate;
  }
  
  getDepartureDate() {
    return this.departureDate;
  }
  
  getReturnDate() {
    return this.returnDate;
  }
  
  setDepartureDate(dateStr) {
    this.departureDate = dateStr ? dateStr.split('T')[0] : null;
    this.selectedDate = this.departureDate;
    this.render();
  }
  
  setReturnDate(dateStr) {
    this.returnDate = dateStr ? dateStr.split('T')[0] : null;
    this.render();
  }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
  module.exports = Calendar;
}

