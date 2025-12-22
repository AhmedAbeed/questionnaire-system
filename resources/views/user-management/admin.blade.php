@extends('layouts.app')

@section('breadcrumb')
    <x-layouts.breadcrumbbar
        title="المستخدمين"
        :breadcrumbs="[
            ['name' => 'الرئيسية', 'url' => route('admin.home'), 'active' => false],
            ['name' => 'المستخدمين', 'url' => '#', 'active' => true],
        ]"
    />
@endsection

@section('content')
    <div class="row">
    <x-stat-card 
            title="إجمالي المستخدمين"
            icon="icon-users"
            badge_color="primary" 
            id="total-admins"
        />
    </div>

    <x-page-header
        :title="'المستخدمين'"
        :page-description="'إدارة المستخدمين'"
        :action-items="[
            [
                'type' => 'button',
                'label' => __('Add User'),
                'icon' => 'fa fa-plus',
                'class' => 'btn btn-primary',
                'modal' => [
                    'target' => 'addUserModal'
                ]
            ]
        ]"
    />

    <div class="col-lg-12">
        <x-data-table
            :headers="['#', 'الاسم', 'البريد الإلكتروني', 'الدور', 'الحالة', 'تاريخ التسجيل', 'الإجراءات']"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
                ['data' => 'name', 'name' => 'name', 'searchable' => true],
                ['data' => 'email', 'name' => 'email', 'searchable' => true],
                ['data' => 'role', 'name' => 'role', 'searchable' => true],
                ['data' => 'status', 'name' => 'status', 'searchable' => false],
                ['data' => 'created_at', 'name' => 'created_at', 'searchable' => false],
                ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false]
            ]"
            data-url="{{ route('users.admin.dataTable') }}"
        />
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header d-flex justify-content-between">
                    <div class="div">
                        <h5 class="modal-title" id="addUserModalLabel">إضافة مستخدم جديد</h5>
                    </div>
                    <div class="div">
                        <button type="button" class="btn-close btn-close-white" aria-label="Close" data-bs-dismiss="modal"></button>
                    </div>
                </div>
                <form id="addUserForm" action="{{ route('users.admin.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">الاسم</label>
                            <input type="text" class="form-control border border-primary" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">البريد الإلكتروني</label>
                            <input type="email" class="form-control border border-primary" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الدور</label>
                            <select class="form-select border border-primary" name="role" id="roleSelect" required>
                                <option value="">اختر الدور</option>
                                <option value="admin">مدير النظام</option>
                                <option value="faculty_dean">عميد كلية</option>
                                <option value="quality_manager">مدير الجودة</option>
                            </select>
                        </div>
                        <div class="mb-3 faculty-section" style="display: none;">
                            <label class="form-label">الكلية</label>
                            <select class="form-select" name="faculty_id" id="facultySelect">
                                <option value="">اختر الكلية</option>
                                @foreach($faculties as $faculty)
                                    <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">ملاحظة: يمكن إضافة عمداء الكليات فقط</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">حفظ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window.config = {
        routes: {
            statsEndpoint: "{{ route('users.admin.stats') }}",
            storeEndpoint: "{{ route('users.admin.store') }}",
            resetPasswordEndpoint: "{{ route('users.admin.resetPassword', ['userId' => 'USER_ID']) }}"
        }
    };
</script>
<script src="{{ asset('assets/js/pages/admin-user.js') }}"></script>
@endpush




