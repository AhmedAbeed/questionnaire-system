@props(['headers', 'columns', 'filters' => []])

@push('styles')
<style>
    /* Modern Table Styling */
    #datatable_wrapper {
        padding: 0;
        margin: 0;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        border: 1px solid #e8e8e8;
    }
    
    /* Hide search and length menu if specified */
    #datatable_wrapper > .row:first-child {
        display: none !important;
        margin: 0;
        padding: 0;
    }

    #datatable_wrapper {
        background:transparent !important;
    }

    .dt-layout-full{
        padding: 0 !important;
    }
    
    #datatable {
        width: 100% !important;
        margin: 0 !important;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    #datatable thead th {
        background-color: #931a23;
        color: white;
        font-weight: 600;
        padding: 12px 16px;
        border: 1px solid #8a1a22;
        text-align: right;
        font-size: 0.95rem;
        transition: background-color 0.2s ease;
    }
    
    #datatable thead th:first-child {
        border-top-right-radius: 12px;
    }

    #datatable_wrapper .row:nth-child(3) {
        margin-top: 10px !important;
    }
    
    #datatable thead th:last-child {
        border-top-left-radius: 12px;
    }
    
    #datatable tbody td {
        padding: 12px 16px;
        border: 1px solid #e8e8e8;
        text-align: right;
        font-size: 0.9rem;
        color: #444;
        transition: background-color 0.2s ease;
    }
    
    #datatable tbody tr:hover td {
        background-color: #f8f9fa;
    }
    
    /* Modern Pagination */
    .dataTables_paginate {
        padding: 12px 16px !important;
        display: flex;
        justify-content: flex-start;
        gap: 8px;
        border-top: 1px solid #e8e8e8;
        margin: 0;
    }
    
    .paginate_button {
        padding: 6px 12px !important;
        border: 1px solid #e0e0e0 !important;
        border-radius: 6px !important;
        background: white !important;
        color: #444 !important;
        font-weight: 500 !important;
        transition: all 0.2s ease !important;
    }
    
    .paginate_button:hover {
        background: #f8f9fa !important;
        border-color: #d0d0d0 !important;
        color: #931a23 !important;
    }
    
    .paginate_button.current {
        background: #931a23 !important;
        border-color: #931a23 !important;
        color: white !important;
    }
    
    /* Loading State */
    .dataTables_processing {
        background: rgba(255, 255, 255, 0.9) !important;
        border: none !important;
        border-radius: 8px !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
        padding: 12px 20px !important;
        color: #444 !important;
        font-weight: 500 !important;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        #datatable_wrapper {
            border-radius: 8px;
        }
        
        #datatable thead th,
        #datatable tbody td {
            padding: 10px 12px;
        }
        
        .dataTables_paginate {
            justify-content: center;
            flex-wrap: wrap;
            padding: 10px !important;
        }
    }
    
    /* Error State */
    .dataTables_error {
        padding: 12px 16px;
        text-align: center;
        color: #dc3545;
        background: #fff5f5;
        border-radius: 8px;
        margin: 0;
        border: 1px solid #ffcdd2;
    }
</style>
@endpush

<div class="card m-b-30 bg-transparent" dir="rtl">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="datatable" class="table">
                <thead>
                    <tr>
                        @foreach ($headers as $header)
                            <th>{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <!-- Content will be loaded via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>
@push('scripts')
<script>
$(document).ready(function() {
    try {
        const columns = @json($columns);
        const filters = @json($filters);
        
        // Destroy existing DataTable instance if it exists
        if ($.fn.DataTable.isDataTable('#datatable')) {
            $('#datatable').DataTable().destroy();
        }
        
        var table = $('#datatable').DataTable({
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            language: {
                "sProcessing": "جاري التحميل...",
                "sLengthMenu": "أظهر _MENU_ سجل",
                "sZeroRecords": "لم يعثر على أية سجلات",
                "sInfo": "إظهار _START_ إلى _END_ من أصل _TOTAL_ سجل",
                "sInfoEmpty": "يعرض 0 إلى 0 من أصل 0 سجل",
                "sInfoFiltered": "(منتقاة من مجموع _MAX_ سجل)",
                "sInfoPostFix": "",
                "sSearch": "ابحث:",
                "sUrl": "",
                "oPaginate": {
                    "sFirst": "الأول",
                    "sPrevious": "السابق",
                    "sNext": "التالي",
                    "sLast": "الأخير"
                }
            },
            ordering:false,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "الكل"]],
            responsive: true,
            stateSave: false,
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ $attributes['data-url'] }}',
                type: 'GET',
                data: function(d) {
                    // Add custom filters to the request
                    filters.forEach(filter => {
                        const value = $(filter.selector).val();
                        if (value) {
                            d[filter.name] = value;
                        }
                    });
                },
                error: function (xhr, error, thrown) {
                    const errorMessage = xhr.responseJSON?.message || 'فشل جلب البيانات. يرجى المحاولة مرة أخرى.';
                    $('#datatable_wrapper').prepend(`
                        <div class="dataTables_error">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            ${errorMessage}
                        </div>
                    `);
                }
            },
            columns: columns.map(column => ({
                data: column.data,
                name: column.name,
                orderable: column.orderable !== undefined ? column.orderable : true,
                searchable: column.searchable !== undefined ? column.searchable : true,
                render: column.render ? eval(column.render) : null
            })),
            initComplete: function() {
                // Add custom search functionality
                this.api().columns().every(function() {
                    let column = this;
                    let header = $(column.header());
                    let searchInput = header.find('input[type="search"]');
                    
                    if (searchInput.length) {
                        searchInput.on('keyup change', function() {
                            if (column.search() !== this.value) {
                                column.search(this.value).draw();
                            }
                        });
                    }
                });

                // Remove any existing error messages
                $('.dataTables_error').remove();
            }
        });
        
        // Add loading indicator
        table.on('processing.dt', function(e, settings, processing) {
            if (processing) {
                $('.dataTables_processing').addClass('d-flex align-items-center justify-content-center');
            }
        });

        // Toggle filter section
        $('#toggleFilters').click(function() {
            $('#filterSection').slideToggle();
        });

        // Apply filters
        $('#applyFilters').click(function() {
            table.ajax.reload();
        });

        // Reset filters
        $('#resetFilters').click(function() {
            $('#filterForm')[0].reset();
            table.ajax.reload();
        });

        // Add keyboard shortcut for search
        $(document).keydown(function(e) {
            if (e.ctrlKey && e.keyCode === 70) { // Ctrl + F
                e.preventDefault();
                $('#filterName').focus();
            }
        });

        // Add tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();

    } catch (error) {
        console.error('DataTable initialization error:', error);
        const errorMessage = 'فشل في تهيئة جدول البيانات';
        $('#datatable_wrapper').prepend(`
            <div class="dataTables_error">
                <i class="fas fa-exclamation-circle me-2"></i>
                ${errorMessage}
            </div>
        `);
    }
});
</script>
@endpush
