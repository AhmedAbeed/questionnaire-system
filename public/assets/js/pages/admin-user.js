$(document).ready(function() {
    // Admin User Management Module
    const AdminUser = {
        config: {
            statsEndpoint: window.config.routes.statsEndpoint,
            storeEndpoint: window.config.routes.storeEndpoint,
            statCards: ['total-admins'],
            select2: {
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: true,
                minimumResultsForSearch: 5,
                language: {
                    noResults: function() { return "لا توجد نتائج"; },
                    searching: function() { return "جاري البحث..."; },
                    inputTooShort: function() { return "الرجاء إدخال حرفين على الأقل للبحث"; },
                    errorLoading: function() { return "حدث خطأ أثناء تحميل النتائج"; }
                },
                escapeMarkup: function(markup) { return markup; }
            }
        },

        init: function() {
            this.loadStatsValues();
            this.setupModalHandlers();
            this.setupFormHandlers();
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
                    // Update Response Rate
                    $('#total-admins-value').html(`<h4>${response.data.total_admins.value}</h4>`);
                    $('#total-admins-updated').html(`${response.data.total_admins.updated}`);
                },
                error: (error) => {
                    console.error('Error loading stats:', error);
                    this.showErrorState();
                }
            });
        },

        setupModalHandlers: function() {
            const roleSelect = document.getElementById('roleSelect');
            const facultySection = document.querySelector('.faculty-section');
            const facultySelect = document.getElementById('facultySelect');
            if (roleSelect && facultySection && facultySelect) {
                roleSelect.addEventListener('change', function() {
                    if (this.value === 'admin') {
                        facultySection.style.display = 'block';
                        facultySelect.required = true;
                    } else {
                        facultySection.style.display = 'none';
                        facultySelect.required = false;
                    }
                });
            }
        },

        setupFormHandlers: function() {
            const self = this;
            const addUserForm = document.getElementById('addUserForm');
            const addUserModal = document.getElementById('addUserModal');
            if (!addUserForm || !addUserModal) return;
            const modal = new bootstrap.Modal(addUserModal);
            const submitButton = addUserForm.querySelector('button[type="submit"]');

            addUserForm.addEventListener('submit', function(e) {
                e.preventDefault();
                submitButton.disabled = true;
                submitButton.innerHTML = `
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    جاري الحفظ...
                `;
                const formData = new FormData(this);
               
                $.ajax({
                    url: self.config.storeEndpoint,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    success: (response) => {
                        if (response.success) {
                            self.reloadDataTable();
                            modal.hide();
                            addUserForm.reset();
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: response.message || 'تم إضافة المستخدم بنجاح',
                                showConfirmButton: false,
                                timer: 3000
                            });
                        } else {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'error',
                                title: response.message || 'حدث خطأ أثناء إضافة المستخدم',
                                showConfirmButton: false,
                                timer: 3000
                            });
                        }
                    },
                    error: (error) => {
                        console.error('Error:', error);
                        let errorMessage = 'حدث خطأ أثناء إضافة المستخدم';
                        if (error.responseJSON && error.responseJSON.message) {
                            errorMessage = error.responseJSON.message;
                        } else if (error.responseJSON && error.responseJSON.errors) {
                            errorMessage = Object.values(error.responseJSON.errors)[0][0];
                        }
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'error',
                            title: errorMessage,
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                }).always(() => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = 'حفظ';
                });
                
            });
        },

        reloadDataTable: function() {
            if ($('.data-table').length && $.fn.DataTable.isDataTable('.data-table')) {
                $('.data-table').DataTable().ajax.reload();
            }
        }
    };

    AdminUser.init();

    // Reset Password Button Handler
    $(document).on('click', '.reset-password-btn', function() {
        var userId = $(this).data('id');
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: 'سيتم إرسال كلمة مرور جديدة إلى المستخدم!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'نعم، أعد التعيين',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: window.config.routes.resetPasswordEndpoint.replace('USER_ID', userId),
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: response.message,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'حدث خطأ أثناء إعادة تعيين كلمة المرور.',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                });
            }
        });
    });
});
