(function (window, document) {
    function dataTable(id, params) {
        var options = Object.assign({
            sortable: false,
            searchable: true,
            pagination: true,
            pageSize: 10,
            pageSizeOptions: [10, 25, 50],
            emptyText: 'No records found.',
            params: function () { return {}; }
        }, params || {});
        var table = typeof id === 'string' ? document.getElementById(id) : id;

        if (!table || table.tagName !== 'TABLE') {
            return null;
        }

        var body = table.tBodies[0] || table.createTBody();
        if (options.endpoint) {
            var searchInput = document.querySelector(options.searchInput || '#' + id + '-search');
            var controls = document.querySelector(options.paginationContainer || '#' + id + '-pagination');
            var state = { page: 1, pageSize: options.pageSize };
            var load = function () {
                var query = new URLSearchParams({ datatable: '1', page: state.page, per_page: state.pageSize });
                if (searchInput && searchInput.value.trim()) query.set('search', searchInput.value.trim());
                var extraParams = options.params() || {};
                Object.keys(extraParams).forEach(function (key) { if (extraParams[key]) query.set(key, extraParams[key]); });
                body.innerHTML = '<tr><td colspan="' + (table.tHead ? table.tHead.rows[0].cells.length : 1) + '" style="padding:32px;text-align:center;">Loading...</td></tr>';
                fetch(options.endpoint + (options.endpoint.indexOf('?') === -1 ? '?' : '&') + query.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                    .then(function (response) { return response.json(); })
                    .then(function (result) {
                        body.innerHTML = result.data.length ? result.data.map(options.rowRenderer).join('') : '<tr><td colspan="' + table.tHead.rows[0].cells.length + '" style="padding:32px;text-align:center;color:var(--ink-soft);">' + options.emptyText + '</td></tr>';
                        renderControls(result);
                    });
            };
            var renderControls = function (result) {
                controls.innerHTML = '<span style="color:var(--ink-soft);font-size:13px;">' + result.total + ' records · Page ' + result.current_page + ' of ' + result.last_page + '</span><div style="display:flex;align-items:center;gap:6px;"></div>';
                var group = controls.lastElementChild;
                var select = document.createElement('select'); select.className = 'f-input'; select.style.width = 'auto';
                options.pageSizeOptions.forEach(function (size) { var option = new Option(size + ' / page', size, size === state.pageSize, size === state.pageSize); select.add(option); });
                select.addEventListener('change', function () { state.pageSize = Number(this.value); state.page = 1; load(); }); group.appendChild(select);
                var previous = document.createElement('button'); previous.className = 'btn btn-ghost'; previous.textContent = 'Previous'; previous.disabled = result.current_page <= 1; previous.onclick = function () { state.page -= 1; load(); }; group.appendChild(previous);
                var next = document.createElement('button'); next.className = 'btn btn-ghost'; next.textContent = 'Next'; next.disabled = result.current_page >= result.last_page; next.onclick = function () { state.page += 1; load(); }; group.appendChild(next);
            };
            if (searchInput) searchInput.addEventListener('input', function () { clearTimeout(load.timer); load.timer = setTimeout(function () { state.page = 1; load(); }, 300); });
            if (options.reloadOnChange) {
                document.querySelectorAll(options.reloadOnChange).forEach(function (element) { element.addEventListener('change', function () { state.page = 1; load(); }); });
            }
            load();
            return { table: table, reload: function () { state.page = 1; load(); } };
        }

        var header = table.tHead ? table.tHead.rows[0] : table.rows[0];
        var rows = Array.prototype.slice.call(body.rows);

        if (options.sortable && header) {
            Array.prototype.forEach.call(header.cells, function (cell, index) {
                if (cell.dataset.sortable === 'false') return;
                cell.style.cursor = 'pointer';
                cell.addEventListener('click', function () {
                    var ascending = cell.dataset.sortDirection !== 'asc';
                    rows.sort(function (first, second) {
                        var a = first.cells[index] ? first.cells[index].textContent.trim().toLowerCase() : '';
                        var b = second.cells[index] ? second.cells[index].textContent.trim().toLowerCase() : '';
                        return a.localeCompare(b, undefined, { numeric: true }) * (ascending ? 1 : -1);
                    });
                    Array.prototype.forEach.call(header.cells, function (other) { delete other.dataset.sortDirection; });
                    cell.dataset.sortDirection = ascending ? 'asc' : 'desc';
                    rows.forEach(function (row) { body.appendChild(row); });
                    render();
                });
            });
        }

        var searchInput = null;
        if (options.searchable) {
            searchInput = document.querySelector(options.searchInput || '#' + id + '-search');
            if (searchInput) searchInput.addEventListener('input', render);
        }

        var controls = options.pagination ? document.querySelector(options.paginationContainer || '#' + id + '-pagination') : null;
        function render() {
            var term = searchInput ? searchInput.value.toLowerCase().trim() : '';
            var filtered = rows.filter(function (row) { return !term || row.textContent.toLowerCase().indexOf(term) !== -1; });
            var page = controls ? Number(controls.dataset.page || 1) : 1;
            var visible = options.pagination ? filtered.slice((page - 1) * options.pageSize, page * options.pageSize) : filtered;
            rows.forEach(function (row) { row.hidden = visible.indexOf(row) === -1; });

            if (controls) {
                controls.innerHTML = '';
                var pages = Math.max(1, Math.ceil(filtered.length / options.pageSize));
                if (page > pages) page = pages;
                controls.dataset.page = page;
                for (var number = 1; number <= pages; number += 1) {
                    var button = document.createElement('button');
                    button.type = 'button';
                    button.textContent = number;
                    button.disabled = number === page;
                    button.addEventListener('click', function () { controls.dataset.page = this.textContent; render(); });
                    controls.appendChild(button);
                }
            }
        }

        render();
        return { table: table, refresh: render };
    }

    window.dataTable = dataTable;
    window.dataTable.escape = function (value) { var div = document.createElement('div'); div.textContent = value == null ? '' : value; return div.innerHTML; };
}(window, document));