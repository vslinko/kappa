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

    var Statistics = function (el) {
        var colorCycle = new ColorCycle();
        var textMeasure = new TextMeasure();

        var start = null;
        var end = null;
        var width = null;
        var zoom = null;

        var calculatePosition = function (status) {
            var margin = Math.round((status.getAttribute('data-start') - start) / zoom);
            var width = Math.round((status.getAttribute('data-end') - start) / zoom) - margin;

            status.style.marginLeft = margin + 'px';
            status.style.width = width + 'px';
        };

        var arrangeInformation = function (status) {
            var title = status.getAttribute('data-title');

            if (textMeasure.measure(title).width + 40 < status.clientWidth) {
                status.innerText = title;
            }
        };

        var colorize = function (status) {
            status.style.backgroundColor = colorCycle.next();
        };

        var arrange = function (status) {
            calculatePosition(status);
            arrangeInformation(status);
            colorize(status);
        };

        var reset = function (statistics) {
            while (el.hasChildNodes()) {
                el.removeChild(el.firstChild);
            }

            colorCycle.reset();
        };

        var renderHTML = function (statistics) {
            var statusElements = [];

            for (var staff in statistics.staffs) {
                var tr = document.createElement('tr');
                var th = document.createElement('th');
                var td = document.createElement('td');

                th.innerText = staff;

                tr.appendChild(th);
                tr.appendChild(td);
                el.appendChild(tr);

                for (var i = 0; i < statistics.staffs[staff].length; i++) {
                    var div = document.createElement('div');

                    var status = statistics.staffs[staff][i];

                    div.setAttribute('data-start', status.start);
                    div.setAttribute('data-end', status.end);
                    div.setAttribute('data-title', status.title);

                    td.appendChild(div);

                    statusElements.push(div);
                }
            }

            return {
                tableData: td,
                statusElements: statusElements
            };
        };

        var rearrange = function (statistics, renderResult) {
            start = statistics.start;
            end = statistics.end;
            width = renderResult.tableData ? renderResult.tableData.scrollWidth : el.scrollWidth;
            zoom = (end - start) / width;

            for (var i = 0; i < renderResult.statusElements.length; i++) {
                arrange(renderResult.statusElements[i]);
            }
        };

        var render = function (statistics) {
            reset(statistics);

            var renderResult = renderHTML(statistics);

            rearrange(statistics, renderResult);
        };

        var readStream = function (path) {
            microAjax(path, function (statistics) {
                render(JSON.parse(statistics));
            });
        };

        this.render = function (statistics) {
            render(statistics);
        };

        this.stream = function (path, interval) {
            readStream(path);

            setInterval(function () {
                readStream(path);
            }, interval || 60000);
        };
    };

    window.onload = function () {
        var page = new Statistics(document.getElementById('statistics'));
        page.stream('/statistics.json');
    };
})();
