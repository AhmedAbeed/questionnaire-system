@extends('layouts.app')

@section('breadcrumb')
    <x-layouts.breadcrumbbar
        title="تفاصيل عضو هيئة التدريس"
        :breadcrumbs="[
            ['name' => 'الرئيسية', 'url' => route('home'), 'active' => false],
            ['name' => 'الأكاديمية', 'active' => true],
            ['name' => 'أعضاء هيئة التدريس', 'url' => route('academic.faculty-member.index'), 'active' => false],
            ['name' => $facultyMember->user->full_name, 'active' => true],
        ]"
    />
@endsection

@section('content')
    <div class="row">
        <!-- Faculty Member Info Card -->
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">معلومات عضو هيئة التدريس</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Left Table: Basic Info -->
                        <div class="col-md-6">
                            <table class="table mb-0">
                                <tr>
                                    <th>الاسم</th>
                                    <td>{{ $facultyMember->user->full_name }}</td>
                                </tr>
                                <tr>
                                    <th>الرقم القومي</th>
                                    <td>{{ $facultyMember->national_id }}</td>
                                </tr>
                                <tr>
                                    <th>الكلية</th>
                                    <td>{{ $facultyMember->faculty->name }}</td>
                                </tr>
                            </table>
                        </div>
                        <!-- Right Table: Contact Info -->
                        <div class="col-md-6">
                            <table class="table mb-0">
                                <tr>
                                    <th>البريد الإلكتروني الأكاديمي</th>
                                    <td>{{ $facultyMember->academic_email }}</td>
                                </tr>
                                <tr>
                                    <th>البريد الإلكتروني الشخصي</th>
                                    <td>{{ $facultyMember->personal_email }}</td>
                                </tr>
                                <tr>
                                    <th>رقم الهاتف</th>
                                    <td>{{ $facultyMember->phone_number }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Questionnaires DataTable Card -->
        <div class="col-lg-12 mt-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">الاستبيانات</h4>
                </div>
                <div class="card-body">
                    <x-data-table
                        :headers="['#', 'عنوان الاستبيان', 'الفصل الدراسي', 'المقرر', 'التاريخ', 'الحالة', 'نسبة المشاركة', 'التقييم المتوسط', 'الإجراءات']"
                        :columns="[
                            ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
                            ['data' => 'name', 'name' => 'name'],
                            ['data' => 'semester', 'name' => 'semester'],
                            ['data' => 'course', 'name' => 'course'],
                            ['data' => 'created_at', 'name' => 'created_at'],
                            ['data' => 'status', 'name' => 'status'],
                            ['data' => 'completion_rate', 'name' => 'completion_rate'],
                            ['data' => 'average_rate', 'name' => 'average_rate'],
                            ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
                        ]"
                        data-url="{{ route('academic.faculty-member.specific.datatable', $facultyMember->id) }}"
                    />
                </div>
            </div>
        </div>
    </div>
@endsection 