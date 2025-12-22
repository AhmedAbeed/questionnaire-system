$(document).ready(function() {
    // Dashboard Module
    const Dashboard = {
        // Configuration
        config: {
            statsEndpoint: window.config?.statsEndpoint,
            chartDataEndpoint: window.config?.chartDataEndpoint,
            statCards: ['total-students', 'active-questionnaires', 'total-responses']
        },

        // Initialize dashboard
        init: function() {
            this.loadStatsValues();
            this.initChart();
        },

        // Show error state for stat cards
        showErrorState: function() {
            this.config.statCards.forEach(id => {
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
        },

        // Load stats values
        loadStatsValues: function() {
            $.ajax({
                url: this.config.statsEndpoint,
                method: 'GET',
                success: (response) => {
                    // Update Total Students
                    $('#total-students-value').html(`<h4>${response.data.total_students.value}</h4>`);
                    $('#total-students-updated').html(`${response.data.total_students.updated}`);

                    // Update Active Questionnaires
                    $('#active-questionnaires-value').html(`<h4>${response.data.active_questionnaires.value}</h4>`);
                    $('#active-questionnaires-updated').html(`${response.data.active_questionnaires.updated}`);

                    // Update Response Rate
                    $('#total-responses-value').html(`<h4>${response.data.total_responses.value}</h4>`);
                    $('#total-responses-updated').html(`${response.data.total_responses.updated}`);
                },
                error: (error) => {
                    console.error('Error loading stats:', error);
                    this.showErrorState();
                }
            });
        },

        // Initialize chart
        initChart: function() {
            // Fetch chart data via AJAX
            $.ajax({
                url: this.config.chartDataEndpoint,
                method: 'GET',
                success: (response) => {
                    Dashboard.renderChart(response.data);
                },
                error: (error) => {
                    console.error('Error loading chart data:', error);
                }
            });
        },

        renderChart: function(chartData) {
            const ctx = document.getElementById('responseRateChart');
            if (!ctx) return;

            const config = {
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
                    layout: {
                        padding: {
                            right: 10,
                            left: 10
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: 0,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                stepSize: 50,
                                direction: 'rtl'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                direction: 'rtl'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 10,
                            cornerRadius: 4,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return 'الإجابات: ' + context.raw;
                                }
                            }
                        }
                    }
                }
            };

            const responseChart = new Chart(ctx, config);

            // Handle period selector buttons
            $('.chart-period-btn').on('click', function() {
                $('.chart-period-btn').removeClass('active');
                $(this).addClass('active');

                const period = $(this).data('period');
                responseChart.data.labels = chartData[period].labels;
                responseChart.data.datasets[0].data = chartData[period].data;
                responseChart.update();
            });
        }
    };

    // Initialize dashboard
    Dashboard.init();
}); 