@extends('layouts.app')

@section('breadcrumb')
    <x-layouts.breadcrumbbar
        title="تفاصيل المادة"
        :breadcrumbs="[
            ['name' => 'الرئيسية', 'url' => route('home'), 'active' => false],
            ['name' => 'الأكاديمية', 'active' => true],
            ['name' => 'المقررات الدراسية', 'url' => route('academic.courses.index'), 'active' => false],
            ['name' => ' مقرر ' . $course->name, 'active' => true],
        ]"
    />
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">معلومات المادة</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table">
                                <tr>
                                    <th>كود المادة</th>
                                    <td>{{ $course->code }}</td>
                                </tr>
                                <tr>
                                    <th>اسم المادة</th>
                                    <td>{{ $course->name }}</td>
                                </tr>
                                <tr>
                                    <th>الكلية</th>
                                    <td>{{ $course->faculty->name }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table">
                                <tr>
                                    <th>عدد الطلاب</th>
                                    <td>{{$course->student_counts}}</td>
                                </tr>
                                <tr>
                                    <th>عدد الساعات</th>
                                    <td>{{ $course->credit_hours }}</td>
                                </tr>
                                
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12 mt-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">الاستبيانات</h4>
                </div>
                <div class="card-body">
                    <x-data-table
                        :headers="['#', 'عنوان الاستبيان', 'الفصل الدراسي', 'المحاضر', 'التاريخ', 'الحالة', 'عدد الطلاب', 'نسبة المشاركة', 'الإجراءات']"
                        :columns="[
                            ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
                            ['data' => 'name', 'name' => 'name'],
                            ['data' => 'semester', 'name' => 'semester'],
                            ['data' => 'instructor', 'name' => 'instructor'],
                            ['data' => 'created_at', 'name' => 'created_at'],
                            ['data' => 'status', 'name' => 'status'],
                            ['data' => 'students_count', 'name' => 'students_count'],
                            ['data' => 'completion_rate', 'name' => 'completion_rate'],
                            ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
                        ]"
                        data-url="{{ route('academic.courses.specific.datatable', $course->id) }}"
                    />
                </div>
            </div>
        </div>
    </div>
@endsection
