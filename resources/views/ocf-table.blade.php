<!DOCTYPE html>
<html>
<head>
    <title>OCF Data Table</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "IBM Plex Sans", sans-serif;
        }

        body {
            padding: 20px;
            background: #fafafa;
            color: #333;
        }

        #row-stat {
            background: #e9f0ff;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
        }
        #row-stat strong {
            color: #0055cc;
        }

        /* ----- Loader ----- */
        #loader-bar {
            width: 100%;
            height: 4px;
            background: #e3e3e3;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        #loader {
            display: flex;
            align-items: center;
            font-size: 14px;
            color: #555;
            margin-bottom: 14px;
        }

        .dot {
            width: 8px;
            height: 8px;
            background: #4b8bf4;
            border-radius: 50%;
            margin-right: 8px;
        }

        /* ----- Table Wrapper ----- */
        .table-wrapper {
            background: #fff;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ddd;
        }

        .table-scroll {
            overflow-x: auto;
            overflow-y: auto;
            max-height: 550px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1300px;
            font-size: 13px;
        }

        thead th {
            background: #f5f5f5;
            padding: 8px;
            border-bottom: 1px solid #ddd;
            white-space: nowrap;
            font-weight: 500;
        }

        td {
            padding: 6px;
            border-bottom: 1px solid #eee;
            white-space: nowrap;
        }

        .empty-state {
            text-align: center;
            color: #777;
            padding: 20px;
        }

        /* ----- Badge ----- */
        .badge {
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
        }
        .badge-true {
            background: #e1f6e8;
            color: #0d7a27;
        }
        .badge-false {
            background: #fdeaea;
            color: #b10c0c;
        }
        .badge-neutral {
            background: #eee;
            color: #555;
        }

        /* ----- Pagination ----- */
        .pagination {
            display: flex;
            justify-content: space-between;
            padding: 12px 0 4px;
            font-size: 14px;
        }

        .pagination-controls button {
            padding: 6px 12px;
            border: 1px solid #ccc;
            background: #fff;
            border-radius: 4px;
            cursor: pointer;
            margin: 0 3px;
            font-size: 13px;
        }

        .pagination-controls button:disabled {
            opacity: 0.5;
            cursor: default;
        }

        .rpp-select {
            padding: 4px 6px;
            font-size: 13px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>


<div id="loader"><span class="dot"></span><span id="loader-text">Initializing stream…</span></div>

<div class="table-wrapper">
    <div class="table-scroll">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Series</th>
                <th>Doc No</th>
                <th>OCF Date</th>
                <th>Amount Total</th>
                <th>Customer Code</th>
                <th>Customer Name</th>
                <th>Company Code</th>
                <th>Company Name</th>
                <th>Module ID</th>
                <th>Module Name</th>
                <th>Quantity</th>
                <th>Unit</th>
                <th>Amount</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Status</th>
                <th>Module Code</th>
                <th>ACME Module Name</th>
                <th>Module Type ID</th>
                <th>Module Type Name</th>
            </tr>
            </thead>
            <tbody id="table-body">
                <tr><td colspan="23" class="empty-state">Awaiting data…</td></tr>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <div class="pagination-info">
            <span class="rpp-label">Rows per page:</span>
            <select class="rpp-select" id="rpp-select">
                <option value="10" selected>10</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="250">250</option>
                <option value="500">500</option>
            </select>
        </div>
        <div class="pagination-controls">
            <button id="prev" disabled>← Prev</button>
            <span id="page-info">Page 1 / 1</span>
            <button id="next" disabled>Next →</button>
        </div>
    </div>
</div>

<script>


let dataset = [];
let currentPage = 1;
let rowsPerPage = 10; 
let tableRendered = false; 


function loadData() {
    let lastIndex = 0;
    let lastRenderedCount = 0; // track when we last rendered
    tableRendered = false;
    dataset = [];

    $.ajax({
        url: "{{ url('http://localhost:8001/api/ocf-master') }}",
        type: "POST",
        contentType: "application/json",
        dataType: "text",
        data: JSON.stringify({}),
        xhrFields: {
            onprogress: function () {
                let chunk = this.responseText.substring(lastIndex);
                lastIndex = this.responseText.length;
                console.log(chunk)
                for (let line of chunk.split("\n")) {
                    line = line.trim();
                    if (line.startsWith("{") && line.endsWith("}")) {
                        try { dataset.push(JSON.parse(line)); } catch (e) {}
                    }
                }

                // Update live counter
                $('#loader-text').text("Loading... Rows received: " + dataset.length);
                $('#row-stat').show();

                // ✅ Render table as soon as first page worth of rows is ready
                if (!tableRendered && dataset.length >= rowsPerPage) {
                    tableRendered = true;
                    lastRenderedCount = dataset.length;
                    renderTable();
                    updatePageInfo();
                }
            }
        },
        success: function () {
            $('#loader-text').text("Loaded total rows: " + dataset.length);
            // Final render — update pagination info with complete data
            renderTable();
            updatePageInfo();
        },
        error: function (xhr, status, err) {
            $('#loader-text').text("Error loading data: " + status);
        }
    });
}

// // ── RENDER ────────────────────────────────────────────────────────
function renderTable() {
    let start = (currentPage - 1) * rowsPerPage;
    let end   = start + rowsPerPage;
    let subset = dataset.slice(start, end);
    let html = "";

    if (subset.length === 0) {
        html = '<tr><td colspan="23" class="empty-state">No records found.</td></tr>';
    } else {
        
        subset.forEach(r => {
            html += `
            <tr>
                <td>${r.id ?? "-"}</td>
                <td>${r.Series ?? "-"}</td>
                <td>${r.DocNo ?? "-"}</td>
                <td>${r.ocf_date ?? "-"}</td>
                <td>${r.AmountTotal ?? "-"}</td>
                <td>${r.customercode ?? "-"}</td>
                <td>${r.customername ?? "-"}</td>
                <td>${r.companycode ?? "-"}</td>
                <td>${r.companyname ?? "-"}</td>
                <td>${r.module_id ?? "-"}</td>
                <td>${r.modulename ?? "-"}</td>
                <td>${r.quantity ?? "-"}</td>
                <td>${r.unit ?? "-"}</td>
                <td>${r.amount ?? "-"}</td>
                <td>${r.startDate ?? "-"}</td>
                <td>${r.endDate ?? "-"}</td>
                <td>${r.status ?? "-"}</td>
                <td>${r.modulecode ?? "-"}</td>
                <td>${r.ModuleName ?? "-"}</td>
                <td>${r.moduletypes ?? "-"}</td>
                <td>${r.moduletypename ?? "-"}</td>
            </tr>`;
        });
    }

    document.getElementById("table-body").innerHTML = html;
    $('#prev').prop('disabled', currentPage <= 1);
    $('#next').prop('disabled', currentPage >= Math.ceil(dataset.length / rowsPerPage));
}

function updatePageInfo() {
    let totalPages = Math.max(1, Math.ceil(dataset.length / rowsPerPage));
    document.getElementById("page-info").innerText = "Page " + currentPage + " / " + totalPages;
    $('#prev').prop('disabled', currentPage <= 1);
    $('#next').prop('disabled', currentPage >= totalPages);
}

// ── CONTROLS ──────────────────────────────────────────────────────
$('#next').on('click', function () {
    let totalPages = Math.ceil(dataset.length / rowsPerPage);
    if (currentPage < totalPages) {
        currentPage++;
        renderTable();
        updatePageInfo();
    }
});

$('#prev').on('click', function () {
    if (currentPage > 1) {
        currentPage--;
        renderTable();
        updatePageInfo();
    }
});

$('#rpp-select').on('change', function () {
    rowsPerPage = parseInt($(this).val());
    currentPage = 1;
    renderTable();
    updatePageInfo();
});

// ── START ─────────────────────────────────────────────────────────
loadData();
</script>

</body>
</html>