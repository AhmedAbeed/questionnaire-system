$(document).ready(() => {
    
    // === Config & Endpoints ===
    const scriptTag = document.querySelector('script[src*="admin.js"]');
    let endpoints = {};
    if (scriptTag && scriptTag.dataset.endpoints) {
        try {
            endpoints = JSON.parse(scriptTag.dataset.endpoints);
        } catch (e) {
            console.error('Failed to parse endpoints data attribute:', e);
        }
    }

    // === Helpers ===
    const showAlert = (type, title, text) => {
        Swal.fire({ icon: type, title, text, confirmButtonText: 'حسناً' });
    };

    const showStatErrorState = (statCards) => {
        statCards.forEach(id => {
            $(`#${id}-value`).html(`
                <div class="alert alert-danger py-1 px-2 mb-0">
                    <i class="feather icon-alert-circle me-1"></i>
                    <small>خطأ في التحميل</small>
                </div>
            `);
            $(`#${id}-updated`).html(`
                <i class="feather icon-alert-circle me-1"></i>
                <span class="text-danger">حدث خطأ في تحميل البيانات</span>
            `);
        });
    };

    // === Dashboard Module ===
    const statCards = ['total-students', 'active-questionnaires', 'total-responses'];
    const ajaxDefaults = {
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    };

    // --- Stats ---
    const populateStats = data => {
        try {
            $('#total-students-value').html(`<h4>${data.total_students.value}</h4>`);
            $('#total-students-updated').html(`${data.total_students.updated}`);
            $('#active-questionnaires-value').html(`<h4>${data.active_questionnaires.value}</h4>`);
            $('#active-questionnaires-updated').html(`${data.active_questionnaires.updated}`);
            $('#total-responses-value').html(`<h4>${data.total_responses.value}</h4>`);
            $('#total-responses-updated').html(`${data.total_responses.updated}`);
        } catch (error) {
            console.error('Error populating stats:', error);
            showStatErrorState(statCards);
        }
    };

    const loadStats = () => {
        $.ajax({
            ...ajaxDefaults,
            url: endpoints.statsEndpoint,
            method: 'GET',
            success: res => {
                if (res.success) populateStats(res.data);
                else {
                    showAlert('error', 'خطأ!', res.message || 'خطأ في تحميل البيانات');
                    showStatErrorState(statCards);
                }
            },
            error: xhr => {
                showAlert('error', 'خطأ!', xhr.responseJSON?.message || 'خطأ في تحميل البيانات');
                showStatErrorState(statCards);
            }
        });
    };

    // --- Chart ---
    const renderChart = chartData => {
        const ctx = document.getElementById('responseRateChart');
        if (!ctx) return;
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.monthly.labels,
                datasets: [{
                    label: 'عدد الإجابات',
                    data: chartData.monthly.data,
                    borderColor: '#3b7ddd',
                    backgroundColor: 'rgba(59, 125, 221, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#3b7ddd',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { right: 10, left: 10 } },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 0,
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                        ticks: { stepSize: 50, direction: 'rtl' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { direction: 'rtl' }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 10,
                        cornerRadius: 4,
                        displayColors: false,
                        callbacks: {
                            label: ctx => 'الإجابات: ' + ctx.raw
                        }
                    }
                }
            }
        });
        $('.chart-period-btn').on('click', function() {
            $('.chart-period-btn').removeClass('active');
            $(this).addClass('active');
            const period = $(this).data('period');
            chart.data.labels = chartData[period].labels;
            chart.data.datasets[0].data = chartData[period].data;
            chart.update();
        });
    };

    const loadChart = () => {
        $.ajax({
            url: endpoints.chartDataEndpoint,
            method: 'GET',
            success: res => renderChart(res.data),
            error: err => console.error('Error loading chart data:', err)
        });
    };

    // === Init ===
    loadStats();
    loadChart();
}); 