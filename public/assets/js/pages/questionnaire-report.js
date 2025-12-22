$(document).ready(function() {
    // Questionnaire Report Module
    const QuestionnaireReport = {
        // Configuration
        config: {
            data: window.config.data.questionnaireData,
            chartColors: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'],
            chartOptions: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        },

        // Initialize module
        init: function() {
            this.initializeCharts();
            this.setupHandlers();
        },

        // Setup all handlers
        setupHandlers: function() {
            this.setupAiInsightsHandlers();
            this.setupTabHandlers();
        },

        // Initialize charts
        initializeCharts: function() {
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not loaded');
                this.showErrorMessage('عذراً، لم يتم تحميل مكتبة المخططات');
                return;
            }

            try {
                const questions = this.config.data?.questions || [];
                
                if (!Array.isArray(questions)) {
                    throw new Error('Invalid questionnaire data: questions is not an array');
                }

                questions.forEach((question) => {
                    this.initializeQuestionCharts(question);
                });
            } catch (error) {
                console.error('Error initializing charts:', error);
                this.showErrorMessage('عذراً، بيانات الاستبيان غير صالحة');
            }
        },

        // Initialize charts for a single question
        initializeQuestionCharts: function(question) {
            try {
                const questionType = question.type?.name || question.type;
                
                const supportedTypes = ['likert scale', 'multiple choice', 'single choice', 'rating'];
                const isSupported = supportedTypes.some(type => 
                    questionType?.toLowerCase() === type.toLowerCase()
                );

                if (!isSupported) {
                    console.log('Skipping unsupported question type:', questionType);
                    return;
                }

                const options = question.options || [];
                if (!options.length) {
                    console.log('No options found for question:', question.id);
                    return;
                }
                this.createPieChart(question);
                this.createBarChart(question);
            } catch (error) {
                console.error(`Error initializing charts for question ${question.id}:`, error);
                this.showErrorMessage(`عذراً، حدث خطأ في تحميل المخطط البياني للسؤال ${question.text}`, `chart-${question.id}`);
            }
        },

        // Create pie chart for a question
        createPieChart: function(question) {
            const options = question.options || [];
            const labels = options.map(opt => opt.text || 'غير محدد');
            const data = options.map(opt => opt.count || 0);

            const filteredLabels = labels.filter((_, i) => data[i] > 0);
            const filteredData = data.filter(count => count > 0);
            const pieColors = this.config.chartColors.slice(0, filteredData.length);

            const pieChartId = `chart-${question.id}-pie`;
            const pieCanvas = document.getElementById(pieChartId);
            if (pieCanvas) {
                new Chart(pieCanvas, {
                    type: 'pie',
                    data: {
                        labels: filteredLabels.length > 0 ? filteredLabels : ['لا توجد بيانات'],
                        datasets: [{
                            data: filteredData.length > 0 ? filteredData : [1],
                            backgroundColor: filteredData.length > 0 ? pieColors : ['#cccccc'],
                            borderColor: '#ffffff',
                            borderWidth: 2
                        }]
                    },
                    options: this.config.chartOptions
                });
            }
        },

        // Create bar chart for a question
        createBarChart: function(question) {
            const options = question.options || [];
            const labels = options.map(opt => opt.text || 'غير محدد');
            const data = options.map(opt => opt.count || 0);

            const barChartId = `chart-${question.id}-bar`;
            const barCanvas = document.getElementById(barChartId);
            if (barCanvas) {
                new Chart(barCanvas, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'الاستجابات',
                            data: data,
                            backgroundColor: '#36A2EB',
                            borderColor: '#2a8bc7',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        ...this.config.chartOptions,
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }
        },

        // Setup AI insights handlers
        setupAiInsightsHandlers: function() {
            // Handle all AI toggle buttons
            document.querySelectorAll('.toggle-ai-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const insightsDiv = document.getElementById('mainAiInsights');
                    if (insightsDiv) {
                        // Add a small delay to the icon rotation
                        const icon = this.querySelector('i');
                        if (icon) {
                            icon.style.transition = 'transform 0.3s ease';
                        }

                        // Toggle classes with animation
                        insightsDiv.classList.toggle('show');
                        this.classList.toggle('active');
                        
                        // Update button text with animation
                        const buttonText = this.querySelector('span') || document.createElement('span');
                        if (!this.querySelector('span')) {
                            this.appendChild(buttonText);
                        }
                        
                        if (insightsDiv.classList.contains('show')) {
                            buttonText.textContent = 'إخفاء تحليل الذكاء الاصطناعي';
                            this.innerHTML = `<i class="fa fa-robot ms-2"></i>${buttonText.textContent}`;
                        } else {
                            buttonText.textContent = 'إظهار تحليل الذكاء الاصطناعي';
                            this.innerHTML = `<i class="fa fa-robot ms-2"></i>${buttonText.textContent}`;
                        }

                        // Add ripple effect
                        const ripple = document.createElement('span');
                        ripple.classList.add('ripple');
                        this.appendChild(ripple);
                        
                        const rect = this.getBoundingClientRect();
                        const size = Math.max(rect.width, rect.height);
                        ripple.style.width = ripple.style.height = `${size}px`;
                        ripple.style.left = `${event.clientX - rect.left - size/2}px`;
                        ripple.style.top = `${event.clientY - rect.top - size/2}px`;
                        
                        ripple.classList.add('active');
                        setTimeout(() => ripple.remove(), 600);
                    }
                });
            });
        },

        // Setup tab handlers
        setupTabHandlers: function() {
            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                const targetId = $(e.target).attr('href');
                const $targetPane = $(targetId);
                
                // Refresh charts in the active tab
                $targetPane.find('canvas').each(function() {
                    const chart = Chart.getChart(this);
                    if (chart) {
                        chart.resize();
                    }
                });
            });
        },

        // Show error message
        showErrorMessage: function(message, containerId) {
            const container = containerId 
                ? document.getElementById(containerId)
                : document.querySelector('.chart-wrapper');
                
            if (container) {
                container.innerHTML = `<div class="alert alert-danger" role="alert">${message}</div>`;
            }
        }
    };

    // Initialize the module
    QuestionnaireReport.init();
});

