<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Customer Master</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
        }

        h1 {
            margin-bottom: 20px;
            color: #333;
            font-size: 24px;
        }


        .filter-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-size: 14px;
            font-weight: 500;
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 14px;
        }

        .table-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .per-page {
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .per-page label {
            font-size: 14px;
            color: #555;
        }

        .per-page select {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        thead {
            background: #f8f9fa;
        }

        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
            font-size: 14px;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
            color: #495057;
        }

        tbody tr:hover {
            background-color: #f8f9fa;
        }

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pagination-info {
            font-size: 14px;
            color: #555;
        }

        .pagination-buttons {
            display: flex;
            gap: 10px;
        }

        .pagination-buttons button {
            padding: 8px 16px;
            background: #007bff;
            color: white;
            border: none;

            font-size: 14px;
        }


        .pagination-buttons button:disabled {
            background: #cccccc;
            cursor: not-allowed;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Customer Master</h1>

        <div class="filter-section">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="nameSearch">Name</label>
                    <input type="text" id="nameSearch" placeholder="Search by name...">
                </div>
                <div class="filter-group">
                    <label for="customerTypeFilter">Customer Type</label>
                    <select id="customerTypeFilter">
                        <option value="">All</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="packageTypeFilter">Package Type</label>
                    <select id="packageTypeFilter">
                        <option value="">All</option>
                    </select>
                </div>
                <div class="filter-group">
                    <button class="reset" id="resetBtn">Reset</button>
                </div>
            </div>
        </div>

        <div class="table-controls">
            <div class="per-page">
                <label for="perPageSelect">Show entries:</label>
                <select id="perPageSelect">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>

        <div id="tableWrapper">
            <div class="loading">Loading...</div>
        </div>

        <div class="pagination" id="paginationSection" style="display:none;">
            <div class="pagination-info" id="paginationInfo"></div>
            <div class="pagination-buttons">
                <button id="prevBtn" disabled>Previous</button>
                <button id="nextBtn" disabled>Next</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            let currentPage = 1;
            let perPage = 10;
            let totalPages = 1;
            let searchTimeout = null;

            loadDropdowns();
            loadCustomers();

            function loadDropdowns() {
                $.ajax({
                    url: '/api/customers/dropdowns/customertypes',
                    method: 'GET',
                    success: function(data) {
                        data.forEach(function(type) {
                            $('#customerTypeFilter').append(`<option value="${type}">${type}</option>`);
                        });
                    }
                });

                $.ajax({
                    url: '/api/customers/dropdowns/packagetypes',
                    method: 'GET',
                    success: function(data) {
                        data.forEach(function(type) {
                            $('#packageTypeFilter').append(`<option value="${type}">${type}</option>`);
                        });
                    }
                });
            }

            function loadCustomers() {
                const name = $('#nameSearch').val();
                const customerType = $('#customerTypeFilter').val();
                const packageType = $('#packageTypeFilter').val();

                const params = {
                    page: currentPage,
                    per_page: perPage
                };

                if (name) params.name = name;
                if (customerType) params.customertype = customerType;
                if (packageType) params.packagetype = packageType;

                $.ajax({
                    url: '/api/customers',
                    method: 'GET',
                    data: params,
                    beforeSend: function() {
                        $('#tableWrapper').html('<div class="loading">Loading...</div>');
                        $('#paginationSection').hide();
                    },
                    success: function(response) {
                        if (response.data && response.data.length > 0) {
                            renderTable(response.data);
                            updatePagination(response);
                        } else {
                            $('#tableWrapper').html('<div class="no-data">No customers found</div>');
                            $('#paginationSection').hide();
                        }
                    },
                    error: function() {
                        $('#tableWrapper').html('<div class="no-data">Error loading customers</div>');
                        $('#paginationSection').hide();
                    }
                });
            }

            function renderTable(customers) {
                let html = `
                    <table>
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Address</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Customer Type</th>
                                <th>Package Type</th>
                                <th>Owner</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                customers.forEach(function(customer) {
                    html += `
                        <tr>
                            <td>${customer.OWNCODE}</td>
                            <td>${customer.NAME || '-'}</td>
                            <td>${customer.ADDRESS || '-'}</td>
                            <td>${customer.CONTACTNUMBER || '-'}</td>
                            <td>${customer.EMAILADDRESS1 || '-'}</td>
                            <td>${customer.CUSTOMERTYPE || '-'}</td>
                            <td>${customer.PACKAGETYPE || '-'}</td>
                            <td>${customer.OWNERNAME1 || '-'}</td>
                        </tr>
                    `;
                });

                html += `
                        </tbody>
                    </table>
                `;

                $('#tableWrapper').html(html);
            }

            function updatePagination(response) {
                totalPages = response.last_page;
                currentPage = response.current_page;

                const from = response.from || 0;
                const to = response.to || 0;
                const total = response.total || 0;

                $('#paginationInfo').text(`Showing ${from} to ${to} of ${total} entries`);

                $('#prevBtn').prop('disabled', currentPage === 1);
                $('#nextBtn').prop('disabled', currentPage === totalPages);

                $('#paginationSection').show();
            }

            $('#nameSearch').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    currentPage = 1;
                    loadCustomers();
                }, 500);
            });

            $('#customerTypeFilter, #packageTypeFilter').on('change', function() {
                currentPage = 1;
                loadCustomers();
            });


            $('#resetBtn').on('click', function() {
                $('#nameSearch').val('');
                $('#customerTypeFilter').val('');
                $('#packageTypeFilter').val('');
                currentPage = 1;
                loadCustomers();
            });

            $('#perPageSelect').on('change', function() {
                perPage = $(this).val();
                currentPage = 1;
                loadCustomers();
            });

            $('#prevBtn').on('click', function() {
                if (currentPage > 1) {
                    currentPage--;
                    loadCustomers();
                }
            });

            $('#nextBtn').on('click', function() {
                if (currentPage < totalPages) {
                    currentPage++;
                    loadCustomers();
                }
            });
        });
    </script>
</body>
</html>
