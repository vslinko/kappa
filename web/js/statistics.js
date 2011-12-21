(function () {
    var ColorCycle = function () {
        var colors = ['#fce94f', '#fcaf3e', '#e9b96e', '#8ae234', '#729fcf', '#ad7fa8', '#ef2929'];
        var i = -1;

        this.next = function () {
            i++;
            if (i == colors.length) {
                i = 0;
            }
            return colors[i];
        };

        this.reset = function () {
            i = -1;
        };
    };

    var TextMeasure = function () {
        var el = document.createElement('p');
        el.style.position = 'absolute';
        el.style.width = 'auto';
        el.style.height = 'auto';
        el.style.top = '100%';
        document.body.appendChild(el);

        this.measure = function (string) {
            el.innerHTML = string;
            var sizes = {width: el.clientWidth, height: el.clientHeight};
            el.innerHTML = '';

            return sizes;
        };
    };

    var Statistics = function (table, template) {
        var colorCycle = new ColorCycle();
        var textMeasure = new TextMeasure();

        var start = null;
        var end = null;
        var statistics = null;
        var clientWidth = 0;
        var staffWidth = 0;
        var panelWidth = 0;
        var zoom = null;

        var position = function (ticketStart, ticketEnd) {
            var margin = Math.round((ticketStart - start) / zoom);
            var width = Math.round((ticketEnd - start) / zoom) - margin;

            return {
                margin: margin,
                width: width
            }
        };

        var time = function (position) {
            return Math.round(position * zoom) + start;
        };

        var render = function () {
            if (document.body.clientWidth == clientWidth) {
                return;
            }

            clientWidth = document.body.clientWidth;
            panelWidth = document.body.clientWidth - staffWidth;
            zoom = (end - start) / panelWidth;
            colorCycle.reset();

            var view = {
                staffs: [],
                timeline: []
            };

            var day = null;
            var max = (Math.round(panelWidth / 100) - 1) * 100;

            for (var staffName in statistics) {
                var staff = {
                    name: staffName,
                    tickets: []
                };

                var tickets = statistics[staffName];
                for (var i = 0; i < tickets.length; i++) {
                    var ticket = tickets[i];
                    var measure = textMeasure.measure(ticket.title);
                    var place = position(ticket.start, ticket.end);

                    staff.tickets.push({
                        margin: place.margin,
                        width: place.width,
                        color: colorCycle.next(),
                        label: measure.width < place.width ? ticket.title : ''
                    });
                }

                view.staffs.push(staff);
            }

            for (var pos = 0; pos < max; pos += 100) {
                var date = new Date();
                date.setTime(time(pos) * 1000);

                if (day != date.getDate()) {
                    view.timeline.push({timestamp: date.toString('MMM dd HH:mm')});
                    day = date.getDate();
                } else {
                    view.timeline.push({timestamp: date.toString('HH:mm')});
                }
            }

            table.innerHTML = template(view);
        };

        var update = function (data) {
            start = data.start;
            end = data.end;
            statistics = data.statistics;
            staffWidth = 0;

            for (var staff in statistics) {
                var measure = textMeasure.measure(staff);
                if (measure.width > staffWidth) {
                    staffWidth = measure.width;
                }
            }

            render();
        };

        var receive = function (path) {
            microAjax(path, function (statistics) {
                update(JSON.parse(statistics));
            });
        };

        this.update = function (data) {
            update(data);
        };

        this.subscribe = function (path, interval) {
            receive(path);

            setInterval(function () {
                receive(path);
            }, interval || 60000)
        };

        vine.bind(window, 'resize', function () {
            render();
        });
    };

    vine.bind(window, 'load', function () {
        var page = new Statistics(document.getElementById('statistics-table'), ich.statistics);
        page.subscribe('/statistics.json');
    });
})();
