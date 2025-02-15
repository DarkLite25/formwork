import Icons from '../icons';
import Utils from '../utils';

export default function DateInput(input, options) {
    var defaults = {
        weekStarts: 0,
        format: 'YYYY-MM-DD',
        time: false,
        labels: {
            today: 'Today',
            weekdays: {
                long: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                short: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
            },
            months: {
                long: ['January', 'February', 'March', 'April', 'May', 'June', 'July' ,'August', 'September', 'October', 'November', 'December'],
                short: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
            }
        }
    };

    var today = new Date();
    var dateKeeper, dateHelpers, calendar;

    options = Utils.extendObject({}, defaults, options);

    dateKeeper = {
        year: today.getFullYear(),
        month: today.getMonth(),
        day: today.getDate(),
        hours: today.getHours(),
        minutes: today.getMinutes(),
        seconds: today.getSeconds(),
        setDate: function (date) {
            this.year = date.getFullYear();
            this.month = date.getMonth();
            this.day = date.getDate();
            this.hours = date.getHours();
            this.minutes = date.getMinutes();
            this.seconds = date.getSeconds();
        },
        lastDay: function () {
            this.day = dateHelpers.daysInMonth(this.month, this.year);
        },
        prevYear: function () {
            this.year--;
        },
        nextYear: function () {
            this.year++;
        },
        prevMonth: function () {
            this.month = dateHelpers.mod(this.month - 1, 12);
            if (this.month === 11) {
                this.prevYear();
            }
            if (this.day > dateHelpers.daysInMonth(this.month, this.year)) {
                this.lastDay();
            }
        },
        nextMonth: function () {
            this.month = dateHelpers.mod(this.month + 1, 12);
            if (this.month === 0) {
                this.nextYear();
            }
            if (this.day > dateHelpers.daysInMonth(this.month, this.year)) {
                this.lastDay();
            }
        },
        prevWeek: function () {
            this.day -= 7;
            if (this.day < 1) {
                this.prevMonth();
                this.day += dateHelpers.daysInMonth(this.month, this.year);
            }
        },
        nextWeek: function () {
            this.day += 7;
            if (this.day > dateHelpers.daysInMonth(this.month, this.year)) {
                this.day -= dateHelpers.daysInMonth(this.month, this.year);
                this.nextMonth();
            }
        },
        prevDay: function () {
            this.day--;
            if (this.day < 1) {
                this.prevMonth();
                this.lastDay();
            }
        },
        nextDay: function () {
            this.day++;
            if (this.day > dateHelpers.daysInMonth(this.month, this.year)) {
                this.nextMonth();
                this.day = 1;
            }
        },
        nextHour: function () {
            this.hours = dateHelpers.mod(this.hours + 1, 24);
            if (this.hours === 0) {
                this.nextDay();
            }
        },
        prevHour: function () {
            this.hours = dateHelpers.mod(this.hours - 1, 24);
            if (this.hours === 23) {
                this.prevDay();
            }
        },
        nextMinute: function () {
            this.minutes = dateHelpers.mod(this.minutes + 1, 60);
            if (this.minutes === 0) {
                this.nextHour();
            }
        },
        prevMinute: function () {
            this.minutes = dateHelpers.mod(this.minutes - 1, 60);
            if (this.minutes === 59) {
                this.prevHour();
            }
        },
        nextSecond: function () {
            this.seconds = dateHelpers.mod(this.seconds + 1, 60);
            if (this.seconds === 0) {
                this.nextMinute();
            }
        },
        prevSecond: function () {
            this.seconds = dateHelpers.mod(this.seconds - 1, 60);
            if (this.minutes === 59) {
                this.prevMinute();
            }
        }
    };

    dateHelpers = {
        _daysInMonth: [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31],
        mod: function (x, y) {
            // Return x mod y (always rounded downwards, differs from x % y which is the remainder)
            return x - y * Math.floor(x / y);
        },
        pad: function (num, length) {
            var result = num.toString();
            while (result.length < length) {
                result = '0' + result;
            }
            return result;
        },
        isValidDate: function (date) {
            return date && !isNaN(Date.parse(date));
        },
        isLeapYear: function (year) {
            return (year % 4 === 0 && year % 100 !== 0) || year % 400 === 0;
        },
        daysInMonth: function (month, year) {
            return month === 1 && this.isLeapYear(year) ? 29 : this._daysInMonth[month];
        },
        weekStart: function (date, firstDay) {
            var day = date.getDate();
            if (typeof firstDay === 'undefined') {
                firstDay = options.weekStarts;
            }
            day -= this.mod(date.getDay() - firstDay, 7);
            return new Date(date.getFullYear(), date.getMonth(), day);
        },
        weekNumberingYear: function (date) {
            var year = date.getFullYear();
            var thisYearFirstWeekStart = this.weekStart(new Date(year, 0, 4), 1);
            var nextYearFirstWeekStart = this.weekStart(new Date(year + 1, 0, 4), 1);
            if (date.getTime() >= nextYearFirstWeekStart.getTime()) {
                return year + 1;
            } else if (date.getTime() >= thisYearFirstWeekStart.getTime()) {
                return year;
            }
            return year - 1;
        },
        weekOfYear: function (date) {
            var weekNumberingYear = this.weekNumberingYear(date);
            var firstWeekStart = this.weekStart(new Date(weekNumberingYear, 0, 4), 1);
            var weekStart = this.weekStart(date, 1);
            return Math.round((weekStart.getTime() - firstWeekStart.getTime()) / 604800000) + 1;
        },
        has12HourFormat: function (format) {
            var match = format.match(/\[([^\]]*)\]|H{1,2}/);
            return match !== null && match[0][0] === 'H';
        },
        formatDateTime: function (date, format) {
            var regex = /\[([^\]]*)\]|[YR]{4}|uuu|[YR]{2}|[MD]{1,4}|[WHhms]{1,2}|[AaZz]/g;
            var self = this;

            if (typeof format === 'undefined') {
                format = options.format;
            }

            function splitTimezoneOffset(offset) {
                // Note that the offset returned by Date.getTimezoneOffset()
                // is positive if behind UTC and negative if ahead UTC
                var sign = offset > 0 ? '-' : '+';
                var hours = Math.floor(Math.abs(offset) / 60);
                var minutes = Math.abs(offset) % 60;
                return [sign + dateHelpers.pad(hours, 2), dateHelpers.pad(minutes, 2)];
            }

            return format.replace(regex, function (match, $1) {
                switch (match) {
                case 'YY':
                    return date.getFullYear().toString().substr(-2);
                case 'YYYY':
                    return date.getFullYear();
                case 'M':
                    return date.getMonth() + 1;
                case 'MM':
                    return self.pad(date.getMonth() + 1, 2);
                case 'MMM':
                    return options.labels.months.short[date.getMonth()];
                case 'MMMM':
                    return options.labels.months.long[date.getMonth()];
                case 'D':
                    return date.getDate();
                case 'DD':
                    return self.pad(date.getDate(), 2);
                case 'DDD':
                    return options.labels.weekdays.short[self.mod(date.getDay() + options.weekStarts, 7)];
                case 'DDDD':
                    return options.labels.weekdays.long[self.mod(date.getDay() + options.weekStarts, 7)];
                case 'W':
                    return self.weekOfYear(date);
                case 'WW':
                    return self.pad(self.weekOfYear(date), 2);
                case 'RR':
                    return self.weekNumberingYear(date).toString().substr(-2);
                case 'RRRR':
                    return self.weekNumberingYear(date);
                case 'H':
                    return self.mod(date.getHours(), 12) || 12;
                case 'HH':
                    return self.pad(self.mod(date.getHours(), 12) || 12, 2);
                case 'h':
                    return date.getHours();
                case 'hh':
                    return self.pad(date.getHours(), 2);
                case 'm':
                    return date.getMinutes();
                case 'mm':
                    return self.pad(date.getMinutes(), 2);
                case 's':
                    return date.getSeconds();
                case 'ss':
                    return self.pad(date.getSeconds(), 2);
                case 'uuu':
                    return self.pad(date.getMilliseconds(), 3);
                case 'A':
                    return date.getHours() < 12 ? 'AM' : 'PM';
                case 'a':
                    return date.getHours() < 12 ? 'am' : 'pm';
                case 'Z':
                    return splitTimezoneOffset(date.getTimezoneOffset()).join(':');
                case 'z':
                    return splitTimezoneOffset(date.getTimezoneOffset()).join('');
                default:
                    return $1 || match;
                }
            });
        }
    };

    calendar = $('.calendar') ? $('.calendar') : generateCalendar();

    initInput();

    function initInput() {
        var value = input.value;
        input.readOnly = true;
        input.size = options.format.length;
        if (dateHelpers.isValidDate(value)) {
            value = new Date(value);
            input.setAttribute('data-date', value);
            input.value = dateHelpers.formatDateTime(value);
        }
        input.addEventListener('change', function () {
            if (this.value === '') {
                this.setAttribute('data-date', '');
            } else {
                this.value = dateHelpers.formatDateTime(this.getAttribute('data-date'));
            }
        });
        input.addEventListener('keydown', function (event) {
            var date = this.getAttribute('data-date');
            dateKeeper.setDate(dateHelpers.isValidDate(date) ? new Date(date) : new Date());
            switch (event.which) {
            case 13: // enter
                $('.calendar-day.selected', calendar).click();
                calendar.style.display = 'none';
                break;
            case 8: // backspace
                this.value = '';
                this.blur();
                calendar.style.display = 'none';
                break;
            case 27: // escape
                this.blur();
                calendar.style.display = 'none';
                break;
            case 37: // left arrow
                if (event.ctrlKey || event.metaKey) {
                    if (event.shiftKey) {
                        dateKeeper.prevYear();
                    } else {
                        dateKeeper.prevMonth();
                    }
                } else {
                    dateKeeper.prevDay();
                }
                updateInput(this);
                break;
            case 38: // up arrow
                dateKeeper.prevWeek();
                updateInput(this);
                break;
            case 39: // right arrow
                if (event.ctrlKey || event.metaKey) {
                    if (event.shiftKey) {
                        dateKeeper.nextYear();
                    } else {
                        dateKeeper.nextMonth();
                    }
                } else {
                    dateKeeper.nextDay();
                }
                updateInput(this);
                break;
            case 40: // down arrow
                dateKeeper.nextWeek();
                updateInput(this);
                break;
            case 48: // 0
                if (event.ctrlKey || event.metaKey) {
                    dateKeeper.setDate(new Date());
                }
                updateInput(this);
                break;
            default:
                return;
            }
            event.stopPropagation();
            event.preventDefault();
        });

        input.addEventListener('focus', function () {
            var date = dateHelpers.isValidDate(this.getAttribute('data-date')) ? new Date(this.getAttribute('data-date')) : new Date();
            dateKeeper.setDate(date);
            generateCalendarTable(dateKeeper.year, dateKeeper.month, dateKeeper.day);
            updateCalendarTime(dateKeeper.hours, dateKeeper.minutes);
            calendar.style.display = 'block';
            setCalendarPosition();
        });

        input.addEventListener('blur', function () {
            calendar.style.display = 'none';
        });
    }

    function updateInput(input) {
        var date = new Date(dateKeeper.year, dateKeeper.month, dateKeeper.day, dateKeeper.hours, dateKeeper.minutes);
        generateCalendarTable(dateKeeper.year, dateKeeper.month, dateKeeper.day);
        updateCalendarTime(dateKeeper.hours, dateKeeper.minutes);
        input.value = dateHelpers.formatDateTime(date);
        input.setAttribute('data-date', date);
    }

    function getCurrentInput() {
        return document.activeElement.classList.contains('input-date') ? document.activeElement : null;
    }

    function updateCalendarTime(hours, minutes) {
        var meridiem = '';
        var timeFormat = dateHelpers.has12HourFormat(options.format) ? 12 : 24;

        if (!options.time) {
            return;
        }

        if (timeFormat === 12) {
            meridiem = hours < 12 ? 'AM' : 'PM';
        }

        hours = timeFormat === 12 ? (dateHelpers.mod(hours, 12) || 12) : hours;

        $('.calendar-hours', calendar).innerHTML = dateHelpers.pad(hours, 2);
        $('.calendar-minutes', calendar).innerHTML = dateHelpers.pad(minutes, 2);
        $('.calendar-meridiem', calendar).innerHTML = meridiem;
    }

    function generateCalendar() {
        calendar = document.createElement('div');
        calendar.className = 'calendar';
        calendar.innerHTML = '<div class="calendar-buttons"><button type="button" class="prevMonth"></button><button class="currentMonth">' + options.labels.today + '</button><button type="button" class="nextMonth"></button></div><div class="calendar-separator"></div><table class="calendar-table"></table>';

        if (options.time === true) {
            calendar.innerHTML += '<div class="calendar-separator"></div><table class="calendar-time"><tr><td><button type="button" class="nextHour"></button></td><td></td><td><button type="button" class="nextMinute"></button></td></tr><tr><td class="calendar-hours"></td><td>:</td><td class="calendar-minutes"></td><td class="calendar-meridiem"></td></tr><tr><td><button type="button" class="prevHour"></button></td><td></td><td><button type="button" class="prevMinute"></button></td></tr></table></div>';

            Icons.inject('chevron-down', $('.prevHour', calendar));
            Icons.inject('chevron-up', $('.nextHour', calendar));

            Icons.inject('chevron-down', $('.prevMinute', calendar));
            Icons.inject('chevron-up', $('.nextMinute', calendar));

            Utils.longClick($('.nextHour', calendar), function (event) {
                dateKeeper.nextHour();
                updateInput(input);
                event.preventDefault();
            }, 750, 250);

            Utils.longClick($('.prevHour', calendar), function (event) {
                dateKeeper.prevHour();
                updateInput(input);
                event.preventDefault();
            }, 750, 250);

            Utils.longClick($('.nextMinute', calendar), function (event) {
                dateKeeper.nextMinute();
                updateInput(input);
                event.preventDefault();
            }, 750, 250);

            Utils.longClick($('.prevMinute', calendar), function (event) {
                dateKeeper.prevMinute();
                updateInput(input);
                event.preventDefault();
            }, 750, 250);
        }

        document.body.appendChild(calendar);

        Icons.inject('calendar-clock', $('.currentMonth', calendar));

        Icons.inject('chevron-left', $('.prevMonth', calendar));
        Icons.inject('chevron-right', $('.nextMonth', calendar));

        $('.currentMonth', calendar).addEventListener('mousedown', function (event) {
            var input = getCurrentInput();
            var today = new Date();
            dateKeeper.setDate(today);
            updateInput(input);
            input.blur();
            event.preventDefault();
        });

        Utils.longClick($('.prevMonth', calendar), function (event) {
            dateKeeper.prevMonth();
            updateInput(input);
            event.preventDefault();
        }, 750, 500);

        Utils.longClick($('.nextMonth', calendar), function (event) {
            dateKeeper.nextMonth();
            updateInput(input);
            event.preventDefault();
        }, 750, 500);

        window.addEventListener('mousedown', function (event) {
            if (calendar.style.display !== 'none') {
                if (event.target.closest('.calendar')) {
                    event.preventDefault();
                }
            }
        });

        window.addEventListener('resize', Utils.throttle(setCalendarPosition, 100));

        return calendar;
    }

    function generateCalendarTable(year, month, day) {
        var i, j;
        var num = 1;
        var firstDay = new Date(year, month, 1).getDay();
        var monthLength = dateHelpers.daysInMonth(month, year);
        var monthName = options.labels.months.long[month];
        var start = dateHelpers.mod(firstDay - options.weekStarts, 7);
        var html = '';
        html += '<tr><th class="calendar-header" colspan="7">';
        html += monthName + '&nbsp;' + year;
        html += '</th></tr>';
        html += '<tr>';
        for (i = 0; i < 7; i++ ){
            html += '<td class="calendar-header-day">';
            html += options.labels.weekdays.short[dateHelpers.mod(i + options.weekStarts, 7)];
            html += '</td>';
        }
        html += '</tr><tr>';
        for (i = 0; i < 6; i++) {
            for (j = 0; j < 7; j++) {
                if (num <= monthLength && (i > 0 || j >= start)) {
                    if (num === day) {
                        html += '<td class="calendar-day selected">';
                    } else {
                        html += '<td class="calendar-day">';
                    }
                    html += num++;
                } else if (num === 1) {
                    html += '<td class="calendar-prev-month-day">';
                    html += dateHelpers.daysInMonth(dateHelpers.mod(month - 1, 12), year) - start + j + 1;
                } else {
                    html += '<td class="calendar-next-month-day">';
                    html += num++ - monthLength;
                }
                html += '</td>';
            }
            html += '</tr><tr>';
        }
        html += '</tr>';
        $('.calendar-table', calendar).innerHTML = html;
        $$('.calendar-day', calendar).forEach(function (element) {
            element.addEventListener('mousedown', function (event) {
                event.stopPropagation();
                event.preventDefault();
            });
            element.addEventListener('click', function () {
                var input = getCurrentInput();
                var date = new Date(dateKeeper.year, dateKeeper.month, parseInt(this.textContent));
                input.setAttribute('data-date', date);
                input.value = dateHelpers.formatDateTime(date);
                input.blur();
            });
        });
    }

    function setCalendarPosition() {
        var inputRect, inputTop, inputLeft,
            calendarRect, calendarTop, calendarLeft, calendarWidth, calendarHeight,
            windowWidth, windowHeight;

        input = getCurrentInput();

        if (!input || calendar.style.display !== 'block') {
            return;
        }

        inputRect = input.getBoundingClientRect();
        inputTop = inputRect.top + window.pageYOffset;
        inputLeft = inputRect.left + window.pageXOffset;
        calendar.style.top = (inputTop + input.offsetHeight) + 'px';
        calendar.style.left = (inputLeft + input.offsetLeft) + 'px';

        calendarRect = calendar.getBoundingClientRect();
        calendarTop = calendarRect.top + window.pageYOffset;
        calendarLeft = calendarRect.left + window.pageXOffset;
        calendarWidth = Utils.outerWidth(calendar);
        calendarHeight = Utils.outerHeight(calendar);

        windowWidth = document.documentElement.clientWidth;
        windowHeight = document.documentElement.clientHeight;

        if (calendarLeft + calendarWidth > windowWidth) {
            calendar.style.left = (windowWidth - calendarWidth) + 'px';
        }

        if (calendarTop < window.pageYOffset || window.pageYOffset < calendarTop + calendarHeight - windowHeight) {
            window.scrollTo(window.pageXOffset, calendarTop + calendarHeight - windowHeight);
        }
    }
}
